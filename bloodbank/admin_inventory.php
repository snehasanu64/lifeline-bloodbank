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

// Firestore documents are schemaless. If collection_date/expiry_date were
// never set on a row, fall back to last_updated-derived dates, same as
// the original SHOW COLUMNS fallback used to do.
require_once __DIR__ . '/inventory_helper.php';
refresh_stock_dates($db); // keeps batch dates current (runs once a day)

$stockRows = [];
try {
    $rawStock = $db->getAll('blood_stock');
    foreach ($rawStock as $row) {
        if (empty($row['collection_date']) || empty($row['expiry_date'])) {
            $base = !empty($row['last_updated']) ? strtotime($row['last_updated']) : time();
            $row['collection_date'] = $row['collection_date'] ?? date('Y-m-d', $base);
            $row['expiry_date'] = $row['expiry_date'] ?? date('Y-m-d', strtotime('+35 days', $base));
        }
        $stockRows[] = $row;
    }
    usort($stockRows, function ($a, $b) {
        return [$a['blood_bank_name'] ?? '', $a['blood_group'] ?? '', $a['component'] ?? '']
            <=> [$b['blood_bank_name'] ?? '', $b['blood_group'] ?? '', $b['component'] ?? ''];
    });
} catch (Exception $e) {
    error_log("Stock fetch failed: " . $e->getMessage());
}

$movementRows = [];
try {
    $movementRows = $db->getAll('inventory_transactions', 'trans_date', 'DESCENDING', 8);
} catch (Exception $e) {
    error_log("Movement fetch failed: " . $e->getMessage());
}

$totalUnits = 0;
$criticalCount = 0;
$groups = [];
$banks = [];
foreach ($stockRows as $row) {
    $units = (int)$row['units_available'];
    $totalUnits += $units;
    if ($units <= 5) {
        $criticalCount++;
    }
    $groups[$row['blood_group']] = true;
    $banks[$row['blood_bank_name']] = true;
}

$searchText = isset($_GET['q']) ? trim($_GET['q']) : '';
$groupFilter = isset($_GET['group']) ? trim($_GET['group']) : '';
$stockOrder = isset($_GET['order']) ? trim($_GET['order']) : 'default';

$allGroups = array_keys($groups);
sort($allGroups);

$filteredRows = array_filter($stockRows, function ($row) use ($searchText, $groupFilter) {
    $passesGroup = ($groupFilter === '' || strcasecmp($row['blood_group'], $groupFilter) === 0);
    if (!$passesGroup) {
        return false;
    }

    if ($searchText === '') {
        return true;
    }

    $haystack = strtolower($row['blood_bank_name'] . ' ' . $row['blood_group'] . ' ' . $row['component'] . ' ' . $row['collection_date'] . ' ' . $row['expiry_date']);
    return strpos($haystack, strtolower($searchText)) !== false;
});

$filteredRows = array_values($filteredRows);

if ($stockOrder === 'low-first') {
    usort($filteredRows, function ($a, $b) {
        return ((int)$a['units_available']) <=> ((int)$b['units_available']);
    });
}

$safeCount = max(count($stockRows) - $criticalCount, 0);
$bankCount = count($banks);
$filteredCount = count($filteredRows);
$movementCount = count($movementRows);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inventory - Lifeline Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0f1824;
            --muted: #606978;
            --paper: #f9f2f5;
            --surface: #ffffff;
            --line: #e7ebf2;
            --brand: #c0132d;
            --brand-deep: #970e23;
            --sidebar-top: #7a1538;
            --sidebar-bottom: #5e0f2a;
            --critical-bg: #ffe9ed;
            --critical-text: #a3061f;
            --safe-bg: #ebfaf6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Manrope', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--paper);
            color: var(--ink);
            line-height: 1.6;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, var(--sidebar-top) 0%, var(--sidebar-bottom) 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 12px 0 28px rgba(11, 16, 28, 0.24);
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
            border: 2px solid rgba(255, 255, 255, 0.28);
            background: #fff;
            padding: 5px;
        }

        .sidebar-header h2 {
            font-family: 'Sora', 'Manrope', sans-serif;
            font-size: 18px;
        }

        .sidebar-header p {
            color: rgba(216, 223, 238, 0.75);
            font-size: 13px;
            margin-top: 3px;
        }

        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin: 0; }
        .sidebar-menu a {
            display: block;
            color: #d8dfee;
            padding: 12px 20px;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            font-weight: 700;
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

        .logout-btn {
            width: 100%;
            border: none;
            background: var(--brand);
            color: white;
            padding: 11px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 800;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .logout-btn:hover {
            transform: translateY(-1px);
            background: #d51532;
        }

        .main-content {
            margin-left: 240px;
            padding: 30px;
            width: calc(100% - 240px);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }

        .header h1 {
            font-family: 'Sora', 'Manrope', sans-serif;
            color: var(--ink);
            font-size: 38px;
            line-height: 1.12;
        }

        .header p {
            color: var(--muted);
            margin-top: 4px;
        }

        .request-btn {
            text-decoration: none;
            background: var(--brand);
            color: #fff;
            font-weight: 800;
            padding: 11px 16px;
            border-radius: 11px;
            box-shadow: 0 10px 18px rgba(192, 19, 45, 0.25);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            white-space: nowrap;
        }

        .request-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 24px rgba(192, 19, 45, 0.32);
            background: #d51532;
        }

        .portal-highlight {
            background:
                radial-gradient(circle at 86% 26%, rgba(18, 125, 219, 0.14) 0%, transparent 34%),
                linear-gradient(135deg, #ffffff 0%, #f8fbff 58%, #eef5ff 100%);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 16px 28px rgba(17, 27, 45, 0.09);
            border: 1px solid var(--line);
            margin-bottom: 20px;
        }

        .portal-highlight h2 {
            font-family: 'Sora', 'Manrope', sans-serif;
            font-size: 22px;
            margin-bottom: 6px;
        }

        .portal-highlight p {
            color: var(--muted);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 13px;
            padding: 16px;
            box-shadow: 0 10px 20px rgba(17, 27, 45, 0.07);
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .stat-card h3 {
            color: var(--muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .stat-card p {
            color: var(--ink);
            font-size: 34px;
            font-weight: 800;
            line-height: 1.12;
        }

        .stat-card span {
            color: var(--muted);
            font-size: 13px;
        }

        .stat-card.critical {
            background: var(--critical-bg);
            border-color: #ffd5dd;
        }

        .stat-card.critical h3,
        .stat-card.critical p {
            color: var(--critical-text);
        }

        .stat-card.safe {
            background: var(--safe-bg);
            border-color: #d7efe7;
        }

        .controls {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 13px;
            padding: 14px;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr auto;
            gap: 12px;
            margin-bottom: 16px;
            box-shadow: 0 10px 20px rgba(17, 27, 45, 0.06);
        }

        .controls input,
        .controls select {
            width: 100%;
            border: 1px solid #dce2ec;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
            font-family: inherit;
            color: var(--ink);
            background: #fbfcff;
        }

        .controls button,
        .reset-btn {
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            font-family: inherit;
            font-weight: 800;
            font-size: 13px;
            cursor: pointer;
        }

        .controls button {
            background: var(--brand);
            color: #fff;
        }

        .reset-btn {
            text-decoration: none;
            background: #eff3f9;
            color: #46526a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .table-box {
            background: var(--surface);
            padding: 18px;
            border-radius: 13px;
            box-shadow: 0 10px 20px rgba(17, 27, 45, 0.08);
            border: 1px solid var(--line);
            overflow-x: auto;
        }

        .table-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .table-head h3 {
            font-family: 'Sora', 'Manrope', sans-serif;
            font-size: 22px;
        }

        .result-badge {
            font-size: 12px;
            font-weight: 800;
            color: #365087;
            background: #eef3ff;
            border: 1px solid #dae4ff;
            padding: 5px 10px;
            border-radius: 999px;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #edf1f6;
            text-align: left;
            font-size: 14px;
            white-space: nowrap;
        }

        th {
            color: #556179;
            background: #f8fafe;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-size: 11px;
            font-weight: 800;
        }

        tbody tr:hover {
            background: #fafcff;
        }

        .critical {
            color: var(--critical-text);
            font-weight: 800;
        }

        .group-chip {
            display: inline-block;
            background: #eef3ff;
            color: #2f4676;
            border: 1px solid #dbe5ff;
            border-radius: 999px;
            padding: 3px 10px;
            font-weight: 700;
            font-size: 12px;
        }

        .component-tag {
            color: #3b4554;
            font-weight: 700;
        }

        .movement-box {
            margin-top: 16px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 13px;
            padding: 18px;
            box-shadow: 0 10px 20px rgba(17, 27, 45, 0.08);
        }

        .movement-box h3 {
            font-family: 'Sora', 'Manrope', sans-serif;
            font-size: 22px;
            margin-bottom: 10px;
        }

        .movement-table {
            width: 100%;
            border-collapse: collapse;
        }

        .movement-table td {
            border-bottom: 1px solid #edf1f6;
            padding: 11px 10px;
            font-size: 14px;
            white-space: nowrap;
        }

        .movement-badge {
            display: inline-block;
            min-width: 44px;
            text-align: center;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }

        .movement-badge.in {
            background: #e8fbf1;
            color: #0e7a45;
            border: 1px solid #c8efd8;
        }

        .movement-badge.out {
            background: #ffecec;
            color: #b61b2c;
            border: 1px solid #ffd4da;
        }

        .movement-note {
            color: var(--muted);
        }

        @media(max-width: 900px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; width: calc(100% - 200px); padding: 15px; }
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .controls { grid-template-columns: 1fr 1fr; }
            .header { flex-direction: column; align-items: flex-start; }
        }

        @media(max-width: 640px) {
            .dashboard-container { flex-direction: column; }

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

            .stats,
            .controls {
                grid-template-columns: 1fr;
            }
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
            <li><a href="admin_dashboard.php">📊 Dashboard</a></li>
            <li><a href="admin_donors.php">👥 Donors</a></li>
            <li><a href="admin_requests.php">🩸 Blood Requests</a></li>
            <li><a href="admin_appointments.php">📅 Appointments</a></li>
            <li><a href="admin_camps.php">🏥 Camps</a></li>
            <li><a href="admin_inventory.php" class="active">📦 Inventory</a></li>
            <li><a href="admin_blood_ledger.php">🧾 Blood Ledger</a></li>
            <li><a href="admin_analytics.php">📈 Analytics</a></li>
        </ul>

        <div class="sidebar-logout">
            <form method="post" style="display:inline;">
                <button type="submit" name="logout" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div>
                <h1>Inventory Management</h1>
                <p>Real-time blood stock monitoring and quick low-unit action.</p>
            </div>
            <a class="request-btn" href="admin_requests.php">Log Outgoing Request</a>
        </div>

        <div class="portal-highlight">
            <h2>Live Blood Stock Intelligence</h2>
            <p>Monitor units by bank, group, and component with instant filtering for urgent shortages.</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>Total Inventory</h3>
                <p><?php echo $totalUnits; ?></p>
                <span>Units in storage</span>
            </div>
            <div class="stat-card critical">
                <h3>Critical Low Stock</h3>
                <p><?php echo $criticalCount; ?></p>
                <span>Rows with 5 units or less</span>
            </div>
            <div class="stat-card safe">
                <h3>Stable Rows</h3>
                <p><?php echo $safeCount; ?></p>
                <span>Rows above the risk threshold</span>
            </div>
            <div class="stat-card">
                <h3>Active Banks</h3>
                <p><?php echo $bankCount; ?></p>
                <span><?php echo count($allGroups); ?> blood groups tracked</span>
            </div>
        </div>

        <form method="get" class="controls">
            <input type="text" name="q" placeholder="Search bank, group, component, date..." value="<?php echo htmlspecialchars($searchText); ?>">
            <select name="group">
                <option value="">All Blood Groups</option>
                <?php foreach ($allGroups as $group) : ?>
                    <option value="<?php echo htmlspecialchars($group); ?>" <?php echo (strcasecmp($group, $groupFilter) === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars($group); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="order">
                <option value="default" <?php echo ($stockOrder === 'default') ? 'selected' : ''; ?>>Default Order</option>
                <option value="low-first" <?php echo ($stockOrder === 'low-first') ? 'selected' : ''; ?>>Low Stock First</option>
            </select>
            <div style="display:flex; gap:8px;">
                <button type="submit">Apply</button>
                <a class="reset-btn" href="admin_inventory.php">Reset</a>
            </div>
        </form>

        <div class="table-box">
            <div class="table-head">
                <h3>Live Inventory Registry</h3>
                <span class="result-badge"><?php echo $filteredCount; ?> results</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Blood Bank</th>
                        <th>Blood Group</th>
                        <th>Component</th>
                        <th>Units Available</th>
                        <th>Collected Date</th>
                        <th>Expiry Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($filteredCount > 0) : ?>
                        <?php foreach ($filteredRows as $row) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['blood_bank_name']); ?></td>
                                <td><span class="group-chip"><?php echo htmlspecialchars($row['blood_group']); ?></span></td>
                                <td><span class="component-tag"><?php echo htmlspecialchars($row['component']); ?></span></td>
                                <td class="<?php echo ((int)$row['units_available'] <= 5) ? 'critical' : ''; ?>"><?php echo (int)$row['units_available']; ?></td>
                                <td><?php echo htmlspecialchars($row['collection_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['expiry_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6" style="text-align:center; color:#728097;">No records matched your current filter.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="movement-box">
            <div class="table-head">
                <h3>Recent Stock Movements</h3>
                <span class="result-badge"><?php echo $movementCount; ?> latest entries</span>
            </div>
            <table class="movement-table">
                <tbody>
                    <?php if ($movementCount > 0) : ?>
                        <?php foreach ($movementRows as $row) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['trans_date']); ?></td>
                                <td><span class="movement-badge <?php echo strtolower($row['trans_type']); ?>"><?php echo htmlspecialchars($row['trans_type']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['source_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['source_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['blood_bank_name']); ?></td>
                                <td><span class="group-chip"><?php echo htmlspecialchars($row['blood_group']); ?></span></td>
                                <td><span class="component-tag"><?php echo htmlspecialchars($row['component']); ?></span></td>
                                <td><?php echo (int)$row['units']; ?> units</td>
                                <td class="movement-note"><?php echo htmlspecialchars($row['remarks'] ?: ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td style="color:#728097; padding: 10px 0;">No recent movement records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
