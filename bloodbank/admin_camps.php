<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

include('db_connect.php');
require_once __DIR__ . '/camps_helper.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfOk = isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
$campMsg = "";
$campErr = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && (isset($_POST['add_camp']) || isset($_POST['update_camp']) || isset($_POST['toggle_camp']) || isset($_POST['delete_camp']))) {
    if (!$csrfOk) {
        $campErr = "Invalid request. Please refresh and try again.";
    } else {
        try {
            if (isset($_POST['add_camp']) || isset($_POST['update_camp'])) {
                $isUpdate = isset($_POST['update_camp']);
                $editId = trim($_POST['camp_id'] ?? '');
                $name = trim($_POST['name'] ?? '');
                $address = trim($_POST['address'] ?? '');
                $time = trim($_POST['time'] ?? '');
                $startDate = trim($_POST['start_date'] ?? '');
                $recurrence = $_POST['recurrence'] ?? 'none';
                $description = trim($_POST['description'] ?? '');
                $mapQuery = trim($_POST['map_query'] ?? '');
                $dateObj = DateTime::createFromFormat('Y-m-d', $startDate);

                if ($name === '' || $address === '' || $time === '') {
                    $campErr = "Camp name, location and time are required.";
                } elseif (!$dateObj || $dateObj->format('Y-m-d') !== $startDate) {
                    $campErr = "Please choose a valid date.";
                } elseif (!$isUpdate && $startDate < date('Y-m-d')) {
                    $campErr = "The first camp date cannot be in the past.";
                } elseif (!in_array($recurrence, ['none', 'weekly', 'monthly'], true)) {
                    $campErr = "Invalid repeat option.";
                } elseif (strlen($name) > 80 || strlen($address) > 150 || strlen($time) > 40 || strlen($description) > 400) {
                    $campErr = "One of the fields is too long.";
                } elseif ($isUpdate && ($editId === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $editId) || !$db->getById('camps', $editId))) {
                    $campErr = "That camp no longer exists.";
                } elseif ($isUpdate) {
                    $db->update('camps', $editId, [
                        'name' => $name,
                        'address' => $address,
                        'time' => $time,
                        'description' => $description,
                        'map_query' => $mapQuery !== '' ? $mapQuery : ($address . ', Bengaluru'),
                        'start_date' => $startDate,
                        'recurrence' => $recurrence,
                    ]);
                    $campMsg = "Camp updated. Changes are live on the Camps page and in donor booking.";
                } else {
                    $db->add('camps', [
                        'name' => $name,
                        'address' => $address,
                        'time' => $time,
                        'description' => $description,
                        'map_query' => $mapQuery !== '' ? $mapQuery : ($address . ', Bengaluru'),
                        'start_date' => $startDate,
                        'recurrence' => $recurrence,
                        'active' => true,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $campMsg = "Camp added. It is now visible on the Camps page and in donor booking.";
                }
            } elseif (isset($_POST['toggle_camp'])) {
                $id = trim($_POST['camp_id'] ?? '');
                $camp = $id !== '' ? $db->getById('camps', $id) : null;
                if ($camp) {
                    $nowActive = !(array_key_exists('active', $camp) && !$camp['active']);
                    $db->update('camps', $id, ['active' => !$nowActive]);
                    $campMsg = $nowActive ? "Camp paused (hidden from visitors)." : "Camp is active again.";
                }
            } elseif (isset($_POST['delete_camp'])) {
                $id = trim($_POST['camp_id'] ?? '');
                if ($id !== '' && $db->getById('camps', $id)) {
                    $db->delete('camps', $id);
                    $campMsg = "Camp deleted.";
                }
            }
        } catch (Exception $e) {
            error_log("Camp admin action failed: " . $e->getMessage());
            $campErr = "Something went wrong. Please try again.";
        }
    }
}

$editCamp = null;
$editParam = trim($_GET['edit'] ?? '');
if ($editParam !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $editParam)) {
    try {
        $editCamp = $db->getById('camps', $editParam);
    } catch (Exception $e) {
        error_log("Camp edit load failed: " . $e->getMessage());
    }
}
$ev = fn($k) => htmlspecialchars((string)($editCamp[$k] ?? ''), ENT_QUOTES, 'UTF-8');

$managedCamps = [];
try {
    $managedCamps = $db->getAll('camps');
} catch (Exception $e) {
    error_log("Camps fetch failed: " . $e->getMessage());
}
usort($managedCamps, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

$successMsg = "";
$errorMsg = "";

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Camps table might not exist in this system - using donations for camp data.
// Firestore has no COUNT/GROUP BY/GROUP_CONCAT, so pull everything once
// and aggregate here in PHP.
$allAppointments = [];
try {
    $allAppointments = $db->getAll('donation_appointments');
} catch (Exception $e) {
    error_log("Camps appointment fetch failed: " . $e->getMessage());
}

$totalAppointments = count($allAppointments);

// Upcoming appointments grouped by date (today or later)
$today = date('Y-m-d');
$byDate = [];
foreach ($allAppointments as $row) {
    if ($row['appointment_date'] >= $today) {
        $date = $row['appointment_date'];
        if (!isset($byDate[$date])) {
            $byDate[$date] = ['appointment_date' => $date, 'count' => 0, 'locations' => []];
        }
        $byDate[$date]['count']++;
        if (!in_array($row['location'], $byDate[$date]['locations'], true)) {
            $byDate[$date]['locations'][] = $row['location'];
        }
    }
}
ksort($byDate);
$upcomingAppointments = [];
foreach (array_slice($byDate, 0, 10) as $row) {
    $row['locations'] = implode(',', $row['locations']);
    $upcomingAppointments[] = $row;
}

// Appointment summary by location
$byLocation = [];
foreach ($allAppointments as $row) {
    $loc = $row['location'];
    if (!isset($byLocation[$loc])) {
        $byLocation[$loc] = ['location' => $loc, 'count' => 0];
    }
    $byLocation[$loc]['count']++;
}
$locationSummary = array_values($byLocation);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Camps Management - Lifeline Blood Bank</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .stat-card .icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .stat-card .label {
            color: #888;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .stat-card .value {
            color: #2c3e50;
            font-size: 28px;
            font-weight: bold;
        }

        .tables-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .table-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .table-box h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 16px;
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

        .empty-state {
            text-align: center;
            padding: 30px;
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .tables-grid {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 22px;
            }

            table {
                font-size: 12px;
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
            <li><a href="admin_appointments.php">📅 Appointments</a></li>
            <li><a href="admin_camps.php" class="active">🏥 Camps</a></li>
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
            <h1>🏥 Camps & Donation Centers</h1>
        </div>
        <!-- MANAGE CAMPS -->
        <div class="table-box" style="margin-bottom:25px;">
            <h3><?php echo $editCamp ? '✏️ Edit Camp' : '➕ Add a Camp'; ?></h3>
            <?php if ($campMsg) : ?><div style="background:#e6f4ea;color:#1b5e20;padding:10px 12px;border-radius:8px;margin-bottom:12px;"><?php echo htmlspecialchars($campMsg, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($campErr) : ?><div style="background:#fdecea;color:#b71c1c;padding:10px 12px;border-radius:8px;margin-bottom:12px;"><?php echo htmlspecialchars($campErr, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <form method="post" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                <?php if ($editCamp) : ?><input type="hidden" name="camp_id" value="<?php echo $ev('_id'); ?>"><?php endif; ?>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <label>Camp name<input type="text" name="name" maxlength="80" value="<?php echo $ev('name'); ?>" required placeholder="e.g. Koramangala Camp" style="width:100%;padding:9px;border:1px solid #ccc;border-radius:8px;"></label>
                <label>Location<input type="text" name="address" maxlength="150" value="<?php echo $ev('address'); ?>" required placeholder="e.g. Forum Mall, Koramangala" style="width:100%;padding:9px;border:1px solid #ccc;border-radius:8px;"></label>
                <label>Time<input type="text" name="time" maxlength="40" value="<?php echo $ev('time'); ?>" required placeholder="9:00 AM - 5:00 PM" style="width:100%;padding:9px;border:1px solid #ccc;border-radius:8px;"></label>
                <label>First date<input type="date" name="start_date" value="<?php echo $ev('start_date'); ?>" <?php echo $editCamp ? '' : 'min="' . date('Y-m-d') . '"'; ?> required style="width:100%;padding:9px;border:1px solid #ccc;border-radius:8px;"></label>
                <label>Repeats
                    <select name="recurrence" style="width:100%;padding:9px;border:1px solid #ccc;border-radius:8px;">
                        <option value="none" <?php echo ($editCamp['recurrence'] ?? 'none') === 'none' ? 'selected' : ''; ?>>Does not repeat (one day only)</option>
                        <option value="weekly" <?php echo ($editCamp['recurrence'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Every week (same weekday)</option>
                        <option value="monthly" <?php echo ($editCamp['recurrence'] ?? '') === 'monthly' ? 'selected' : ''; ?>>Every month (same date)</option>
                    </select>
                </label>
                <label>Map search (optional)<input type="text" name="map_query" maxlength="150" value="<?php echo $ev('map_query'); ?>" placeholder="defaults to the location" style="width:100%;padding:9px;border:1px solid #ccc;border-radius:8px;"></label>
                <label style="grid-column:1/-1;">Description (optional)<input type="text" name="description" maxlength="400" value="<?php echo $ev('description'); ?>" style="width:100%;padding:9px;border:1px solid #ccc;border-radius:8px;"></label>
                <div style="grid-column:1/-1;"><button type="submit" name="<?php echo $editCamp ? 'update_camp' : 'add_camp'; ?>" style="background:#7a1538;color:#fff;border:0;padding:11px 26px;border-radius:24px;font-weight:bold;cursor:pointer;"><?php echo $editCamp ? 'Save Changes' : 'Add Camp'; ?></button>
                    <?php if ($editCamp) : ?><a href="admin_camps.php" style="margin-left:12px;color:#7a1538;">Cancel</a><?php endif; ?></div>
            </form>
            <p style="margin-top:10px;color:#666;font-size:14px;">Dates update by themselves: a repeating camp always shows its next date, and a one-day camp disappears after it has happened.</p>
        </div>

        <div class="table-box" style="margin-bottom:25px;">
            <h3>🏥 Manage Camps</h3>
            <?php if ($managedCamps) : ?>
                <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr><th>Camp</th><th>Location</th><th>Next date</th><th>Repeats</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($managedCamps as $camp) :
                            $next = camp_next_date($camp);
                            $isActive = !(array_key_exists('active', $camp) && !$camp['active']);
                            $statusText = !$isActive ? 'Paused' : ($next ? 'Active' : 'Ended');
                            $repeatText = ['none' => 'One day', 'weekly' => 'Weekly', 'monthly' => 'Monthly'][$camp['recurrence'] ?? 'none'] ?? 'One day';
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($camp['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong><br><small><?php echo htmlspecialchars($camp['time'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small></td>
                            <td><?php echo htmlspecialchars($camp['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $next ? htmlspecialchars(date('d M Y (l)', strtotime($next)), ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                            <td><?php echo $repeatText; ?></td>
                            <td><?php echo $statusText; ?></td>
                            <td style="white-space:nowrap;">
                                <a href="admin_camps.php?edit=<?php echo urlencode($camp['_id']); ?>" style="display:inline-block;padding:6px 12px;border:1px solid #1565c0;background:#fff;color:#1565c0;border-radius:16px;text-decoration:none;font-size:13.3px;">Edit</a>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="camp_id" value="<?php echo htmlspecialchars($camp['_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" name="toggle_camp" style="padding:6px 12px;border:1px solid #7a1538;background:#fff;color:#7a1538;border-radius:16px;cursor:pointer;"><?php echo $isActive ? 'Pause' : 'Resume'; ?></button>
                                </form>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this camp?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="camp_id" value="<?php echo htmlspecialchars($camp['_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" name="delete_camp" style="padding:6px 12px;border:0;background:#b71c1c;color:#fff;border-radius:16px;cursor:pointer;">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else : ?>
                <div class="empty-state">No camps yet. Add one above.</div>
            <?php endif; ?>
        </div>
        <!-- STATISTICS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">📅</div>
                <div class="label">Total Appointments</div>
                <div class="value"><?php echo $totalAppointments; ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">🏥</div>
                <div class="label">Active Locations</div>
                <div class="value"><?php echo count($locationSummary); ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">📆</div>
                <div class="label">Upcoming Dates</div>
                <div class="value"><?php echo count($upcomingAppointments); ?></div>
            </div>
        </div>

        <!-- TABLES -->
        <div class="tables-grid">
            <!-- Upcoming Camps -->
            <div class="table-box">
                <h3>📆 Upcoming Donation Dates</h3>
                <?php if (count($upcomingAppointments) > 0) : ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Donors Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingAppointments as $apt) : ?>
                                <tr>
                                    <td><?php echo date('d M Y (l)', strtotime($apt['appointment_date'])); ?></td>
                                    <td><strong><?php echo $apt['count']; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div class="empty-state">No upcoming dates scheduled</div>
                <?php endif; ?>
            </div>

            <!-- Location Summary -->
            <div class="table-box">
                <h3>📍 Appointments by Location</h3>
                <?php if (count($locationSummary) > 0) : ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Location</th>
                                <th>Total Appointments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($locationSummary as $loc) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($loc['location']); ?></td>
                                    <td><strong><?php echo $loc['count']; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div class="empty-state">No location data available</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>
