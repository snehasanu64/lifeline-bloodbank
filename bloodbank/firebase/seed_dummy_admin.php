<?php
/**
 * Adds dummy data so the admin panel (Donors, Blood Requests, Appointments,
 * Inventory movements, Dashboard, Analytics) has something to show.
 *
 * Run (Command Prompt):
 *   cd D:\xampp\htdocs\project_firebase\bloodbank
 *   D:\xampp\php\php.exe firebase\seed_dummy_admin.php
 *
 * Every dummy document has an ID starting with "dummy_" and the donors use
 * @example.com e-mail addresses, so they are easy to spot and delete later.
 * Safe to run more than once: fixed IDs mean the same documents are simply
 * overwritten, never duplicated. Real data is never touched.
 *
 * All dummy donors can log in on donor.php with password:  Donor@123
 * Dates are relative to today, so the data always looks current.
 */

require_once __DIR__ . '/Firestore.php';
$db = new Firestore(__DIR__ . '/../../service-account.json');

$d = fn(int $days) => date('Y-m-d', strtotime(($days >= 0 ? "+$days" : "$days") . ' days'));
$dt = fn(int $days) => date('Y-m-d H:i:s', strtotime(($days >= 0 ? "+$days" : "$days") . ' days 10:30'));
$passwordHash = password_hash('Donor@123', PASSWORD_DEFAULT);

// ---------------------------------------------------------------- donors
$donors = [
    ['dummy_donor_1',  'arun_kumar',   'arun.kumar@example.com',   '9845012301', 'O+',  28, 72, -120, 'Jayanagar, Bangalore'],
    ['dummy_donor_2',  'bhavana_rao',  'bhavana.rao@example.com',  '9845012302', 'A+',  25, 58, -95,  'Indiranagar, Bangalore'],
    ['dummy_donor_3',  'chetan_gowda', 'chetan.gowda@example.com', '9845012303', 'B+',  34, 80, -200, 'Whitefield, Bangalore'],
    ['dummy_donor_4',  'divya_shetty', 'divya.shetty@example.com', '9845012304', 'AB+', 30, 61, -40,  'Malleshwaram, Bangalore'],
    ['dummy_donor_5',  'eshwar_naik',  'eshwar.naik@example.com',  '9845012305', 'O-',  41, 78, -150, 'Banashankari, Bangalore'],
    ['dummy_donor_6',  'farah_khan',   'farah.khan@example.com',   '9845012306', 'A-',  27, 55, -110, 'Shivajinagar, Bangalore'],
    ['dummy_donor_7',  'girish_babu',  'girish.babu@example.com',  '9845012307', 'B-',  36, 70, -75,  'Yelahanka, Bangalore'],
    ['dummy_donor_8',  'harsha_vardhan','harsha.vardhan@example.com','9845012308','O+', 23, 64, -180, 'BTM Layout, Bangalore'],
    ['dummy_donor_9',  'indu_prakash', 'indu.prakash@example.com', '9845012309', 'AB-', 32, 59, -130, 'Rajajinagar, Bangalore'],
    ['dummy_donor_10', 'jagadish_m',   'jagadish.m@example.com',   '9845012310', 'A+',  45, 83, -60,  'Electronic City, Bangalore'],
];
foreach ($donors as $i => [$id, $user, $email, $phone, $bg, $age, $weight, $lastDays, $address]) {
    $db->set('donors', $id, [
        'username' => $user, 'email' => $email, 'password' => $passwordHash, 'phone' => $phone,
        'bloodgroup' => $bg, 'age' => $age, 'weight' => $weight,
        'lastdonation' => $d($lastDays), 'address' => $address,
        'created_at' => $dt(-90 + $i * 5),
    ]);
}
echo "Donors: " . count($donors) . "\n";

// -------------------------------------------------------------- requests
$requests = [
    // name, phone, email, patient, hospital, group, units, urgency, area, message, status, days ago
    ['Rajesh Menon',  '9900112201', 'rajesh.menon@example.com',  'Sunita Menon',  'Apollo Hospitals',   'O+',  3, 'Within 24 hours',        'Bannerghatta Road', 'Planned surgery',           'Pending',   0],
    ['Seema Pillai',  '9900112202', 'seema.pillai@example.com',  'Arvind Pillai', 'Fortis Hospital',    'A+',  2, 'Within 12 hours',        'Cunningham Road',   'Post-operative care',       'Pending',   1],
    ['Tarun Bhat',    '9900112203', 'tarun.bhat@example.com',    'Kiran Bhat',    'Victoria Hospital Blood Bank', 'B+', 4, 'Immediately / Emergency', 'KR Market', 'Road accident',        'Pending',   1],
    ['Uma Devi',      '9900112204', 'uma.devi@example.com',      'Uma Devi',      'LifeCare Blood Bank','AB+', 1, 'Within 24 hours',        'Jayanagar',         'Anaemia',                   'Fulfilled', 3],
    ['Vinay Hegde',   '9900112205', 'vinay.hegde@example.com',   'Shanta Hegde',  'Narayana Blood Centre','O-', 2, 'Within 12 hours',       'Bommasandra',       'Cardiac surgery',           'Fulfilled', 4],
    ['Wasim Akram',   '9900112206', 'wasim.akram@example.com',   'Zubeda Akram',  'Red Cross Blood Bank','A-', 1, 'Within 24 hours',        'Shivajinagar',      'Delivery complications',    'Fulfilled', 6],
    ['Yamini Rao',    '9900112207', 'yamini.rao@example.com',    'Rohan Rao',     'Apollo Hospitals',   'B-',  2, 'Immediately / Emergency','Sarjapur',          'Dengue - low platelets',    'Fulfilled', 8],
    ['Zaheer Ahmed',  '9900112208', 'zaheer.ahmed@example.com',  'Nasreen Ahmed', 'Fortis Hospital',    'O+',  3, 'Within 24 hours',        'Richmond Road',     'Patient shifted elsewhere', 'Cancelled', 9],
    ['Anand Kulkarni','9900112209', 'anand.kulkarni@example.com','Meera Kulkarni','LifeCare Blood Bank','AB-', 1, 'Within 12 hours',        'Malleshwaram',      'Plasma needed',             'Pending',   2],
    ['Bindu Nair',    '9900112210', 'bindu.nair@example.com',    'Gopal Nair',    'Narayana Blood Centre','A+', 2, 'Within 24 hours',       'Hosur Road',        'Kidney dialysis',           'Fulfilled', 12],
];
foreach ($requests as $i => [$name, $phone, $email, $patient, $hospital, $bg, $units, $urgency, $area, $msg, $status, $ago]) {
    $db->set('blood_requests', 'dummy_request_' . ($i + 1), [
        'requester_name' => $name, 'phone' => $phone, 'email' => $email, 'patient_name' => $patient,
        'hospital_name' => $hospital, 'blood_group' => $bg, 'units_needed' => $units,
        'urgency_level' => $urgency, 'location_area' => $area, 'message' => $msg,
        'request_status' => $status, 'created_at' => $dt(-$ago),
    ]);
}
echo "Blood requests: " . count($requests) . "\n";

// ---------------------------------------------------------- appointments
$office = 'Lifeline Blood Bank Office, HAL 3rd Stage';
$mg = 'MG Road Camp - Brigade Road, Bangalore';
$uni = 'University Campus Camp - Bangalore University Auditorium';
$ec = 'Electronic City Camp - Infosys Campus';
$slots = ['9:00 AM - 11:00 AM', '11:00 AM - 1:00 PM', '1:00 PM - 3:00 PM', '3:00 PM - 5:00 PM'];
$appointments = [
    // donor index (1-based), day offset, slot index, location, status
    [1,  -20, 0, $office, 'Completed'],
    [2,  -15, 1, $mg,     'Completed'],
    [3,  -12, 2, $uni,    'Completed'],
    [5,  -9,  0, $ec,     'Completed'],
    [6,  -6,  3, $office, 'Cancelled'],
    [7,  -3,  1, $mg,     'Completed'],
    [8,  1,   0, $office, 'Confirmed'],
    [9,  2,   2, $uni,    'Confirmed'],
    [10, 3,   1, $ec,     'Pending'],
    [4,  5,   3, $mg,     'Pending'],
    [1,  8,   0, $office, 'Pending'],
    [2,  10,  2, $uni,    'Confirmed'],
];
foreach ($appointments as $i => [$n, $off, $slot, $loc, $status]) {
    $donor = $donors[$n - 1];
    $db->set('donation_appointments', 'dummy_appointment_' . ($i + 1), [
        'donor_email' => $donor[2], 'donor_name' => $donor[1], 'bloodgroup' => $donor[4],
        'appointment_date' => $d($off), 'appointment_time' => $slots[$slot], 'location' => $loc,
        'status' => $status, 'created_at' => $dt($off - 4),
    ]);
}
echo "Appointments: " . count($appointments) . "\n";

// ------------------------------------------------ inventory movements
$movements = [
    // days ago, IN/OUT, source type, source name, bank, group, component, units, remarks
    [0,  'IN',  'Camp',     'MG Road Camp',         'LifeCare Blood Bank',          'O+',  'Whole Blood', 8,  'Morning collection'],
    [0,  'OUT', 'Hospital', 'Apollo Hospitals',     'LifeCare Blood Bank',          'A+',  'RBC',         3,  'Scheduled surgery'],
    [1,  'IN',  'Donor',    'Walk-in donors',       'Victoria Hospital Blood Bank', 'B+',  'Whole Blood', 5,  'Daily walk-in donors'],
    [1,  'OUT', 'Patient',  'Kiran Bhat',           'Victoria Hospital Blood Bank', 'B+',  'Whole Blood', 2,  'Accident case'],
    [2,  'IN',  'Camp',     'University Campus Camp','Red Cross Blood Bank',        'AB+', 'Whole Blood', 9,  'Student drive'],
    [2,  'OUT', 'Hospital', 'Fortis Hospital',      'Red Cross Blood Bank',         'O+',  'Platelets',   2,  'Platelet replacement'],
    [3,  'IN',  'Donor',    'Regular donors',       'Narayana Blood Centre',        'A-',  'Plasma',      4,  'Plasma donation'],
    [4,  'OUT', 'Patient',  'Zubeda Akram',         'Red Cross Blood Bank',         'A-',  'Whole Blood', 1,  'Delivery complications'],
    [5,  'IN',  'Camp',     'Electronic City Camp', 'Narayana Blood Centre',        'O-',  'Whole Blood', 6,  'Corporate camp'],
    [6,  'OUT', 'Hospital', 'Narayana Health',      'Narayana Blood Centre',        'O-',  'RBC',         2,  'Cardiac surgery'],
    [8,  'IN',  'Donor',    'Weekend donors',       'LifeCare Blood Bank',          'B-',  'Whole Blood', 3,  'Rare group donors'],
    [9,  'OUT', 'Hospital', 'Victoria Hospital',    'Victoria Hospital Blood Bank', 'AB-', 'Plasma',      1,  'Burn unit'],
    [11, 'IN',  'Camp',     'MG Road Camp',         'LifeCare Blood Bank',          'A+',  'Whole Blood', 7,  'Monthly camp'],
    [13, 'OUT', 'Patient',  'Rohan Rao',            'Red Cross Blood Bank',         'B-',  'Platelets',   2,  'Dengue treatment'],
];
foreach ($movements as $i => [$ago, $type, $srcType, $srcName, $bank, $bg, $comp, $units, $remarks]) {
    $db->set('inventory_transactions', 'dummy_movement_' . ($i + 1), [
        'trans_date' => $d(-$ago), 'trans_type' => $type, 'source_type' => $srcType, 'source_name' => $srcName,
        'blood_bank_name' => $bank, 'blood_group' => $bg, 'component' => $comp, 'units' => $units, 'remarks' => $remarks,
    ]);
}
echo "Inventory movements: " . count($movements) . "\n";
echo "Done. Dummy donors log in with password: Donor@123\n";
