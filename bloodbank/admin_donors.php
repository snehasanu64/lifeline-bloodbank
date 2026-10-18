<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

include('db_connect.php');

$successMsg = "";
$errorMsg = "";

// Firestore documents are schemaless, so every field is just read with a
// null-coalesce fallback below instead of checking SHOW COLUMNS first.

function getDonorStatus(array $donor): string
{
    if (!empty($donor['status'])) {
        return $donor['status'];
    }

    if (empty($donor['lastdonation']) || $donor['lastdonation'] === '0000-00-00') {
        return 'Eligible to Donate';
    }

    $lastDate = new DateTime($donor['lastdonation']);
    $nextDate = clone $lastDate;
    $nextDate->modify('+90 days');

    return (new DateTime() >= $nextDate) ? 'Eligible to Donate' : 'Not Eligible Yet';
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Handle delete donor
if (isset($_POST['delete_donor'])) {
    $donor_email = trim($_POST['donor_email'] ?? '');

    if (!empty($donor_email)) {
        try {
            $donorToDelete = $db->getOneWhere('donors', 'email', $donor_email);
            if ($donorToDelete) {
                $db->delete('donors', $donorToDelete['_id']);
                $successMsg = "Donor deleted successfully.";
            } else {
                $errorMsg = "Donor not found.";
            }
        } catch (Exception $e) {
            $errorMsg = "Error deleting donor: " . $e->getMessage();
        }
    }
}

// Get search filter
$searchFilter = trim($_GET['search'] ?? '');
$bloodGroupFilter = trim($_GET['bloodgroup'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

// Fetch donors.
// Firestore has no LIKE/OR search, so the free-text search and the
// eligibility-by-date filter are both applied in PHP after pulling the
// (blood-group-filtered, if given) set of donors.
try {
    if ($bloodGroupFilter !== '') {
        $rawDonors = $db->getWhere('donors', [['field' => 'bloodgroup', 'op' => 'EQUAL', 'value' => $bloodGroupFilter]]);
    } else {
        $rawDonors = $db->getAll('donors');
    }
} catch (Exception $e) {
    die("Query failed: " . htmlspecialchars($e->getMessage()));
}

$donors = [];
foreach ($rawDonors as $row) {
    $row['donor_id'] = $row['_id'];
    $row['username'] = $row['username'] ?? ($row['name'] ?? '');
    $row['computed_status'] = getDonorStatus($row);
    $donors[] = $row;
}

if ($searchFilter !== '') {
    $needle = strtolower($searchFilter);
    $donors = array_values(array_filter($donors, function ($donor) use ($needle) {
        return str_contains(strtolower($donor['username'] ?? ''), $needle)
            || str_contains(strtolower($donor['email'] ?? ''), $needle)
            || str_contains(strtolower($donor['phone'] ?? ''), $needle);
    }));
}

if ($statusFilter !== '') {
    $donors = array_values(array_filter($donors, function ($donor) use ($statusFilter) {
        return $donor['computed_status'] === $statusFilter;
    }));
}

// Sort newest first (Firestore auto-IDs aren't sequential, so sort by
// created_at if present, otherwise leave insertion order).
usort($donors, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

// Get blood groups for filter
$bloodGroups = [];
foreach ($rawDonors as $row) {
    if (!empty($row['bloodgroup']) && !in_array($row['bloodgroup'], $bloodGroups, true)) {
        $bloodGroups[] = $row['bloodgroup'];
    }
}
sort($bloodGroups);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Donors Management - Lifeline Blood Bank</title>
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
                    grid-template-columns: 1fr 1fr 1fr auto;
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

        .donor-count {
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

        .blood-group {
            background: #7a1538;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            display: inline-block;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 12px;
            display: inline-block;
        }

        .status-eligible {
            background: #d4edda;
            color: #155724;
        }

        .status-not-eligible-yet {
            background: #f8d7da;
            color: #721c24;
        }

        .status-not-available {
            background: #e2e3e5;
            color: #495057;
        }

        .action-btn {
            padding: 6px 12px;
            margin: 2px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            transition: all 0.2s;
        }

        .view-btn {
            background: #17a2b8;
            color: white;
        }

        .view-btn:hover {
            background: #138496;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 24, 36, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 9999;
        }

        .modal-backdrop.open {
            display: flex;
        }

        .modal-card {
            width: min(720px, 100%);
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(180deg, #7a1538 0%, #5e0f2a 100%);
            color: #fff;
            padding: 18px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .modal-header h3 {
            font-size: 18px;
        }

        .modal-close {
            border: none;
            background: rgba(255, 255, 255, 0.16);
            color: #fff;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
        }

        .modal-body {
            padding: 20px;
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .detail-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 12px;
        }

        .detail-item .label {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #7a1538;
            margin-bottom: 6px;
            font-weight: 700;
        }

        .detail-item .value {
            font-size: 14px;
            color: #2c3e50;
            word-break: break-word;
        }

        .modal-footer {
            padding: 0 20px 20px;
            display: flex;
            justify-content: flex-end;
        }

        .modal-footer button {
            background: #7a1538;
            color: #fff;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
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

            .action-btn {
                padding: 4px 8px;
                font-size: 11px;
            }

            .modal-body {
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
            <li><a href="admin_donors.php" class="active">👥 Donors</a></li>
            <li><a href="admin_requests.php">🩸 Blood Requests</a></li>
            <li><a href="admin_appointments.php">📅 Appointments</a></li>
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

    <div class="main-content">
        <div class="header">
            <h1>👥 Donors Management</h1>
        </div>

        <?php if (!empty($successMsg)) : ?>
            <div class="success-msg"><?php echo htmlspecialchars($successMsg); ?></div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)) : ?>
            <div class="error-msg"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>

        <div class="filters">
            <form method="get" style="display: contents;">
                <div class="filter-group">
                    <label for="search">Search Name / Email / Phone</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchFilter); ?>" placeholder="Type to search...">
                </div>

                <div class="filter-group">
                    <label for="bloodgroup">Blood Group</label>
                    <select id="bloodgroup" name="bloodgroup">
                        <option value="">-- All Blood Groups --</option>
                        <?php foreach ($bloodGroups as $bg) : ?>
                            <option value="<?php echo htmlspecialchars($bg); ?>" <?php echo ($bloodGroupFilter === $bg) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bg); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">-- All Statuses --</option>
                        <option value="Eligible to Donate" <?php echo ($statusFilter === 'Eligible to Donate') ? 'selected' : ''; ?>>Eligible to Donate</option>
                        <option value="Not Eligible Yet" <?php echo ($statusFilter === 'Not Eligible Yet') ? 'selected' : ''; ?>>Not Eligible Yet</option>
                    </select>
                </div>

                <button type="submit" class="filter-btn">🔍 Filter</button>
            </form>
        </div>

        <div class="table-box">
            <div class="table-header">
                <h3>Registered Donors</h3>
                <span class="donor-count"><?php echo count($donors); ?> Total</span>
            </div>

            <?php if (count($donors) > 0) : ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Age</th>
                            <th>Blood Group</th>
                            <th>Address</th>
                            <th>Last Donation</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donors as $donor) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($donor['username']); ?></td>
                                <td><?php echo htmlspecialchars($donor['email']); ?></td>
                                <td><?php echo htmlspecialchars($donor['phone']); ?></td>
                                <td><?php echo htmlspecialchars($donor['age']); ?></td>
                                <td><span class="blood-group"><?php echo htmlspecialchars($donor['bloodgroup']); ?></span></td>
                                <td><?php echo htmlspecialchars(!empty($donor['address']) ? substr($donor['address'], 0, 30) : 'N/A'); ?></td>
                                <td>
                                    <?php
                                    if (!empty($donor['lastdonation']) && $donor['lastdonation'] !== '0000-00-00') {
                                        echo date('d M Y', strtotime($donor['lastdonation']));
                                    } else {
                                        echo 'Never';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = strtolower(str_replace(' ', '-', $donor['computed_status']));
                                    if (!in_array($statusClass, ['eligible-to-donate', 'not-eligible-yet'], true)) {
                                        $statusClass = 'not-available';
                                    }
                                    ?>
                                    <span class="status-badge status-<?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($donor['computed_status']); ?></span>
                                </td>
                                <td>
                                    <button class="action-btn view-btn" type="button" onclick='viewDonor(<?php echo json_encode([
                                        "name" => $donor["username"],
                                        "email" => $donor["email"],
                                        "phone" => $donor["phone"],
                                        "age" => $donor["age"],
                                        "bloodgroup" => $donor["bloodgroup"],
                                        "address" => $donor["address"],
                                        "lastdonation" => $donor["lastdonation"],
                                        "created_at" => $donor["created_at"],
                                        "status" => $donor["computed_status"],
                                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>)'>View</button>

                                    <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this donor?');">
                                        <input type="hidden" name="donor_email" value="<?php echo htmlspecialchars($donor['email']); ?>">
                                        <button type="submit" name="delete_donor" class="action-btn delete-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="empty-state">
                    <p>No donors found. Try adjusting your filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="donorModal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="donorModalTitle">
        <div class="modal-header">
            <h3 id="donorModalTitle">Donor Details</h3>
            <button class="modal-close" type="button" onclick="closeDonorModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-item"><span class="label">Name</span><div class="value" id="modalName"></div></div>
            <div class="detail-item"><span class="label">Email</span><div class="value" id="modalEmail"></div></div>
            <div class="detail-item"><span class="label">Phone</span><div class="value" id="modalPhone"></div></div>
            <div class="detail-item"><span class="label">Age</span><div class="value" id="modalAge"></div></div>
            <div class="detail-item"><span class="label">Blood Group</span><div class="value" id="modalBloodGroup"></div></div>
            <div class="detail-item"><span class="label">Status</span><div class="value" id="modalStatus"></div></div>
            <div class="detail-item"><span class="label">Last Donation</span><div class="value" id="modalLastDonation"></div></div>
            <div class="detail-item"><span class="label">Created At</span><div class="value" id="modalCreatedAt"></div></div>
            <div class="detail-item" style="grid-column: 1 / -1;"><span class="label">Address</span><div class="value" id="modalAddress"></div></div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeDonorModal()">Close</button>
        </div>
    </div>
</div>

<script>
function viewDonor(donor) {
    document.getElementById('modalName').textContent = donor.name || 'N/A';
    document.getElementById('modalEmail').textContent = donor.email || 'N/A';
    document.getElementById('modalPhone').textContent = donor.phone || 'N/A';
    document.getElementById('modalAge').textContent = donor.age || 'N/A';
    document.getElementById('modalBloodGroup').textContent = donor.bloodgroup || 'N/A';
    document.getElementById('modalStatus').textContent = donor.status || 'N/A';
    document.getElementById('modalLastDonation').textContent = donor.lastdonation || 'Never';
    document.getElementById('modalCreatedAt').textContent = donor.created_at || 'N/A';
    document.getElementById('modalAddress').textContent = donor.address || 'N/A';

    const modal = document.getElementById('donorModal');
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
}

function closeDonorModal() {
    const modal = document.getElementById('donorModal');
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
}

document.getElementById('donorModal').addEventListener('click', function (event) {
    if (event.target === this) {
        closeDonorModal();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeDonorModal();
    }
});
</script>

</body>
</html>