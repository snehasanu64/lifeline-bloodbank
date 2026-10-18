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

define('SERVICE_ACCOUNT_KEY_PATH', __DIR__ . '/../service-account.json');

try {
    $db = new Firestore(SERVICE_ACCOUNT_KEY_PATH);
} catch (Exception $e) {
    error_log("Firestore connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
