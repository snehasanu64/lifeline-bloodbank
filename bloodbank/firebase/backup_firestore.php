<?php
/**
 * Backs up the whole project: every Firestore collection (as JSON) plus a
 * copy of the website files.
 *
 * Run (Command Prompt):
 *   cd D:\xampp\htdocs\project_firebase\bloodbank
 *   D:\xampp\php\php.exe firebase\backup_firestore.php
 *
 * Output goes to  D:\xampp\backups\bloodbank_YYYY-MM-DD_HHMMSS\
 *   data\<collection>.json   - all documents of each Firestore collection
 *   site\                    - copy of the bloodbank folder (code + images)
 *   manifest.txt             - what was saved and how many documents
 *
 * The backup folder is OUTSIDE htdocs on purpose (it contains donor details
 * and password hashes, so it must not be reachable from a browser).
 * service-account.json is never copied.
 */

if (PHP_SAPI !== 'cli') {
    exit("Run this from the command line.\n");
}

require_once __DIR__ . '/Firestore.php';
$db = new Firestore(__DIR__ . '/../../service-account.json');

$root = 'D:/xampp/backups';
$dir = $root . '/bloodbank_' . date('Y-m-d_His');
@mkdir($dir . '/data', 0777, true);

$collections = [
    'donors', 'blood_requests', 'donation_appointments', 'blood_stock',
    'inventory_transactions', 'blood_ledger', 'contact_messages',
    'camps', 'admins', 'settings',
];

$manifest = ["Lifeline Blood Bank backup - " . date('Y-m-d H:i:s'), "", "Firestore collections:"];
foreach ($collections as $c) {
    $docs = $db->getAll($c);
    file_put_contents("$dir/data/$c.json", json_encode($docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $manifest[] = sprintf("  %-24s %d documents", $c, count($docs));
    echo sprintf("%-24s %d documents\n", $c, count($docs));
}

// Copy website files (skip any key file and older backups just in case)
$src = realpath(__DIR__ . '/..');
$files = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
foreach ($it as $item) {
    $rel = str_replace('\\', '/', substr($item->getPathname(), strlen($src) + 1));
    if (stripos($rel, 'service-account') !== false || strpos($rel, 'backups/') === 0) {
        continue;
    }
    $target = "$dir/site/$rel";
    if ($item->isDir()) {
        @mkdir($target, 0777, true);
    } else {
        @mkdir(dirname($target), 0777, true);
        copy($item->getPathname(), $target);
        $files++;
    }
}
$manifest[] = "";
$manifest[] = "Website files copied: $files";
$manifest[] = "To restore the website: copy the contents of 'site' back into D:\\xampp\\htdocs\\project_firebase\\bloodbank";
$manifest[] = "To restore data: each data\\<collection>.json holds the documents (field values plus _id).";
file_put_contents("$dir/manifest.txt", implode("\r\n", $manifest));

echo "\nBackup saved to: " . str_replace('/', '\\', $dir) . "\n";
echo "Website files copied: $files\n";
