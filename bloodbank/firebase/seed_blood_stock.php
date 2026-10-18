<?php
/**
 * Seeds ONLY the blood_stock collection, using the same values as the
 * dropdowns on find.php and inventory.php, so searching actually returns rows.
 *
 * Put this file inside:  D:\xampp\htdocs\project_firebase\bloodbank\firebase\
 *
 * Run (Command Prompt):
 *   cd D:\xampp\htdocs\project_firebase\bloodbank
 *   D:\xampp\php\php.exe firebase\seed_blood_stock.php
 *
 * Safe by default: if blood_stock already has documents, it stops and does
 * nothing (so you never get duplicates). To wipe blood_stock and reseed:
 *   D:\xampp\php\php.exe firebase\seed_blood_stock.php --reset
 *
 * Preview without touching Firestore:
 *   D:\xampp\php\php.exe firebase\seed_blood_stock.php --dry
 */

$banks = [
    // bank name                     => [state, district, city]
    ['LifeCare Blood Bank',          'Karnataka', 'Bangalore',       'Bengaluru'],
    ['Victoria Hospital Blood Bank', 'Karnataka', 'Bangalore South', 'Bengaluru'],
    ['Red Cross Blood Bank',         'Karnataka', 'Bangalore North', 'Bengaluru'],
    ['Narayana Blood Centre',        'Karnataka', 'Bangalore South', 'Bengaluru'],
];

$groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];

// component => shelf life in days (used for expiry_date)
$components = [
    'Whole Blood' => 35,
    'RBC'         => 42,
    'Plasma'      => 180,
    'Platelets'   => 7,
];

$rows = [];
$seed = 7; // fixed seed so every run produces the same numbers
foreach ($banks as [$bankName, $state, $district, $city]) {
    foreach ($groups as $group) {
        foreach ($components as $component => $shelfDays) {
            $seed = ($seed * 31 + 11) % 997;
            // Rare groups get lower stock
            $rare = in_array($group, ['AB-', 'B-', 'O-', 'A-'], true);
            $units = $rare ? 2 + ($seed % 9) : 8 + ($seed % 23);
            $age = $seed % 5;                       // collected 0-4 days ago
            $collected = date('Y-m-d', strtotime("-{$age} days"));
            $expires = date('Y-m-d', strtotime("$collected +{$shelfDays} days"));
            $rows[] = [
                'blood_bank_name' => $bankName,
                'state'           => $state,
                'district'        => $district,
                'city'            => $city,
                'blood_group'     => $group,
                'component'       => $component,
                'units_available' => $units,
                'collection_date' => $collected,
                'expiry_date'     => $expires,
                'last_updated'    => date('Y-m-d H:i:s'),
            ];
        }
    }
}

if (in_array('--dry', $argv ?? [], true)) {
    echo "Dry run: would create " . count($rows) . " blood_stock documents.\n";
    print_r($rows[0]);
    exit(0);
}

require_once __DIR__ . '/Firestore.php';
$db = new Firestore(__DIR__ . '/../../service-account.json');

$existing = $db->getAll('blood_stock');
if (count($existing) > 0) {
    if (!in_array('--reset', $argv ?? [], true)) {
        echo "blood_stock already has " . count($existing) . " documents. Nothing was changed.\n";
        echo "To delete them and reseed, run again with --reset\n";
        exit(0);
    }
    echo "Deleting " . count($existing) . " existing blood_stock documents...\n";
    foreach ($existing as $doc) {
        $db->delete('blood_stock', $doc['_id']);
    }
}

echo "Creating " . count($rows) . " blood_stock documents...\n";
foreach ($rows as $row) {
    $db->add('blood_stock', $row);
}
echo "Done.\n";
