<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

include('db_connect.php');

$successMsg = "";
$errorMsg = "";

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Firestore collections are created implicitly on first write — no CREATE TABLE needed.

if (isset($_POST['save_ledger'])) {
    $entryDate = trim($_POST['entry_date'] ?? '');
    $transactionType = trim($_POST['transaction_type'] ?? '');
    $fromPatient = trim($_POST['from_patient_name'] ?? '');
    $toPatient = trim($_POST['to_patient_name'] ?? '');
    $bloodGroup = trim($_POST['blood_group'] ?? '');
    $component = trim($_POST['component'] ?? '');
    $units = (int)($_POST['units'] ?? 0);
    $hospitalName = trim($_POST['hospital_name'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    $validTypes = ['TAKEN', 'GIVEN'];

    if ($entryDate === '') {
        $entryDate = date('Y-m-d');
    }

    if (!in_array($transactionType, $validTypes, true)) {
        $errorMsg = "Invalid transaction type.";
    } elseif ($bloodGroup === '' || $component === '' || $units <= 0) {
        $errorMsg = "Please fill all required fields with valid values.";
    } elseif ($transactionType === 'TAKEN' && $fromPatient === '') {
        $errorMsg = "For TAKEN entries, enter the patient/donor name in From Patient.";
    } elseif ($transactionType === 'GIVEN' && $toPatient === '') {
        $errorMsg = "For GIVEN entries, enter the patient name in To Patient.";
    } else {
        try {
            $db->add('blood_ledger', [
                'entry_date' => $entryDate,
                'transaction_type' => $transactionType,
                'from_patient_name' => $fromPatient,
                'to_patient_name' => $toPatient,
                'blood_group' => $bloodGroup,
                'component' => $component,
                'units' => $units,
                'hospital_name' => $hospitalName,
                'remarks' => $remarks,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $successMsg = "Blood ledger entry saved successfully.";
            $_POST = [];
        } catch (Exception $e) {
            $errorMsg = "Could not save entry: " . $e->getMessage();
        }
    }
}

$searchFilter = trim($_GET['search'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');
$groupFilter = trim($_GET['group'] ?? '');

// Firestore has no LIKE/OR, so the free-text search filter is applied in
// PHP after pulling the (type/group-filtered, if given) set of entries.
$filters = [];
if ($typeFilter !== '') {
    $filters[] = ['field' => 'transaction_type', 'op' => 'EQUAL', 'value' => $typeFilter];
}
if ($groupFilter !== '') {
    $filters[] = ['field' => 'blood_group', 'op' => 'EQUAL', 'value' => $groupFilter];
}

$ledgerRows = [];
try {
    $rows = $db->getWhere('blood_ledger', $filters);
    foreach ($rows as $row) {
        $row['id'] = $row['_id'];
        $ledgerRows[] = $row;
    }
} catch (Exception $e) {
    $errorMsg = "Read query failed: " . $e->getMessage();
}

if ($searchFilter !== '') {
    $needle = strtolower($searchFilter);
    $ledgerRows = array_values(array_filter($ledgerRows, function ($row) use ($needle) {
        return str_contains(strtolower($row['from_patient_name'] ?? ''), $needle)
            || str_contains(strtolower($row['to_patient_name'] ?? ''), $needle)
            || str_contains(strtolower($row['hospital_name'] ?? ''), $needle)
            || str_contains(strtolower($row['remarks'] ?? ''), $needle);
    }));
}

// Newest entry_date first, tie-broken by created_at
usort($ledgerRows, function ($a, $b) {
    return [$b['entry_date'] ?? '', $b['created_at'] ?? '']
        <=> [$a['entry_date'] ?? '', $a['created_at'] ?? ''];
});

$totalInUnits = 0;
$totalOutUnits = 0;
foreach ($ledgerRows as $row) {
    if ($row['transaction_type'] === 'TAKEN') {
        $totalInUnits += (int)$row['units'];
    } else {
        $totalOutUnits += (int)$row['units'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Blood Ledger - Lifeline Admin</title>
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
            line-height: 1.6;
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
            width: calc(100% - 240px);
            padding: 30px;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 4px;
        }

        .header p {
            color: #6b7a8a;
            margin-bottom: 20px;
        }

        .success-msg,
        .error-msg {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .success-msg {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-msg {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 16px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #eadfe4;
            border-radius: 10px;
            padding: 14px;
        }

        .stat-card h4 {
            color: #7a1538;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
        }

        .stat-card p {
            font-size: 28px;
            color: #2f3944;
            font-weight: 800;
        }

        .panel {
            background: #fff;
            border: 1px solid #eadfe4;
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 16px;
        }

        .panel h3 {
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 12px;
        }

        .entry-form {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .entry-form .full {
            grid-column: 1 / -1;
        }

        .entry-form label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .entry-form input,
        .entry-form select,
        .entry-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .entry-form textarea {
            min-height: 80px;
            resize: vertical;
        }

        .save-btn,
        .filter-btn {
            border: none;
            border-radius: 6px;
            padding: 10px 14px;
            background: #7a1538;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .filters {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr auto;
            gap: 10px;
            align-items: end;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eceff3;
            font-size: 13px;
            white-space: nowrap;
        }

        th {
            background: #f8f9fa;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 11px;
        }

        .chip {
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
        }

        .chip.taken {
            background: #d4edda;
            color: #155724;
        }

        .chip.given {
            background: #f8d7da;
            color: #721c24;
        }

        @media(max-width: 960px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .entry-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding: 16px;
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
            }

            .entry-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <div class="sidebar-header">
            <img decoding="async" src="assets/img/logo.jpg" alt="Lifeline Blood Bank Logo">
            <h2>Lifeline Blood Bank</h2>
            <p>Blood Bank Admin</p>
        </div>

        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php">📊 Dashboard</a></li>
            <li><a href="admin_donors.php">👥 Donors</a></li>
            <li><a href="admin_requests.php">🩸 Blood Requests</a></li>
            <li><a href="admin_appointments.php">📅 Appointments</a></li>
            <li><a href="admin_camps.php">🏥 Camps</a></li>
            <li><a href="admin_inventory.php">📦 Inventory</a></li>
            <li><a href="admin_blood_ledger.php" class="active">🧾 Blood Ledger</a></li>
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
            <h1>Blood Ledger</h1>
            <p>Track from which patient/donor blood was taken and to which patient it was given.</p>
        </div>

        <?php if ($successMsg !== "") : ?>
            <div class="success-msg"><?php echo htmlspecialchars($successMsg); ?></div>
        <?php endif; ?>

        <?php if ($errorMsg !== "") : ?>
            <div class="error-msg"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <h4>Total Entries</h4>
                <p><?php echo count($ledgerRows); ?></p>
            </div>
            <div class="stat-card">
                <h4>Total Taken Units</h4>
                <p><?php echo $totalInUnits; ?></p>
            </div>
            <div class="stat-card">
                <h4>Total Given Units</h4>
                <p><?php echo $totalOutUnits; ?></p>
            </div>
        </div>

        <div class="panel">
            <h3>Add Ledger Entry</h3>
            <form method="post" class="entry-form">
                <div>
                    <label for="entry_date">Date</label>
                    <input type="date" id="entry_date" name="entry_date" value="<?php echo htmlspecialchars($_POST['entry_date'] ?? date('Y-m-d')); ?>">
                </div>
                <div>
                    <label for="transaction_type">Transaction Type</label>
                    <select id="transaction_type" name="transaction_type" required>
                        <option value="TAKEN" <?php echo (($_POST['transaction_type'] ?? '') === 'TAKEN') ? 'selected' : ''; ?>>TAKEN (Collected)</option>
                        <option value="GIVEN" <?php echo (($_POST['transaction_type'] ?? '') === 'GIVEN') ? 'selected' : ''; ?>>GIVEN (Issued)</option>
                    </select>
                </div>
                <div>
                    <label for="blood_group">Blood Group</label>
                    <select id="blood_group" name="blood_group" required>
                        <option value="">Select</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg) : ?>
                            <option value="<?php echo $bg; ?>" <?php echo (($_POST['blood_group'] ?? '') === $bg) ? 'selected' : ''; ?>><?php echo $bg; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="component">Component</label>
                    <select id="component" name="component" required>
                        <option value="">Select</option>
                        <?php foreach (['Whole Blood','Packed RBC','Plasma','Platelets'] as $component) : ?>
                            <option value="<?php echo htmlspecialchars($component); ?>" <?php echo (($_POST['component'] ?? '') === $component) ? 'selected' : ''; ?>><?php echo htmlspecialchars($component); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="from_patient_name">From Patient/Donor</label>
                    <input type="text" id="from_patient_name" name="from_patient_name" value="<?php echo htmlspecialchars($_POST['from_patient_name'] ?? ''); ?>" placeholder="Patient/Donor name">
                </div>
                <div>
                    <label for="to_patient_name">To Patient</label>
                    <input type="text" id="to_patient_name" name="to_patient_name" value="<?php echo htmlspecialchars($_POST['to_patient_name'] ?? ''); ?>" placeholder="Recipient patient name">
                </div>
                <div>
                    <label for="units">Units</label>
                    <input type="number" id="units" name="units" min="1" value="<?php echo htmlspecialchars($_POST['units'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="hospital_name">Hospital/Ward</label>
                    <input type="text" id="hospital_name" name="hospital_name" value="<?php echo htmlspecialchars($_POST['hospital_name'] ?? ''); ?>" placeholder="Hospital or ward">
                </div>
                <div class="full">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" placeholder="Extra notes..."><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
                </div>
                <div class="full">
                    <button type="submit" name="save_ledger" class="save-btn">Save Entry</button>
                </div>
            </form>
        </div>

        <div class="panel">
            <h3>Ledger History</h3>
            <form method="get" class="filters" style="margin-bottom: 12px;">
                <div>
                    <label for="search" style="display:block; margin-bottom:5px; font-size:12px; text-transform:uppercase; font-weight:600; color:#2c3e50;">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchFilter); ?>" placeholder="Patient, hospital, remarks" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                </div>
                <div>
                    <label for="type" style="display:block; margin-bottom:5px; font-size:12px; text-transform:uppercase; font-weight:600; color:#2c3e50;">Type</label>
                    <select id="type" name="type" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                        <option value="">All</option>
                        <option value="TAKEN" <?php echo ($typeFilter === 'TAKEN') ? 'selected' : ''; ?>>TAKEN</option>
                        <option value="GIVEN" <?php echo ($typeFilter === 'GIVEN') ? 'selected' : ''; ?>>GIVEN</option>
                    </select>
                </div>
                <div>
                    <label for="group" style="display:block; margin-bottom:5px; font-size:12px; text-transform:uppercase; font-weight:600; color:#2c3e50;">Blood Group</label>
                    <select id="group" name="group" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                        <option value="">All</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg) : ?>
                            <option value="<?php echo $bg; ?>" <?php echo ($groupFilter === $bg) ? 'selected' : ''; ?>><?php echo $bg; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="filter-btn">Apply</button>
                </div>
            </form>

            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>From Patient/Donor</th>
                            <th>To Patient</th>
                            <th>Blood Group</th>
                            <th>Component</th>
                            <th>Units</th>
                            <th>Hospital/Ward</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($ledgerRows) > 0) : ?>
                            <?php foreach ($ledgerRows as $row) : ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars(substr($row['id'], 0, 6)); ?></td>
                                    <td><?php echo htmlspecialchars($row['entry_date']); ?></td>
                                    <td><span class="chip <?php echo strtolower($row['transaction_type']); ?>"><?php echo htmlspecialchars($row['transaction_type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['from_patient_name'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['to_patient_name'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                                    <td><?php echo htmlspecialchars($row['component']); ?></td>
                                    <td><?php echo (int)$row['units']; ?></td>
                                    <td><?php echo htmlspecialchars($row['hospital_name'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['remarks'] ?: '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="10" style="text-align:center; color:#7c8a98;">No blood ledger entries found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
