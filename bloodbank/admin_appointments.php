<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

include('db_connect.php');

$successMsg = "";
$errorMsg = "";

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Handle status update
if (isset($_POST['update_status'])) {
    $apt_id = trim($_POST['apt_id'] ?? '');
    $new_status = trim($_POST['new_status'] ?? '');
    
    $validStatuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
    if (!in_array($new_status, $validStatuses)) {
        $errorMsg = "Invalid status.";
    } else {
        try {
            $db->update('donation_appointments', $apt_id, ['status' => $new_status]);
            $successMsg = "Appointment status updated to " . htmlspecialchars($new_status);
            if ($new_status === 'Completed') {
                // Keep the blood ledger up to date automatically.
                require_once __DIR__ . '/inventory_helper.php';
                $aptDoc = $db->getById('donation_appointments', $apt_id);
                if ($aptDoc && ledger_from_appointment($db, $aptDoc)) {
                    $successMsg .= " and recorded in the Blood Ledger";
                }
            }
        } catch (Exception $e) {
            error_log("Appointment status update failed: " . $e->getMessage());
            $errorMsg = "Error updating appointment.";
        }
    }
}

// Get filters
$statusFilter = trim($_GET['status'] ?? '');
$locationFilter = trim($_GET['location'] ?? '');

// Build query
$filters = [];
if (!empty($statusFilter)) {
    $filters[] = ['field' => 'status', 'op' => 'EQUAL', 'value' => $statusFilter];
}
if (!empty($locationFilter)) {
    $filters[] = ['field' => 'location', 'op' => 'EQUAL', 'value' => $locationFilter];
}

$appointments = [];
try {
    $rows = $db->getWhere('donation_appointments', $filters);
    usort($rows, fn($a, $b) => strcmp($b['appointment_date'] ?? '', $a['appointment_date'] ?? ''));
    foreach ($rows as $row) {
        $row['id'] = $row['_id'];
        $appointments[] = $row;
    }
} catch (Exception $e) {
    error_log("Appointment list fetch failed: " . $e->getMessage());
}

// Get locations
$locations = ['Lifeline Office', 'MG Road Camp', 'University Campus Camp', 'Electronic City Camp'];

// Get unique locations actually used in Firestore (no DISTINCT, so dedupe in PHP)
$dbLocations = [];
try {
    $allAppointments = $db->getAll('donation_appointments');
    foreach ($allAppointments as $loc) {
        if (!empty($loc['location'])) {
            $dbLocations[] = $loc['location'];
        }
    }
} catch (Exception $e) {
    error_log("Location list fetch failed: " . $e->getMessage());
}
$allLocations = array_unique(array_merge($locations, $dbLocations));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointments Management - Lifeline Blood Bank</title>
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

        .sidebar-header img {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 2px solid rgba(255, 255, 255, 0.35);
            background: #fff;
            padding: 5px;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 10px;
        }

        .sidebar-header h2 {
            font-size: 16px;
            margin-bottom: 4px;
        }

        .sidebar-header p {
            font-size: 12px;
            opacity: 0.8;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin: 0;
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

        .logout-btn:hover {
            background: #c02455;
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
            margin-bottom: 30px;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 28px;
        }

        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #7a1538;
            box-shadow: 0 0 0 3px rgba(122, 21, 56, 0.1);
        }

        .filter-btn {
            background: #7a1538;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .filter-btn:hover {
            background: #c02455;
        }

        .table-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .table-header h3 {
            color: #2c3e50;
            font-size: 16px;
        }

        .apt-count {
            background: #7a1538;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }

        th {
            padding: 12px;
            text-align: left;
            color: #2c3e50;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge.confirmed {
            background: #cfe2ff;
            color: #084298;
        }

        .badge.completed {
            background: #d4edda;
            color: #155724;
        }

        .badge.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .blood-group {
            background: #7a1538;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            display: inline-block;
        }

        .status-form {
            display: flex;
            gap: 5px;
        }

        .status-form select {
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
        }

        .status-form button {
            padding: 6px 10px;
            background: #7a1538;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
        }

        .status-form button:hover {
            background: #c02455;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        @media(max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
                width: calc(100% - 200px);
                padding: 15px;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 22px;
            }

            table {
                font-size: 12px;
            }

            .status-form {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- SIDEBAR -->
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
            <li><a href="admin_appointments.php" class="active">📅 Appointments</a></li>
            <li><a href="admin_camps.php">🏥 Camps</a></li>
            <li><a href="admin_inventory.php">📦 Inventory</a></li>
            <li><a href="admin_blood_ledger.php">🧾 Blood Ledger</a></li>
            <li><a href="admin_analytics.php">📈 Analytics</a></li>
        </ul>

        <div class="sidebar-logout">
            <form method="post" style="display:inline;">
                <button type="submit" name="logout" class="logout-btn">🚪 Logout</button>
            </form>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="header">
            <h1>📅 Appointments Management</h1>
        </div>

        <?php if (!empty($successMsg)) : ?>
            <div class="success-msg"><?php echo htmlspecialchars($successMsg); ?></div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)) : ?>
            <div class="error-msg"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>

        <!-- FILTERS -->
        <div class="filters">
            <form method="get" style="display: contents;">
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">-- All Status --</option>
                        <option value="Pending" <?php echo ($statusFilter === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Confirmed" <?php echo ($statusFilter === 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="Completed" <?php echo ($statusFilter === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo ($statusFilter === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="location">Location</label>
                    <select id="location" name="location">
                        <option value="">-- All Locations --</option>
                        <?php foreach ($allLocations as $loc) : ?>
                            <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo ($locationFilter === $loc) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="filter-btn">🔍 Filter</button>
            </form>
        </div>

        <!-- APPOINTMENTS TABLE -->
        <div class="table-box">
            <div class="table-header">
                <h3>Donation Appointments</h3>
                <span class="apt-count"><?php echo count($appointments); ?> Total</span>
            </div>

            <?php if (count($appointments) > 0) : ?>
                <table>
                    <thead>
                        <tr>
                            <th>Donor Name</th>
                            <th>Email</th>
                            <th>Blood Group</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $apt) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($apt['donor_name']); ?></td>
                                <td><?php echo htmlspecialchars($apt['donor_email']); ?></td>
                                <td><span class="blood-group"><?php echo htmlspecialchars($apt['bloodgroup']); ?></span></td>
                                <td><?php echo date('d M Y', strtotime($apt['appointment_date'])); ?></td>
                                <td><?php echo htmlspecialchars($apt['appointment_time']); ?></td>
                                <td><?php echo htmlspecialchars(substr($apt['location'], 0, 25)); ?></td>
                                <td><span class="badge <?php echo strtolower($apt['status']); ?>"><?php echo htmlspecialchars($apt['status']); ?></span></td>
                                <td>
                                    <form method="post" class="status-form">
                                        <input type="hidden" name="apt_id" value="<?php echo htmlspecialchars($apt['id']); ?>">
                                        <select name="new_status">
                                            <option value="Pending" <?php echo ($apt['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Confirmed" <?php echo ($apt['status'] === 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="Completed" <?php echo ($apt['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Cancelled" <?php echo ($apt['status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_status">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="empty-state">
                    <p>No appointments found. Try adjusting your filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
