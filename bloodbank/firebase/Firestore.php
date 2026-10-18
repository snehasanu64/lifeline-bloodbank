<?php
require_once __DIR__ . '/ServiceAccountAuth.php';

/**
 * Small Firestore REST API client.
 * Not a full SDK — just the operations this project needs:
 *   getAll, getWhere, getById, add, set, update, delete, runQuery(raw)
 *
 * Documents are returned as plain PHP associative arrays with an added
 * '_id' key holding the Firestore document ID (so code can still do
 * $row['_id'] the way it used to do $row['id']).
 */
class Firestore
{
    private ServiceAccountAuth $auth;
    private string $baseUrl;

    public function __construct(string $serviceAccountKeyPath)
    {
        $this->auth = new ServiceAccountAuth($serviceAccountKeyPath);
        $projectId = $this->auth->getProjectId();
        $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents";
    }

    // ---------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------

    /** Get every document in a collection. */
    public function getAll(string $collection, ?string $orderBy = null, ?string $direction = 'ASCENDING', ?int $limit = null): array
    {
        return $this->runQuery($collection, [], $orderBy, $direction, $limit);
    }

    /**
     * Get documents matching one or more simple equality/comparison filters.
     * $filters = [ ['field' => 'email', 'op' => 'EQUAL', 'value' => 'x@y.com'], ... ]
     * Valid ops: EQUAL, NOT_EQUAL, LESS_THAN, LESS_THAN_OR_EQUAL,
     *            GREATER_THAN, GREATER_THAN_OR_EQUAL, ARRAY_CONTAINS
     */
    public function getWhere(string $collection, array $filters, ?string $orderBy = null, ?string $direction = 'ASCENDING', ?int $limit = null): array
    {
        return $this->runQuery($collection, $filters, $orderBy, $direction, $limit);
    }

    /** Get one document by its Firestore document ID. Returns null if not found. */
    public function getById(string $collection, string $id): ?array
    {
        $res = $this->request('GET', "/{$collection}/{$id}");
        if ($res === null || isset($res['error'])) {
            return null;
        }
        return $this->decodeDocument($res);
    }

    /** Convenience: get the first document matching a single field = value. */
    public function getOneWhere(string $collection, string $field, $value): ?array
    {
        $rows = $this->getWhere($collection, [['field' => $field, 'op' => 'EQUAL', 'value' => $value]], null, 'ASCENDING', 1);
        return $rows[0] ?? null;
    }

    /** Add a new document with an auto-generated ID. Returns the new ID. */
    public function add(string $collection, array $data): string
    {
        $res = $this->request('POST', "/{$collection}", $this->encodeFields($data));
        $parts = explode('/', $res['name']);
        return end($parts);
    }

    /** Create/overwrite a document at a specific ID. */
    public function set(string $collection, string $id, array $data): void
    {
        $this->request('PATCH', "/{$collection}/{$id}", $this->encodeFields($data));
    }

    /** Update only the given fields on an existing document. */
    public function update(string $collection, string $id, array $data): void
    {
        $mask = implode('&', array_map(fn($f) => 'updateMask.fieldPaths=' . urlencode($f), array_keys($data)));
        $this->request('PATCH', "/{$collection}/{$id}?{$mask}", $this->encodeFields($data));
    }

    public function delete(string $collection, string $id): void
    {
        $this->request('DELETE', "/{$collection}/{$id}");
    }

    public function count(string $collection, array $filters = []): int
    {
        // Simple client-side count (fine at this project's data scale).
        return count($this->getWhere($collection, $filters));
    }

    // ---------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------

    private function runQuery(string $collection, array $filters, ?string $orderBy, ?string $direction, ?int $limit): array
    {
        $structuredQuery = [
            'from' => [['collectionId' => $collection]],
        ];

        if (count($filters) === 1) {
            $structuredQuery['where'] = $this->buildFieldFilter($filters[0]);
        } elseif (count($filters) > 1) {
            $structuredQuery['where'] = [
                'compositeFilter' => [
                    'op' => 'AND',
                    'filters' => array_map(fn($f) => $this->buildFieldFilter($f), $filters),
                ],
            ];
        }

        if ($orderBy) {
            $structuredQuery['orderBy'] = [[
                'field' => ['fieldPath' => $orderBy],
                'direction' => $direction ?? 'ASCENDING',
            ]];
        }

        if ($limit) {
            $structuredQuery['limit'] = $limit;
        }

        $res = $this->request('POST', ':runQuery', ['structuredQuery' => $structuredQuery], true);

        $rows = [];
        foreach ($res as $item) {
            if (isset($item['document'])) {
                $rows[] = $this->decodeDocument($item['document']);
            }
        }
        return $rows;
    }

    private function buildFieldFilter(array $f): array
    {
        return [
            'fieldFilter' => [
                'field' => ['fieldPath' => $f['field']],
                'op' => $f['op'],
                'value' => $this->encodeValue($f['value']),
            ],
        ];
    }

    /**
     * $path can be relative ("/collection/id") or a root-level verb
     * like ":runQuery" which is sent against the collection root.
     */
    private function request(string $method, string $path, ?array $body = null, bool $isRootVerb = false)
    {
        $url = $isRootVerb ? $this->baseUrl . $path : $this->baseUrl . $path;
        $token = $this->auth->getAccessToken();

        $ch = curl_init($url);
        $opts = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_CAINFO => 'D:/xampp/apache/bin/curl-ca-bundle.crt',
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            error_log("Firestore API error ({$httpCode}) on {$method} {$path}: {$response}");
            if ($httpCode === 404) {
                return null;
            }
            throw new Exception("Firestore request failed: " . ($decoded['error']['message'] ?? $response));
        }

        return $decoded;
    }

    /** Convert a Firestore document (with typed fields) into a plain assoc array. */
    private function decodeDocument(array $doc): array
    {
        $out = [];
        foreach ($doc['fields'] ?? [] as $key => $value) {
            $out[$key] = $this->decodeValue($value);
        }
        $nameParts = explode('/', $doc['name']);
        $out['_id'] = end($nameParts);
        return $out;
    }

    private function decodeValue(array $value)
    {
        if (array_key_exists('stringValue', $value)) return $value['stringValue'];
        if (array_key_exists('integerValue', $value)) return (int)$value['integerValue'];
        if (array_key_exists('doubleValue', $value)) return (float)$value['doubleValue'];
        if (array_key_exists('booleanValue', $value)) return $value['booleanValue'];
        if (array_key_exists('nullValue', $value)) return null;
        if (array_key_exists('timestampValue', $value)) return $value['timestampValue'];
        if (array_key_exists('arrayValue', $value)) {
            return array_map(fn($v) => $this->decodeValue($v), $value['arrayValue']['values'] ?? []);
        }
        if (array_key_exists('mapValue', $value)) {
            $map = [];
            foreach ($value['mapValue']['fields'] ?? [] as $k => $v) {
                $map[$k] = $this->decodeValue($v);
            }
            return $map;
        }
        return null;
    }

    private function encodeFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[$key] = $this->encodeValue($value);
        }
        return ['fields' => $fields];
    }

    private function encodeValue($value): array
    {
        if (is_null($value)) return ['nullValue' => null];
        if (is_bool($value)) return ['booleanValue' => $value];
        if (is_int($value)) return ['integerValue' => (string)$value];
        if (is_float($value)) return ['doubleValue' => $value];
        if (is_array($value)) {
            // Associative -> mapValue, list -> arrayValue
            if (array_keys($value) !== range(0, count($value) - 1)) {
                $fields = [];
                foreach ($value as $k => $v) {
                    $fields[$k] = $this->encodeValue($v);
                }
                return ['mapValue' => ['fields' => $fields]];
            }
            return ['arrayValue' => ['values' => array_map(fn($v) => $this->encodeValue($v), $value)]];
        }
        return ['stringValue' => (string)$value];
    }
}
