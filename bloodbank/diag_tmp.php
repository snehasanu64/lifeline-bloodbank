<?php
// TEMPORARY diagnostic - will be deleted. Prints no secrets.
header('Content-Type: text/plain');
if (!hash_equals('e666165563ec2f4c', $_GET['k'] ?? '')) { http_response_code(404); exit; }
$e = getenv('FIREBASE_SERVICE_ACCOUNT_JSON');
echo "env set: ", $e ? 'yes, length ' . strlen($e) . ', first char ' . $e[0] : 'no', "\n";
echo "php: ", PHP_VERSION, "  curl ext: ", extension_loaded('curl') ? 'yes' : 'NO', "  openssl: ", extension_loaded('openssl') ? 'yes' : 'NO', "\n";
echo "temp dir writable: ", is_writable(sys_get_temp_dir()) ? 'yes' : 'NO', "\n";
try {
    require __DIR__ . '/db_connect.php';
    echo "key file loaded, project: ", $db ? 'ok' : 'none', "\n";
    $id = $db->add('_diag_test', ['t' => date('c')]);
    $db->delete('_diag_test', $id);
    echo "Firestore write+delete: OK\n";
} catch (Throwable $t) {
    echo "ERROR: ", get_class($t), ": ", $t->getMessage(), "\n";
}
$ch = curl_init('https://oauth2.googleapis.com/token'); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>1, CURLOPT_TIMEOUT=>10, CURLOPT_NOBODY=>1]);
curl_exec($ch); echo "reach oauth2.googleapis.com: HTTP ", curl_getinfo($ch, CURLINFO_HTTP_CODE), " ", curl_error($ch), "\n";
