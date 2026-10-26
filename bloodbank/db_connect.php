<?php
/**
 * Firestore connection bootstrap.
 * Replaces the old mysqli connection — every page still just does
 * `require 'db_connect.php';` and gets a ready-to-use $db object.
 *
 * SETUP:
 *   1. Firebase Console -> Project Settings -> Service Accounts ->
 *      "Generate new private key". Save the JSON file OUTSIDE your
 *      public web root, e.g. one folder above it, as service-account.json.
 *   2. Update SERVICE_ACCOUNT_KEY_PATH below to point to it.
 *   3. In Firestore, set security rules to deny public client access
 *      (this app talks to Firestore only from the server, so client-side
 *      rules can just be `allow read, write: if false;`).
 */

require_once __DIR__ . '/firebase/Firestore.php';

// On a host without a private folder (Render, Railway…), put the key in the
// FIREBASE_SERVICE_ACCOUNT_JSON environment variable (raw JSON, or base64 of it).
$envKey = getenv('FIREBASE_SERVICE_ACCOUNT_JSON');
if ($envKey !== false && $envKey !== '') {
    $envKey = trim($envKey, " \t\r\n\"'");
    $json = ($envKey[0] === '{') ? $envKey : base64_decode($envKey);
    // Some hosts mangle the key's line breaks when it is pasted into a form; repair them.
    $keyData = json_decode($json, true);
    if (is_array($keyData) && !empty($keyData['private_key'])) {
        $pk = str_replace(["\\r\\n", "\\n", "\r\n"], "\n", $keyData['private_key']);
        if (strpos($pk, "\n") === false) {
            // Newlines were dropped entirely: rebuild the PEM from its base64 body.
            $body = preg_replace('/-----[A-Z ]+-----|\s+/', '', $pk);
            $pk = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($body, 64, "\n") . "-----END PRIVATE KEY-----\n";
        }
        $keyData['private_key'] = $pk;
        $json = json_encode($keyData);
        if (!openssl_pkey_get_private($pk)) {
            error_log('Firebase service account: private_key in FIREBASE_SERVICE_ACCOUNT_JSON is not a valid key.');
        }
    } else {
        error_log('Firebase service account: FIREBASE_SERVICE_ACCOUNT_JSON is not valid JSON (check you pasted the whole file).');
    }
    $keyPath = sys_get_temp_dir() . '/firebase-sa-' . md5($json) . '.json';
    if (!is_file($keyPath)) {
        file_put_contents($keyPath, $json);
        @chmod($keyPath, 0600);
    }
    define('SERVICE_ACCOUNT_KEY_PATH', $keyPath);
} else {
    define('SERVICE_ACCOUNT_KEY_PATH', __DIR__ . '/../service-account.json');
}

try {
    $db = new Firestore(SERVICE_ACCOUNT_KEY_PATH);
} catch (Exception $e) {
    error_log("Firestore connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
