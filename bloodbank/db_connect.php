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
    $json = ($envKey[0] === '{') ? $envKey : base64_decode($envKey);
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
