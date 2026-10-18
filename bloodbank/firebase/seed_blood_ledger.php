<?php
/**
 * Fills the blood_ledger collection up to exactly 20 entries.
 *
 * Run (Command Prompt):
 *   cd D:\xampp\htdocs\project_firebase\bloodbank
 *   D:\xampp\php\php.exe firebase\seed_blood_ledger.php
 *
 * 1. Back-fills entries for requests already marked Fulfilled and
 *    appointments already marked Completed (real activity first).
 * 2. Tops up with sample entries until the ledger holds 20 entries.
 *
 * Safe to run more than once: every entry has a fixed source_id, so an entry
 * that already exists is skipped. If the ledger already has 20 or more
 * entries nothing is added. Sample dates are relative to today.
 */

require_once __DIR__ . '/Firestore.php';
require_once __DIR__ . '/../inventory_helper.php';

$target = 20;
$db = new Firestore(__DIR__ . '/../../service-account.json');

// 1. Real activity first
$back = 0;
foreach ($db->getAll('blood_requests') as $req) {
    if (($req['request_status'] ?? '') === 'Fulfilled' && ledger_from_request($db, $req)) {
        $back++;
    }
}
foreach ($db->getAll('donation_appointments') as $apt) {
    if (($apt['status'] ?? '') === 'Completed' && ledger_from_appointment($db, $apt)) {
        $back++;
    }
}
echo "Back-filled from existing records: $back\n";

// 2. Samples: [days ago, type, from (donor), to (patient), group, component, units, hospital/location, remarks]
$samples = [
    [0,  'TAKEN', 'Ravi Kumar',     '',                'O+',  'Whole Blood', 1, 'Lifeline Blood Bank Office, HAL 3rd Stage', 'Walk-in donor'],
    [0,  'GIVEN', '',               'Meena Iyer',      'A+',  'RBC',         2, 'Victoria Hospital',  'Surgery support'],
    [1,  'TAKEN', 'Anita Rao',      '',                'B+',  'Whole Blood', 1, 'MG Road Camp - Brigade Road, Bangalore', 'Camp donation'],
    [1,  'GIVEN', '',               'Suresh Babu',     'O-',  'Whole Blood', 2, 'Narayana Health',    'Emergency trauma case'],
    [2,  'TAKEN', 'Imran Pasha',    '',                'AB+', 'Whole Blood', 1, 'University Campus Camp - Bangalore University Auditorium', 'Student drive'],
    [3,  'GIVEN', '',               'Kavya Shetty',    'B-',  'Platelets',   1, 'Fortis Hospital',    'Dengue patient'],
    [3,  'TAKEN', 'Deepak Nair',    '',                'A-',  'Whole Blood', 1, 'Electronic City Camp - Infosys Campus', 'Corporate camp'],
    [4,  'GIVEN', '',               'Lakshmi Devi',    'AB-', 'Plasma',      1, 'Apollo Hospitals',   'Burn unit'],
    [5,  'TAKEN', 'Sneha Joshi',    '',                'O+',  'Whole Blood', 1, 'Lifeline Blood Bank Office, HAL 3rd Stage', 'Regular donor'],
    [6,  'GIVEN', '',               'Mohammed Farooq', 'A+',  'Whole Blood', 3, 'Victoria Hospital',  'Accident victim'],
    [8,  'TAKEN', 'Priya Menon',    '',                'B+',  'Whole Blood', 1, 'MG Road Camp - Brigade Road, Bangalore', 'Camp donation'],
    [10, 'GIVEN', '',               'Rohit Sharma',    'O+',  'RBC',         2, 'Narayana Health',    'Anaemia treatment'],
    [12, 'TAKEN', 'Nikhil Gowda',   '',                'AB+', 'Whole Blood', 1, 'University Campus Camp - Bangalore University Auditorium', 'Student drive'],
    [14, 'GIVEN', '',               'Geeta Patil',     'A-',  'Plasma',      1, 'Fortis Hospital',    'Clotting disorder'],
    [18, 'TAKEN', 'Arjun Reddy',    '',                'O-',  'Whole Blood', 1, 'Lifeline Blood Bank Office, HAL 3rd Stage', 'Rare group donor'],
    [21, 'GIVEN', '',               'Fatima Begum',    'B+',  'Platelets',   2, 'Apollo Hospitals',   'Chemotherapy support'],
    [24, 'TAKEN', 'Vikram Singh',   '',                'A+',  'Whole Blood', 1, 'MG Road Camp - Brigade Road, Bangalore', 'Camp donation'],
    [26, 'GIVEN', '',               'Pooja Hegde',     'AB+', 'RBC',         1, 'Victoria Hospital',  'Post-delivery care'],
    [28, 'TAKEN', 'Harini Krishnan','',                'B-',  'Whole Blood', 1, 'Lifeline Blood Bank Office, HAL 3rd Stage', 'Regular donor'],
    [30, 'GIVEN', '',               'Ganesh Rao',      'O+',  'Whole Blood', 2, 'Narayana Health',    'Cardiac surgery'],
];

$existing = count($db->getAll('blood_ledger'));
echo "Ledger entries before adding samples: $existing\n";

$added = 0;
foreach ($samples as $i => [$daysAgo, $type, $from, $to, $group, $component, $units, $place, $remarks]) {
    if ($existing + $added >= $target) {
        break;
    }
    $ok = ledger_record_once($db, 'sample_' . ($i + 1), [
        'entry_date' => date('Y-m-d', strtotime("-{$daysAgo} days")),
        'transaction_type' => $type,
        'from_patient_name' => $from,
        'to_patient_name' => $to,
        'blood_group' => $group,
        'component' => $component,
        'units' => $units,
        'hospital_name' => $place,
        'remarks' => $remarks . ' (sample)',
    ]);
    $added += $ok ? 1 : 0;
}
echo "Sample entries added: $added\n";
echo "Total ledger entries now: " . count($db->getAll('blood_ledger')) . "\n";
