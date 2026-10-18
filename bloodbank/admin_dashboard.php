<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

include('db_connect.php');

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Fetch statistics.
// Firestore has no COUNT/GROUP BY/SUM, so each collection is pulled once
// with getAll() and everything below is aggregated here in PHP.
require_once __DIR__ . '/inventory_helper.php';
refresh_stock_dates($db); // keeps batch dates current (runs once a day)

$allDonors = [];
$allRequests = [];
$allAppointmentsRaw = [];
$allStock = [];

try {
    $allDonors = $db->getAll('donors');
    $allRequests = $db->getAll('blood_requests');
    $allAppointmentsRaw = $db->getAll('donation_appointments');
    $allStock = $db->getAll('blood_stock');
} catch (Exception $e) {
    error_log("Dashboard data fetch failed: " . $e->getMessage());
}

$totalDonors = count($allDonors);
$pendingRequests = count($allRequests);
$totalCamps = count($allAppointmentsRaw);

// Simulate blood units (in real system, track this in database)
$totalBloodUnits = intval($totalDonors * 0.45);

// Blood group distribution among donors
$bloodGroupData = array('A+' => 0, 'A-' => 0, 'B+' => 0, 'B-' => 0, 'AB+' => 0, 'AB-' => 0, 'O+' => 0, 'O-' => 0);
foreach ($allDonors as $donorRow) {
    $bg = $donorRow['bloodgroup'] ?? '';
    if (isset($bloodGroupData[$bg])) {
        $bloodGroupData[$bg]++;
    }
}

// Recent appointments (sorted by created_at desc, top 10)
usort($allAppointmentsRaw, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
$appointments = array_slice($allAppointmentsRaw, 0, 10);

// Request status breakdown
$requestStatus = array('Pending' => 0, 'Fulfilled' => 0, 'Cancelled' => 0);
foreach ($allRequests as $reqRow) {
    $status = $reqRow['status'] ?? ($reqRow['request_status'] ?? '');
    if (isset($requestStatus[$status])) {
        $requestStatus[$status]++;
    }
}

// Appointments scheduled today
$today = date('Y-m-d');
$scheduledToday = 0;
foreach ($allAppointmentsRaw as $apt) {
    if (($apt['appointment_date'] ?? '') === $today) {
        $scheduledToday++;
    }
}

// Inventory totals by blood group
$inventoryLevels = array('O+' => 0, 'A+' => 0, 'B+' => 0, 'AB+' => 0, 'O-' => 0, 'A-' => 0, 'B-' => 0, 'AB-' => 0);
foreach ($allStock as $stockRow) {
    $group = $stockRow['blood_group'] ?? '';
    if (isset($inventoryLevels[$group])) {
        $inventoryLevels[$group] += (int)($stockRow['units_available'] ?? 0);
    }
}

$inventoryPeak = max(1, max($inventoryLevels));
$totalInventoryUnits = array_sum($inventoryLevels);
$urgentRequests = $requestStatus['Pending'];

$criticalGroup = '';
$criticalUnits = 0;
foreach ($inventoryLevels as $group => $units) {
    if ($units > 0 && ($criticalUnits === 0 || $units < $criticalUnits)) {
        $criticalUnits = $units;
        $criticalGroup = $group;
    }
}

// Item nearest to expiry
$nearExpiryItem = null;
$stockWithExpiry = array_filter($allStock, fn($row) => !empty($row['expiry_date']));
if (!empty($stockWithExpiry)) {
    usort($stockWithExpiry, fn($a, $b) => strcmp($a['expiry_date'], $b['expiry_date']));
    $nearExpiryItem = $stockWithExpiry[0];
}

// Low stock (fewer than 5 units in one batch) and batches expiring within 3 days.
$lowStockThreshold = 5;
$expiringSoonDays = 3;
$lowStockRows = [];
$expiringSoonRows = [];
$expiredCount = 0;
$soonLimit = date('Y-m-d', strtotime("+{$expiringSoonDays} days"));
foreach ($allStock as $stockRow) {
    $units = (int)($stockRow['units_available'] ?? 0);
    $expiry = $stockRow['expiry_date'] ?? '';
    if ($expiry !== '' && $expiry < $today) {
        $expiredCount++;
        continue; // expired batches are not counted as usable stock
    }
    if ($units < $lowStockThreshold) {
        $lowStockRows[] = $stockRow;
    }
    if ($expiry !== '' && $expiry <= $soonLimit) {
        $expiringSoonRows[] = $stockRow;
    }
}
usort($lowStockRows, fn($a, $b) => (int)($a['units_available'] ?? 0) <=> (int)($b['units_available'] ?? 0));
usort($expiringSoonRows, fn($a, $b) => strcmp($a['expiry_date'], $b['expiry_date']));

$recentActivities = [];
foreach (array_slice($appointments, 0, 3) as $apt) {
    $recentActivities[] = [
        'title' => 'Scheduled Appointment: ' . $apt['donor_name'],
        'meta' => $apt['bloodgroup'] . ' - ' . $apt['appointment_time'],
        'badge' => strtoupper($apt['status'])
    ];
}

usort($allRequests, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
foreach (array_slice($allRequests, 0, 2) as $req) {
    $recentActivities[] = [
        'title' => 'Request Logged: ' . $req['requester_name'],
        'meta' => ($req['units_needed'] ?? '') . ' units ' . ($req['blood_group'] ?? ''),
        'badge' => strtoupper($req['request_status'] ?? '')
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Lifeline Blood Bank</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f9f2f5;
            line-height: 1.5;
            color: #1f2a33;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, #7a1538 0%, #5e0f2a 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 10px;
        }

        .sidebar-header img {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 2px solid rgba(255, 255, 255, 0.35);
            background: #fff;
            padding: 5px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu a {
            display: block;
            color: #ffdfe8;
            padding: 12px 20px;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.08);
            border-left-color: #ffd5e3;
            color: white;
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.14);
            border-left-color: #ffd5e3;
            color: white;
        }

        .sidebar-logout {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 0 20px;
        }

        .quick-action {
            display: block;
            width: 100%;
            text-align: center;
            border: none;
            background: #b11d4a;
            color: white;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            margin-bottom: 10px;
        }

        .logout-btn {
            width: 100%;
            border: none;
            background: #b11d4a;
            color: white;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .main-content {
            margin-left: 240px;
            padding: 24px;
            width: calc(100% - 240px);
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            gap: 16px;
        }

        .topbar h1 {
            font-size: 44px;
            line-height: 1.05;
            color: #1d2733;
        }

        .topbar p {
            color: #6d7b88;
            font-weight: 600;
            margin-top: 4px;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-box {
            background: #fff;
            border: 1px solid #e8dde1;
            border-radius: 8px;
            min-width: 280px;
            padding: 10px 12px;
            color: #98a4b1;
            font-weight: 600;
        }

        .action-pill {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            border: 1px solid #e8dde1;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #5d6b78;
            font-size: 16px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 16px;
        }

        .kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }

        .kpi-card {
            background: #fff;
            border: 1px solid #ece0e4;
            border-radius: 10px;
            padding: 14px;
        }

        .kpi-card h4 {
            color: #7f8b97;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
        }

        .kpi-card p {
            font-size: 42px;
            font-weight: 800;
            color: #1d2733;
            line-height: 1;
        }

        .kpi-card .sub {
            margin-top: 6px;
            color: #93a0ad;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .panel {
            background: #fff;
            border: 1px solid #ece0e4;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 14px;
        }

        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .panel-head h3 {
            font-size: 30px;
            color: #1f2a33;
        }

        .panel-link {
            color: #c22044;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            text-transform: uppercase;
        }

        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px 16px;
        }

        .inventory-item .meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-weight: 700;
            color: #2b3742;
        }

        .inventory-item .units {
            color: #7d8a96;
            font-size: 12px;
        }

        .bar-track {
            background: #f1e5ea;
            border-radius: 999px;
            overflow: hidden;
            height: 7px;
        }

        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #c31731, #de3f52);
        }

        .activity-list {
            display: grid;
            gap: 10px;
        }

        .activity-item {
            border: 1px solid #eceff3;
            border-radius: 10px;
            padding: 10px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .activity-item strong {
            color: #2a3641;
            font-size: 15px;
            display: block;
        }

        .activity-item span {
            color: #7e8b97;
            font-size: 13px;
        }

        .status-chip {
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 999px;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .status-chip.pending,
        .status-chip.upcoming {
            background: #ffe8cc;
            color: #965b00;
        }

        .status-chip.fulfilled,
        .status-chip.confirmed,
        .status-chip.completed {
            background: #d6f2ff;
            color: #0d6f93;
        }

        .status-chip.cancelled {
            background: #f8d7da;
            color: #7c1f2f;
        }

        .alert-block {
            border: 1px solid #ece0e4;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
            background: #fff;
        }

        .alert-block h4 {
            font-size: 11px;
            color: #b11d4a;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 6px;
        }

        .alert-block p {
            font-size: 24px;
            font-weight: 800;
            color: #222f3a;
            line-height: 1.1;
            margin-bottom: 4px;
        }

        .alert-block small {
            color: #758390;
            font-size: 13px;
        }

        .alert-btn {
            margin-top: 10px;
            display: inline-block;
            padding: 8px 11px;
            border-radius: 6px;
            background: #b11d4a;
            color: #fff;
            font-weight: 700;
            text-decoration: none;
            font-size: 12px;
            text-transform: uppercase;
        }

        .drive-box {
            background: #fff;
            border: 1px solid #ece0e4;
            border-radius: 10px;
            padding: 12px;
        }

        .drive-box h4 {
            font-size: 25px;
            margin-bottom: 4px;
            color: #202b37;
        }

        .drive-box p {
            color: #6f7e8d;
            font-size: 14px;
        }

        @media(max-width: 1100px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .panel-head h3,
            .topbar h1 {
                font-size: 34px;
            }
        }

        @media(max-width: 900px) {
            .kpis {
                grid-template-columns: 1fr;
            }

            .inventory-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media(max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 14px;
            }

            .inventory-grid {
                grid-template-columns: 1fr;
            }

            .search-box {
                min-width: 0;
                width: 100%;
            }

            .top-actions {
                width: 100%;
            }
        }

        /* ===== Redesigned dashboard (dx-) ===== */
        .dx-hero {
            background: linear-gradient(120deg, #7a1538 0%, #a3194a 55%, #c0132d 100%);
            color: #fff;
            border-radius: 20px;
            padding: 26px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            box-shadow: 0 12px 30px rgba(122, 21, 56, 0.25);
            margin-bottom: 20px;
        }
        .dx-hero h1 { font-size: 30px; line-height: 1.15; margin-bottom: 4px; color: #fff; }
        .dx-hero p { opacity: .9; font-size: 15px; }
        .dx-hero-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .dx-search { display: flex; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.35); border-radius: 30px; overflow: hidden; }
        .dx-search input { background: transparent; border: 0; color: #fff; padding: 10px 16px; width: 220px; outline: none; font-size: 14px; }
        .dx-search input::placeholder { color: rgba(255,255,255,.8); }
        .dx-search button { border: 0; background: #fff; color: #7a1538; font-weight: 700; padding: 0 16px; cursor: pointer; }
        .dx-btn { display: inline-block; text-decoration: none; background: #fff; color: #7a1538; font-weight: 700; font-size: 14px; padding: 10px 18px; border-radius: 30px; transition: transform .15s, box-shadow .15s; }
        .dx-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 14px rgba(0,0,0,.2); }
        .dx-btn.ghost { background: transparent; color: #fff; border: 1.5px solid rgba(255,255,255,.7); }

        .dx-alerts { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
        .dx-alert { flex: 1 1 240px; text-decoration: none; display: flex; align-items: center; gap: 12px; padding: 13px 16px; border-radius: 14px; background: #fff; border: 1px solid #eadde2; border-left-width: 6px; color: #2b2f38; transition: transform .15s, box-shadow .15s; }
        .dx-alert:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,.08); }
        .dx-alert strong { display: block; font-size: 15px; }
        .dx-alert span { font-size: 13px; color: #6b7280; }
        .dx-alert .ic { font-size: 22px; }
        .dx-alert.crit { border-left-color: #c0132d; }
        .dx-alert.warn { border-left-color: #d98a00; }
        .dx-alert.good { border-left-color: #1b7f4b; }

        .dx-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .dx-kpi { text-decoration: none; color: inherit; background: #fff; border-radius: 18px; padding: 18px 20px; border: 1px solid #f0e4e8; box-shadow: 0 4px 14px rgba(0,0,0,.04); transition: transform .15s, box-shadow .15s, border-color .15s; display: block; }
        .dx-kpi:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(122,21,56,.14); border-color: #d9aebb; }
        .dx-kpi .top { display: flex; justify-content: space-between; align-items: center; }
        .dx-kpi .label { font-size: 13px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; font-weight: 600; }
        .dx-kpi .icon { width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; font-size: 19px; background: #fbeaf0; }
        .dx-kpi .value { font-size: 36px; font-weight: 800; color: #1d2733; line-height: 1.1; margin-top: 8px; }
        .dx-kpi .sub { font-size: 13px; color: #6b7280; margin-top: 4px; }
        .dx-kpi .go { font-size: 12px; color: #7a1538; font-weight: 700; margin-top: 8px; opacity: 0; transition: opacity .15s; }
        .dx-kpi:hover .go { opacity: 1; }

        .dx-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .dx-panel { background: #fff; border-radius: 18px; padding: 20px 22px; border: 1px solid #f0e4e8; box-shadow: 0 4px 14px rgba(0,0,0,.04); min-width: 0; }
        .dx-panel.wide { grid-column: 1 / -1; }
        .dx-head { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; margin-bottom: 14px; }
        .dx-head h3 { font-size: 18px; color: #1d2733; }
        .dx-head a { font-size: 13px; font-weight: 700; color: #7a1538; text-decoration: none; white-space: nowrap; }
        .dx-head a:hover { text-decoration: underline; }

        .dx-bars { display: grid; gap: 8px; }
        .dx-bar { display: grid; grid-template-columns: 44px 1fr 62px; align-items: center; gap: 10px; text-decoration: none; color: inherit; padding: 4px 6px; border-radius: 10px; transition: background .15s; }
        .dx-bar:hover { background: #fbf3f6; }
        .dx-bar .name { font-weight: 700; font-size: 14px; color: #1d2733; }
        .dx-bar .track { height: 12px; background: #f3e9ed; border-radius: 6px; overflow: hidden; }
        .dx-bar .fill { height: 100%; border-radius: 6px; background: linear-gradient(90deg, #b8325e, #7a1538); }
        .dx-bar.low .fill { background: #c0132d; }
        .dx-bar .num { text-align: right; font-size: 14px; font-weight: 700; color: #1d2733; }
        .dx-bar .tag { font-size: 11px; color: #c0132d; font-weight: 700; display: block; text-align: right; }
        .dx-legend { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 12px; font-size: 13px; color: #4b5563; }
        .dx-legend i { display: inline-block; width: 12px; height: 12px; border-radius: 3px; margin-right: 6px; vertical-align: -1px; }

        .dx-seg { display: flex; height: 22px; border-radius: 11px; overflow: hidden; gap: 2px; background: #fff; }
        .dx-seg a { display: block; min-width: 6px; transition: filter .15s; }
        .dx-seg a:hover { filter: brightness(1.12); }
        .dx-stat-rows { display: grid; gap: 8px; margin-top: 14px; }
        .dx-stat-rows a { display: flex; justify-content: space-between; align-items: center; text-decoration: none; color: #1d2733; padding: 9px 12px; border-radius: 10px; background: #faf6f8; font-size: 14px; transition: background .15s; }
        .dx-stat-rows a:hover { background: #f4e6ec; }
        .dx-stat-rows b { font-size: 16px; }

        .dx-list { display: grid; gap: 4px; }
        .dx-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 11px 12px; border-radius: 12px; text-decoration: none; color: inherit; transition: background .15s; }
        .dx-row:hover { background: #fbf3f6; }
        .dx-row strong { display: block; font-size: 15px; color: #1d2733; }
        .dx-row span.meta { font-size: 13px; color: #6b7280; }
        .dx-chip { font-size: 12px; font-weight: 700; padding: 4px 11px; border-radius: 20px; white-space: nowrap; }
        .dx-chip.pending { background: #fff3d6; color: #8a5a00; }
        .dx-chip.confirmed { background: #e0ecff; color: #1d4fa3; }
        .dx-chip.fulfilled, .dx-chip.completed { background: #dcf3e6; color: #17653c; }
        .dx-chip.cancelled { background: #eceef1; color: #4b5563; }
        .dx-empty { color: #6b7280; padding: 14px 6px; font-size: 14px; }

        .dx-camp { display: flex; gap: 14px; align-items: center; text-decoration: none; color: inherit; padding: 11px 12px; border-radius: 12px; transition: background .15s; }
        .dx-camp:hover { background: #fbf3f6; }
        .dx-date { flex: 0 0 56px; text-align: center; background: #7a1538; color: #fff; border-radius: 12px; padding: 6px 0; line-height: 1.1; }
        .dx-date b { display: block; font-size: 22px; }
        .dx-date small { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; }

        .dx-two { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .dx-mini { text-decoration: none; color: inherit; background: #faf6f8; border-radius: 14px; padding: 14px 16px; display: block; transition: background .15s, transform .15s; }
        .dx-mini:hover { background: #f4e6ec; transform: translateY(-2px); }
        .dx-mini b { font-size: 28px; display: block; color: #1d2733; }
        .dx-mini small { color: #6b7280; font-size: 13px; }
        .dx-table summary { cursor: pointer; font-size: 13px; color: #7a1538; font-weight: 700; margin-top: 12px; }
        .dx-table table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 13px; }
        .dx-table td, .dx-table th { padding: 5px 8px; border-bottom: 1px solid #f0e4e8; text-align: left; }

        @media (max-width: 900px) {
            .dx-hero h1 { font-size: 24px; }
            .dx-search input { width: 150px; }
            .dx-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <div class="sidebar-header">
            <img decoding="async" src="assets/img/logo.jpg" alt="Lifeline Blood Bank Logo" border="0">
            <h2>Lifeline Blood Bank</h2>
            <p>Blood Bank Admin</p>
        </div>

        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php" class="active">📊 Dashboard</a></li>
            <li><a href="admin_donors.php">👥 Donors</a></li>
            <li><a href="admin_requests.php">🩸 Blood Requests</a></li>
            <li><a href="admin_appointments.php">📅 Appointments</a></li>
            <li><a href="admin_camps.php">🏥 Camps</a></li>
            <li><a href="admin_inventory.php">📦 Inventory</a></li>
            <li><a href="admin_blood_ledger.php">🧾 Blood Ledger</a></li>
            <li><a href="admin_analytics.php">📈 Analytics</a></li>
            <li><a href="admin_change_password.php">🔑 Change Password</a></li>
        </ul>

        <div class="sidebar-logout">
            <a href="admin_blood_ledger.php" class="quick-action">+ New Donation</a>
            <form method="post" style="display:inline;">
                <button type="submit" name="logout" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>

    <div class="main-content">
<?php
require_once __DIR__ . '/camps_helper.php';
function dx($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ---- extra figures for the redesigned dashboard (all from Firestore) ----
$liveLevels = array('O+' => 0, 'A+' => 0, 'B+' => 0, 'AB+' => 0, 'O-' => 0, 'A-' => 0, 'B-' => 0, 'AB-' => 0);
foreach ($allStock as $s) {
    $exp = $s['expiry_date'] ?? '';
    if ($exp !== '' && $exp < $today) { continue; }          // expired stock is not usable
    $g = $s['blood_group'] ?? '';
    if (isset($liveLevels[$g])) { $liveLevels[$g] += (int)($s['units_available'] ?? 0); }
}
$liveTotal = array_sum($liveLevels);
$livePeak = max(1, max($liveLevels));
$lowGroupLimit = (int)round($liveTotal / 8 * 0.6);           // below 60% of the average group = low

$eligibleDonors = 0;
foreach ($allDonors as $dn) {
    $ld = $dn['lastdonation'] ?? '';
    if ($ld === '' || date('Y-m-d', strtotime($ld . ' +90 days')) <= $today) { $eligibleDonors++; }
}

$upcomingApts = array_values(array_filter($allAppointmentsRaw, fn($a) =>
    ($a['appointment_date'] ?? '') >= $today && in_array($a['status'] ?? '', ['Pending', 'Confirmed'], true)));
usort($upcomingApts, fn($a, $b) => [$a['appointment_date'], $a['appointment_time'] ?? ''] <=> [$b['appointment_date'], $b['appointment_time'] ?? '']);
$upcomingApts = array_slice($upcomingApts, 0, 5);

$latestRequests = array_slice($allRequests, 0, 5);            // already newest first
$emergencyPending = 0;
foreach ($allRequests as $r) {
    if (($r['request_status'] ?? '') === 'Pending' && stripos($r['urgency_level'] ?? '', 'Immediately') === 0) { $emergencyPending++; }
}

$dashCamps = array_slice(load_upcoming_camps($db), 0, 3);

$ledgerAll = [];
try { $ledgerAll = $db->getAll('blood_ledger'); } catch (Exception $e) { error_log('Dashboard ledger: ' . $e->getMessage()); }
$ledgerIn = 0; $ledgerOut = 0; $since = date('Y-m-d', strtotime('-30 days'));
foreach ($ledgerAll as $le) {
    if (($le['entry_date'] ?? '') < $since) { continue; }
    if (($le['transaction_type'] ?? '') === 'TAKEN') { $ledgerIn += (int)($le['units'] ?? 0); }
    else { $ledgerOut += (int)($le['units'] ?? 0); }
}

$reqTotal = max(1, array_sum($requestStatus));
$statusColor = ['Pending' => '#d98a00', 'Fulfilled' => '#1b7f4b', 'Cancelled' => '#9aa1ab'];
$maxDonorGroup = max(1, max($bloodGroupData));
$adminName = $_SESSION['admin_name'] ?? 'Administrator';
$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>

        <!-- HERO -->
        <div class="dx-hero">
            <div>
                <h1><?php echo $greeting . ', ' . dx($adminName); ?> 👋</h1>
                <p><?php echo date('l, d F Y'); ?> &middot; Updated <?php echo date('h:i A'); ?></p>
            </div>
            <div class="dx-hero-actions">
                <form class="dx-search" action="admin_donors.php" method="get">
                    <input type="text" name="search" placeholder="Search donors by name, email, phone">
                    <button type="submit">Search</button>
                </form>
                <a class="dx-btn" href="admin_camps.php">+ Add Camp</a>
                <a class="dx-btn ghost" href="admin_blood_ledger.php">+ Ledger Entry</a>
            </div>
        </div>

        <!-- ATTENTION STRIP (each one opens the page that fixes it) -->
        <div class="dx-alerts">
            <a class="dx-alert <?php echo $emergencyPending > 0 ? 'crit' : ($requestStatus['Pending'] > 0 ? 'warn' : 'good'); ?>" href="admin_requests.php?status=Pending">
                <span class="ic">🩸</span>
                <div><strong><?php echo (int)$requestStatus['Pending']; ?> pending request<?php echo $requestStatus['Pending'] == 1 ? '' : 's'; ?></strong>
                <span><?php echo $emergencyPending > 0 ? $emergencyPending . ' marked emergency - review now' : 'Review and fulfil'; ?></span></div>
            </a>
            <a class="dx-alert <?php echo count($lowStockRows) > 0 ? 'warn' : 'good'; ?>" href="admin_inventory.php?order=low-first">
                <span class="ic">📦</span>
                <div><strong><?php echo count($lowStockRows); ?> low-stock batch<?php echo count($lowStockRows) == 1 ? '' : 'es'; ?></strong>
                <span>Under <?php echo (int)$lowStockThreshold; ?> units each</span></div>
            </a>
            <a class="dx-alert <?php echo count($expiringSoonRows) > 0 ? 'warn' : 'good'; ?>" href="admin_inventory.php">
                <span class="ic">⏳</span>
                <div><strong><?php echo count($expiringSoonRows); ?> batch<?php echo count($expiringSoonRows) == 1 ? '' : 'es'; ?> expiring in <?php echo (int)$expiringSoonDays; ?> days</strong>
                <span>Use or reallocate soon</span></div>
            </a>
        </div>

        <!-- KPI CARDS (all clickable) -->
        <div class="dx-kpis">
            <a class="dx-kpi" href="admin_donors.php">
                <div class="top"><span class="label">Donors</span><span class="icon">👥</span></div>
                <div class="value"><?php echo (int)$totalDonors; ?></div>
                <div class="sub"><?php echo (int)$eligibleDonors; ?> eligible to donate now</div>
                <div class="go">View donors &rarr;</div>
            </a>
            <a class="dx-kpi" href="admin_inventory.php">
                <div class="top"><span class="label">Blood Units</span><span class="icon">🩸</span></div>
                <div class="value"><?php echo (int)$liveTotal; ?></div>
                <div class="sub">usable units in stock</div>
                <div class="go">Open inventory &rarr;</div>
            </a>
            <a class="dx-kpi" href="admin_requests.php?status=Pending">
                <div class="top"><span class="label">Pending Requests</span><span class="icon">🚨</span></div>
                <div class="value"><?php echo (int)$requestStatus['Pending']; ?></div>
                <div class="sub">of <?php echo (int)array_sum($requestStatus); ?> total requests</div>
                <div class="go">Handle requests &rarr;</div>
            </a>
            <a class="dx-kpi" href="admin_appointments.php">
                <div class="top"><span class="label">Today</span><span class="icon">📅</span></div>
                <div class="value"><?php echo (int)$scheduledToday; ?></div>
                <div class="sub">appointment<?php echo $scheduledToday == 1 ? '' : 's'; ?> scheduled today</div>
                <div class="go">See appointments &rarr;</div>
            </a>
            <a class="dx-kpi" href="admin_camps.php">
                <div class="top"><span class="label">Upcoming Camps</span><span class="icon">🏥</span></div>
                <div class="value"><?php echo count(load_upcoming_camps($db)); ?></div>
                <div class="sub">active camps scheduled</div>
                <div class="go">Manage camps &rarr;</div>
            </a>
            <a class="dx-kpi" href="admin_blood_ledger.php">
                <div class="top"><span class="label">Ledger (30 days)</span><span class="icon">🧾</span></div>
                <div class="value"><?php echo (int)$ledgerIn; ?><small style="font-size:16px;color:#6b7280;"> in</small> / <?php echo (int)$ledgerOut; ?><small style="font-size:16px;color:#6b7280;"> out</small></div>
                <div class="sub"><?php echo count($ledgerAll); ?> ledger entries in total</div>
                <div class="go">Open ledger &rarr;</div>
            </a>
        </div>

        <div class="dx-grid">
            <!-- STOCK BY GROUP -->
            <div class="dx-panel">
                <div class="dx-head"><h3>Stock by Blood Group</h3><a href="admin_inventory.php">Full inventory &rarr;</a></div>
                <div class="dx-bars">
                    <?php foreach ($liveLevels as $grp => $u) :
                        $isLow = $u <= $lowGroupLimit; ?>
                        <a class="dx-bar <?php echo $isLow ? 'low' : ''; ?>" href="admin_inventory.php?group=<?php echo urlencode($grp); ?>" title="<?php echo dx($grp . ': ' . $u . ' usable units'); ?>">
                            <span class="name"><?php echo dx($grp); ?></span>
                            <span class="track"><span class="fill" style="display:block;width:<?php echo (int)round($u / $livePeak * 100); ?>%;"></span></span>
                            <span class="num"><?php echo (int)$u; ?><?php if ($isLow) : ?><span class="tag">LOW</span><?php endif; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="dx-legend"><span><i style="background:#7a1538"></i>Healthy</span><span><i style="background:#c0132d"></i>Low (under <?php echo (int)$lowGroupLimit; ?> units)</span><span>Click a bar to filter inventory</span></div>
                <details class="dx-table"><summary>View as table</summary>
                    <table><tr><th>Group</th><th>Usable units</th></tr>
                    <?php foreach ($liveLevels as $grp => $u) : ?><tr><td><?php echo dx($grp); ?></td><td><?php echo (int)$u; ?></td></tr><?php endforeach; ?></table>
                </details>
            </div>

            <!-- REQUESTS BY STATUS -->
            <div class="dx-panel">
                <div class="dx-head"><h3>Blood Requests</h3><a href="admin_requests.php">All requests &rarr;</a></div>
                <div class="dx-seg" role="img" aria-label="Requests by status">
                    <?php foreach ($requestStatus as $st => $n) : if ($n < 1) { continue; } ?>
                        <a href="admin_requests.php?status=<?php echo urlencode($st); ?>" title="<?php echo dx($st . ': ' . $n); ?>" style="width:<?php echo round($n / $reqTotal * 100, 2); ?>%;background:<?php echo $statusColor[$st]; ?>;"></a>
                    <?php endforeach; ?>
                </div>
                <div class="dx-stat-rows">
                    <?php foreach ($requestStatus as $st => $n) : ?>
                        <a href="admin_requests.php?status=<?php echo urlencode($st); ?>"><span><i style="display:inline-block;width:11px;height:11px;border-radius:3px;margin-right:8px;background:<?php echo $statusColor[$st]; ?>"></i><?php echo dx($st); ?></span><b><?php echo (int)$n; ?></b></a>
                    <?php endforeach; ?>
                </div>
                <div class="dx-two" style="margin-top:14px;">
                    <a class="dx-mini" href="admin_blood_ledger.php?type=TAKEN"><b><?php echo (int)$ledgerIn; ?></b><small>units taken in (30 days)</small></a>
                    <a class="dx-mini" href="admin_blood_ledger.php?type=GIVEN"><b><?php echo (int)$ledgerOut; ?></b><small>units given out (30 days)</small></a>
                </div>
            </div>

            <!-- LATEST REQUESTS -->
            <div class="dx-panel">
                <div class="dx-head"><h3>Latest Requests</h3><a href="admin_requests.php">View all &rarr;</a></div>
                <div class="dx-list">
                    <?php foreach ($latestRequests as $r) : $st = $r['request_status'] ?? 'Pending'; ?>
                        <a class="dx-row" href="admin_requests.php?search=<?php echo urlencode($r['requester_name'] ?? ''); ?>">
                            <div><strong><?php echo dx(($r['requester_name'] ?? '') . ' - ' . ($r['blood_group'] ?? '')); ?></strong>
                            <span class="meta"><?php echo (int)($r['units_needed'] ?? 0); ?> unit(s) &middot; <?php echo dx($r['hospital_name'] ?? ''); ?></span></div>
                            <span class="dx-chip <?php echo dx(strtolower($st)); ?>"><?php echo dx($st); ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$latestRequests) : ?><div class="dx-empty">No requests yet.</div><?php endif; ?>
                </div>
            </div>

            <!-- UPCOMING APPOINTMENTS -->
            <div class="dx-panel">
                <div class="dx-head"><h3>Upcoming Appointments</h3><a href="admin_appointments.php">Manage &rarr;</a></div>
                <div class="dx-list">
                    <?php foreach ($upcomingApts as $a) : $st = $a['status'] ?? 'Pending'; ?>
                        <a class="dx-row" href="admin_appointments.php?status=<?php echo urlencode($st); ?>">
                            <div><strong><?php echo dx(($a['donor_name'] ?? '') . ' - ' . ($a['bloodgroup'] ?? '')); ?></strong>
                            <span class="meta"><?php echo dx(date('d M', strtotime($a['appointment_date']))); ?> &middot; <?php echo dx($a['appointment_time'] ?? ''); ?></span></div>
                            <span class="dx-chip <?php echo dx(strtolower($st)); ?>"><?php echo dx($st); ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$upcomingApts) : ?><div class="dx-empty">No upcoming appointments.</div><?php endif; ?>
                </div>
            </div>

            <!-- CAMPS -->
            <div class="dx-panel">
                <div class="dx-head"><h3>Next Camps</h3><a href="admin_camps.php">Manage camps &rarr;</a></div>
                <div class="dx-list">
                    <?php foreach ($dashCamps as $c) : $ts = strtotime($c['next_date']); ?>
                        <a class="dx-camp" href="admin_camps.php?edit=<?php echo urlencode($c['_id']); ?>">
                            <div class="dx-date"><b><?php echo date('d', $ts); ?></b><small><?php echo date('M', $ts); ?></small></div>
                            <div><strong><?php echo dx($c['name']); ?></strong><br><span class="meta" style="font-size:13px;color:#6b7280;"><?php echo dx(($c['address'] ?? '') . ' - ' . ($c['time'] ?? '')); ?></span></div>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$dashCamps) : ?><div class="dx-empty">No upcoming camps. <a href="admin_camps.php">Add one</a>.</div><?php endif; ?>
                </div>
            </div>

            <!-- DONORS BY GROUP -->
            <div class="dx-panel">
                <div class="dx-head"><h3>Donors by Blood Group</h3><a href="admin_donors.php">All donors &rarr;</a></div>
                <div class="dx-bars">
                    <?php foreach ($bloodGroupData as $grp => $n) : ?>
                        <a class="dx-bar" href="admin_donors.php?bloodgroup=<?php echo urlencode($grp); ?>" title="<?php echo dx($grp . ': ' . $n . ' donor(s)'); ?>">
                            <span class="name"><?php echo dx($grp); ?></span>
                            <span class="track"><span class="fill" style="display:block;width:<?php echo (int)round($n / $maxDonorGroup * 100); ?>%;"></span></span>
                            <span class="num"><?php echo (int)$n; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- NEEDS ATTENTION: stock lists -->
            <div class="dx-panel wide">
                <div class="dx-head"><h3>Stock Needing Attention</h3><a href="admin_inventory.php?order=low-first">Open inventory &rarr;</a></div>
                <div class="dx-grid" style="margin:0;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));">
                    <div>
                        <strong style="font-size:14px;color:#c0132d;">Low stock (under <?php echo (int)$lowStockThreshold; ?> units)</strong>
                        <div class="dx-list" style="margin-top:6px;">
                            <?php foreach (array_slice($lowStockRows, 0, 5) as $row) : ?>
                                <a class="dx-row" href="admin_inventory.php?group=<?php echo urlencode($row['blood_group'] ?? ''); ?>">
                                    <div><strong><?php echo dx(($row['blood_group'] ?? '') . ' ' . ($row['component'] ?? '')); ?></strong><span class="meta"><?php echo dx($row['blood_bank_name'] ?? ''); ?></span></div>
                                    <span class="dx-chip cancelled"><?php echo (int)($row['units_available'] ?? 0); ?> units</span>
                                </a>
                            <?php endforeach; ?>
                            <?php if (!$lowStockRows) : ?><div class="dx-empty">All batches are well stocked.</div><?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <strong style="font-size:14px;color:#8a5a00;">Expiring within <?php echo (int)$expiringSoonDays; ?> days</strong>
                        <div class="dx-list" style="margin-top:6px;">
                            <?php foreach (array_slice($expiringSoonRows, 0, 5) as $row) : ?>
                                <a class="dx-row" href="admin_inventory.php?group=<?php echo urlencode($row['blood_group'] ?? ''); ?>">
                                    <div><strong><?php echo dx(($row['blood_group'] ?? '') . ' ' . ($row['component'] ?? '')); ?></strong><span class="meta"><?php echo dx($row['blood_bank_name'] ?? ''); ?></span></div>
                                    <span class="dx-chip pending"><?php echo dx(date('d M', strtotime($row['expiry_date']))); ?></span>
                                </a>
                            <?php endforeach; ?>
                            <?php if (!$expiringSoonRows) : ?><div class="dx-empty">Nothing expires in the next <?php echo (int)$expiringSoonDays; ?> days.</div><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
