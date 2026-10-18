<?php
/**
 * Run this ONCE from the command line to seed Firestore with sample data:
 *
 *   From your project folder (e.g. D:\xampp\htdocs\project_firebase\bloodbank):
 *   D:\xampp\php\php.exe firebase\seed_firestore.php
 *
 * Collections created: donors, blood_requests, donation_appointments,
 * blood_stock, inventory_transactions
 */

require_once __DIR__ . '/Firestore.php';

$keyPath = __DIR__ . '/../../service-account.json';
$db = new Firestore($keyPath);

echo "Seeding blood_stock...\n";
$bloodStock = [
    ['blood_bank_name' => 'Lifeline Blood Bank', 'state' => 'Karnataka', 'district' => 'Bengaluru Urban', 'city' => 'Bengaluru', 'blood_group' => 'A+', 'component' => 'Whole Blood', 'units_available' => 18, 'collection_date' => '2026-04-18', 'expiry_date' => '2026-04-28'],
    ['blood_bank_name' => 'Lifeline Blood Bank', 'state' => 'Karnataka', 'district' => 'Bengaluru Urban', 'city' => 'Bengaluru', 'blood_group' => 'A+', 'component' => 'Packed RBC', 'units_available' => 22, 'collection_date' => '2026-04-16', 'expiry_date' => '2026-05-02'],
    ['blood_bank_name' => 'Lifeline Blood Bank', 'state' => 'Karnataka', 'district' => 'Bengaluru Urban', 'city' => 'Bengaluru', 'blood_group' => 'B+', 'component' => 'Whole Blood', 'units_available' => 14, 'collection_date' => '2026-04-17', 'expiry_date' => '2026-04-30'],
    ['blood_bank_name' => 'Lifeline Blood Bank', 'state' => 'Karnataka', 'district' => 'Bengaluru Urban', 'city' => 'Bengaluru', 'blood_group' => 'B+', 'component' => 'Packed RBC', 'units_available' => 16, 'collection_date' => '2026-04-14', 'expiry_date' => '2026-05-01'],
    ['blood_bank_name' => 'Lifeline Blood Bank', 'state' => 'Karnataka', 'district' => 'Bengaluru Urban', 'city' => 'Bengaluru', 'blood_group' => 'O+', 'component' => 'Whole Blood', 'units_available' => 26, 'collection_date' => '2026-04-19', 'expiry_date' => '2026-05-04'],
    ['blood_bank_name' => 'Lifeline Blood Bank', 'state' => 'Karnataka', 'district' => 'Bengaluru Urban', 'city' => 'Bengaluru', 'blood_group' => 'O+', 'component' => 'Packed RBC', 'units_available' => 28, 'collection_date' => '2026-04-20', 'expiry_date' => '2026-05-06'],
    ['blood_bank_name' => 'Lifeline Blood Bank', 'state' => 'Karnataka', 'district' => 'Bengaluru Urban', 'city' => 'Bengaluru', 'blood_group' => 'AB+', 'component' => 'Whole Blood', 'units_available' => 8, 'collection_date' => '2026-04-15', 'expiry_date' => '2026-04-26'],
    ['blood_bank_name' => 'Lifeline Blood Bank', 'state' => 'Karnataka', 'district' => 'Bengaluru Urban', 'city' => 'Bengaluru', 'blood_group' => 'AB+', 'component' => 'Platelets', 'units_available' => 10, 'collection_date' => '2026-04-21', 'expiry_date' => '2026-04-29'],
    ['blood_bank_name' => 'Lifeline Blood Bank', 'state' => 'Karnataka', 'district' => 'Bengaluru Urban', 'city' => 'Bengaluru', 'blood_group' => 'O-', 'component' => 'Whole Blood', 'units_available' => 6, 'collection_date' => '2026-04-13', 'expiry_date' => '2026-04-27'],
    ['blood_bank_name' => 'Lifeline Blood Bank', 'state' => 'Karnataka', 'district' => 'Bengaluru Urban', 'city' => 'Bengaluru', 'blood_group' => 'A-', 'component' => 'Plasma', 'units_available' => 7, 'collection_date' => '2026-04-12', 'expiry_date' => '2026-05-03'],
    ['blood_bank_name' => 'North Care Blood Center', 'state' => 'Karnataka', 'district' => 'Mysuru', 'city' => 'Mysuru', 'blood_group' => 'A+', 'component' => 'Platelets', 'units_available' => 15, 'collection_date' => '2026-04-18', 'expiry_date' => '2026-04-25'],
    ['blood_bank_name' => 'North Care Blood Center', 'state' => 'Karnataka', 'district' => 'Mysuru', 'city' => 'Mysuru', 'blood_group' => 'O+', 'component' => 'Plasma', 'units_available' => 19, 'collection_date' => '2026-04-19', 'expiry_date' => '2026-05-05'],
    ['blood_bank_name' => 'North Care Blood Center', 'state' => 'Karnataka', 'district' => 'Mysuru', 'city' => 'Mysuru', 'blood_group' => 'B-', 'component' => 'Whole Blood', 'units_available' => 5, 'collection_date' => '2026-04-11', 'expiry_date' => '2026-04-24'],
    ['blood_bank_name' => 'North Care Blood Center', 'state' => 'Karnataka', 'district' => 'Mysuru', 'city' => 'Mysuru', 'blood_group' => 'AB-', 'component' => 'Packed RBC', 'units_available' => 4, 'collection_date' => '2026-04-10', 'expiry_date' => '2026-04-23'],
    ['blood_bank_name' => 'Metro Health Blood Unit', 'state' => 'Karnataka', 'district' => 'Hubli-Dharwad', 'city' => 'Hubli', 'blood_group' => 'A+', 'component' => 'Plasma', 'units_available' => 12, 'collection_date' => '2026-04-17', 'expiry_date' => '2026-05-01'],
    ['blood_bank_name' => 'Metro Health Blood Unit', 'state' => 'Karnataka', 'district' => 'Hubli-Dharwad', 'city' => 'Hubli', 'blood_group' => 'O+', 'component' => 'Platelets', 'units_available' => 21, 'collection_date' => '2026-04-20', 'expiry_date' => '2026-04-28'],
    ['blood_bank_name' => 'Metro Health Blood Unit', 'state' => 'Karnataka', 'district' => 'Hubli-Dharwad', 'city' => 'Hubli', 'blood_group' => 'B+', 'component' => 'Plasma', 'units_available' => 9, 'collection_date' => '2026-04-15', 'expiry_date' => '2026-04-30'],
    ['blood_bank_name' => 'Metro Health Blood Unit', 'state' => 'Karnataka', 'district' => 'Hubli-Dharwad', 'city' => 'Hubli', 'blood_group' => 'O-', 'component' => 'Packed RBC', 'units_available' => 3, 'collection_date' => '2026-04-09', 'expiry_date' => '2026-04-22'],
    ['blood_bank_name' => 'Central City Blood Bank', 'state' => 'Karnataka', 'district' => 'Belagavi', 'city' => 'Belagavi', 'blood_group' => 'A-', 'component' => 'Whole Blood', 'units_available' => 11, 'collection_date' => '2026-04-16', 'expiry_date' => '2026-04-26'],
    ['blood_bank_name' => 'Central City Blood Bank', 'state' => 'Karnataka', 'district' => 'Belagavi', 'city' => 'Belagavi', 'blood_group' => 'B+', 'component' => 'Platelets', 'units_available' => 13, 'collection_date' => '2026-04-18', 'expiry_date' => '2026-04-29'],
];
foreach ($bloodStock as $row) {
    $db->add('blood_stock', $row);
}

echo "Seeding donors...\n";
$donors = [
    ['name' => 'Rahul Sharma', 'email' => 'rahul@example.com', 'phone' => '9876543210', 'age' => 29, 'bloodgroup' => 'O+', 'address' => 'Bangalore', 'lastdonation' => '2026-01-15', 'created_at' => date('Y-m-d H:i:s')],
    ['name' => 'Priya Nair', 'email' => 'priya@example.com', 'phone' => '9876501234', 'age' => 26, 'bloodgroup' => 'A+', 'address' => 'Mysore', 'lastdonation' => '2025-12-10', 'created_at' => date('Y-m-d H:i:s')],
    ['name' => 'Imran Khan', 'email' => 'imran@example.com', 'phone' => '9876512345', 'age' => 32, 'bloodgroup' => 'B+', 'address' => 'Hubli', 'lastdonation' => null, 'created_at' => date('Y-m-d H:i:s')],
];
foreach ($donors as $row) {
    $db->add('donors', $row);
}

echo "Seeding blood_requests...\n";
$requests = [
    ['requester_name' => 'City Hospital', 'requester_email' => 'cityhospital@example.com', 'bloodgroup' => 'O+', 'quantity' => 4, 'reason' => 'Emergency surgery', 'status' => 'Pending'],
    ['requester_name' => 'MediCare Clinic', 'requester_email' => 'clinic@example.com', 'bloodgroup' => 'A+', 'quantity' => 2, 'reason' => 'Patient transfusion', 'status' => 'Fulfilled'],
];
foreach ($requests as $row) {
    $db->add('blood_requests', $row);
}

echo "Seeding donation_appointments...\n";
$appointments = [
    ['donor_email' => 'rahul@example.com', 'donor_name' => 'Rahul Sharma', 'bloodgroup' => 'O+', 'appointment_date' => date('Y-m-d', strtotime('+2 days')), 'appointment_time' => '9:00 AM - 11:00 AM', 'location' => 'Lifeline Office', 'status' => 'Pending', 'created_at' => date('Y-m-d H:i:s')],
    ['donor_email' => 'priya@example.com', 'donor_name' => 'Priya Nair', 'bloodgroup' => 'A+', 'appointment_date' => date('Y-m-d', strtotime('+3 days')), 'appointment_time' => '11:00 AM - 1:00 PM', 'location' => 'MG Road Camp', 'status' => 'Confirmed', 'created_at' => date('Y-m-d H:i:s')],
];
foreach ($appointments as $row) {
    $db->add('donation_appointments', $row);
}

echo "Seeding inventory_transactions...\n";
$transactions = [
    ['trans_date' => date('Y-m-d'), 'trans_type' => 'IN', 'source_type' => 'Donation Camp', 'source_name' => 'City Mall Drive', 'blood_bank_name' => 'Lifeline Blood Bank', 'blood_group' => 'O+', 'component' => 'Whole Blood', 'units' => 6, 'remarks' => 'Walk-in donors processed'],
    ['trans_date' => date('Y-m-d'), 'trans_type' => 'OUT', 'source_type' => 'Hospital Request', 'source_name' => 'Apollo Ward', 'blood_bank_name' => 'Lifeline Blood Bank', 'blood_group' => 'A+', 'component' => 'Packed RBC', 'units' => 3, 'remarks' => 'Urgent surgery support'],
    ['trans_date' => date('Y-m-d', strtotime('-1 day')), 'trans_type' => 'IN', 'source_type' => 'Donation Camp', 'source_name' => 'Tech Park Camp', 'blood_bank_name' => 'North Care Blood Center', 'blood_group' => 'B+', 'component' => 'Plasma', 'units' => 4, 'remarks' => 'Scheduled camp collection'],
    ['trans_date' => date('Y-m-d', strtotime('-1 day')), 'trans_type' => 'OUT', 'source_type' => 'Hospital Request', 'source_name' => 'MediCare ICU', 'blood_bank_name' => 'Metro Health Blood Unit', 'blood_group' => 'O+', 'component' => 'Platelets', 'units' => 2, 'remarks' => 'Platelet replacement'],
    ['trans_date' => date('Y-m-d', strtotime('-2 days')), 'trans_type' => 'IN', 'source_type' => 'Donation Camp', 'source_name' => 'University Drive', 'blood_bank_name' => 'Central City Blood Bank', 'blood_group' => 'A-', 'component' => 'Whole Blood', 'units' => 5, 'remarks' => 'Student donor drive'],
    ['trans_date' => date('Y-m-d', strtotime('-2 days')), 'trans_type' => 'OUT', 'source_type' => 'Hospital Request', 'source_name' => 'St. Mary Hospital', 'blood_bank_name' => 'North Care Blood Center', 'blood_group' => 'AB-', 'component' => 'Packed RBC', 'units' => 1, 'remarks' => 'Rare group emergency'],
    ['trans_date' => date('Y-m-d', strtotime('-3 days')), 'trans_type' => 'IN', 'source_type' => 'Inter-bank Transfer', 'source_name' => 'Regional Reserve', 'blood_bank_name' => 'Metro Health Blood Unit', 'blood_group' => 'B+', 'component' => 'Plasma', 'units' => 3, 'remarks' => 'Stock balancing transfer'],
    ['trans_date' => date('Y-m-d', strtotime('-3 days')), 'trans_type' => 'OUT', 'source_type' => 'Hospital Request', 'source_name' => 'City Trauma Center', 'blood_bank_name' => 'Lifeline Blood Bank', 'blood_group' => 'O-', 'component' => 'Whole Blood', 'units' => 2, 'remarks' => 'Trauma support'],
];
foreach ($transactions as $row) {
    $db->add('inventory_transactions', $row);
}

echo "Done. Collections created: blood_stock, donors, blood_requests, donation_appointments, inventory_transactions.\n";
echo "(blood_ledger and contact_messages start empty -- they fill up as the app is used.)\n";
