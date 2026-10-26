<?php
/**
 * Gets an OAuth2 access token for a Google service account using the
 * JWT bearer flow — no Composer / SDK required, just openssl (built into PHP).
 *
 * How to get the service account JSON key:
 *   1. Firebase Console -> Project Settings -> Service Accounts
 *   2. Click "Generate new private key" -> downloads a .json file
 *   3. Put that file somewhere OUTSIDE your web root (never public), e.g.
 *      /firebase/service-account.json (already gitignored below)
 */

class ServiceAccountAuth
{
    private string $keyFile;
    private string $cacheFile;
    private array $key;

    public function __construct(string $keyFile)
    {
        if (!file_exists($keyFile)) {
            throw new Exception("Firebase service account key not found at: $keyFile");
        }
        $this->keyFile = $keyFile;
        $this->cacheFile = sys_get_temp_dir() . '/firestore_token_' . md5($keyFile) . '.json';
        $this->key = json_decode(file_get_contents($keyFile), true);

        if (!$this->key || empty($this->key['private_key']) || empty($this->key['client_email'])) {
            throw new Exception("Invalid service account key file.");
        }
    }

    public function getProjectId(): string
    {
        return $this->key['project_id'];
    }

    public function getAccessToken(): string
    {
        // Reuse a cached token if it still has more than 60s left.
        if (file_exists($this->cacheFile)) {
            $cached = json_decode(file_get_contents($this->cacheFile), true);
            if ($cached && isset($cached['expires_at']) && $cached['expires_at'] > time() + 60) {
                return $cached['access_token'];
            }
        }

        $token = $this->fetchNewToken();
        file_put_contents($this->cacheFile, json_encode($token));
        return $token['access_token'];
    }

    private function fetchNewToken(): array
    {
        $now = time();
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        $claims = $this->base64UrlEncode(json_encode([
            'iss'   => $this->key['client_email'],
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $unsigned = $header . '.' . $claims;

        $signature = '';
        $ok = openssl_sign($unsigned, $signature, $this->key['private_key'], 'sha256WithRSAEncryption');
        if (!$ok) {
            throw new Exception('Failed to sign JWT for Firebase service account.');
        }

        $jwt = $unsigned . '.' . $this->base64UrlEncode($signature);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        if (is_file('D:/xampp/apache/bin/curl-ca-bundle.crt')) {
            curl_setopt($ch, CURLOPT_CAINFO, 'D:/xampp/apache/bin/curl-ca-bundle.crt'); // local XAMPP only
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($httpCode !== 200 || empty($data['access_token'])) {
            throw new Exception('Failed to get Firebase access token: ' . $response);
        }

        return [
            'access_token' => $data['access_token'],
            'expires_at'   => $now + ($data['expires_in'] ?? 3600),
        ];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
