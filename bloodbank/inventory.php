<?php
// inventory.php
session_start();
include 'db_connect.php'; // uses $db for Firestore connection

$successMsg = '';
$errorMsg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_inventory'])) {
    $trans_date     = !empty($_POST['trans_date']) ? $_POST['trans_date'] : date('Y-m-d');
    $trans_type     = $_POST['trans_type'] ?? '';
    $source_type    = trim($_POST['source_type'] ?? '');
    $source_name    = trim($_POST['source_name'] ?? '');
    $blood_bank     = trim($_POST['blood_bank'] ?? '');
    $blood_group    = trim($_POST['blood_group'] ?? '');
    $component      = trim($_POST['component'] ?? '');
    $units          = (int)($_POST['units'] ?? 0);
    $remarks        = trim($_POST['remarks'] ?? '');

    if ($trans_date && $trans_type && $source_type && $source_name && $blood_bank &&
        $blood_group && $component && $units > 0) {

        // Firestore's REST API doesn't give us a lightweight multi-document
        // SQL-style transaction here, so this does the two writes in
        // sequence: find the stock row, validate, then update it and log
        // the transaction. Good enough at this project's scale/traffic;
        // for high-concurrency use you'd move this into a Firestore
        // transaction via the :commit endpoint.
        try {
            // 1) Find the matching blood_stock row and validate the change
            $stockRow = null;
            $matches = $db->getWhere('blood_stock', [
                ['field' => 'blood_bank_name', 'op' => 'EQUAL', 'value' => $blood_bank],
                ['field' => 'blood_group', 'op' => 'EQUAL', 'value' => $blood_group],
                ['field' => 'component', 'op' => 'EQUAL', 'value' => $component],
            ], null, 'ASCENDING', 1);
            $stockRow = $matches[0] ?? null;

            if (!$stockRow) {
                throw new Exception('Matching stock record not found in blood_stock collection.');
            }

            $current_units = (int)$stockRow['units_available'];
            $change = ($trans_type === 'IN') ? $units : -$units;
            $new_units = $current_units + $change;

            if ($new_units < 0) {
                throw new Exception('Cannot issue more units than available stock.');
            }

            // 2) Update blood_stock
            $db->update('blood_stock', $stockRow['_id'], [
                'units_available' => $new_units,
                'last_updated' => date('Y-m-d H:i:s'),
            ]);

            // 3) Log the transaction
            $db->add('inventory_transactions', [
                'trans_date' => $trans_date,
                'trans_type' => $trans_type,
                'source_type' => $source_type,
                'source_name' => $source_name,
                'blood_bank_name' => $blood_bank,
                'blood_group' => $blood_group,
                'component' => $component,
                'units' => $units,
                'remarks' => $remarks,
            ]);

            $successMsg = "Inventory updated successfully for $blood_group ($component).";
        } catch (Exception $e) {
            error_log('Inventory error: ' . $e->getMessage());
            $errorMsg = 'Unable to save inventory. ' . $e->getMessage();
        }
    } else {
        $errorMsg = 'Please fill all required fields and enter valid units.';
    }
}

// Fetch recent transactions (last 20)
$transactions = [];
try {
    $transactions = $db->getAll('inventory_transactions', 'trans_date', 'DESCENDING', 20);
} catch (Exception $e) {
    error_log('Inventory transactions fetch failed: ' . $e->getMessage());
}

// Prepare data for daily graph (IN vs OUT per date).
// Firestore has no GROUP BY, so pull every transaction and aggregate here.
$labels = [];
$dataIn = [];
$dataOut = [];

try {
    $allTx = $db->getAll('inventory_transactions', 'trans_date', 'ASCENDING');
    $byDate = [];
    foreach ($allTx as $row) {
        $date = $row['trans_date'];
        if (!isset($byDate[$date])) {
            $byDate[$date] = ['total_in' => 0, 'total_out' => 0];
        }
        if ($row['trans_type'] === 'IN') {
            $byDate[$date]['total_in'] += (int)$row['units'];
        } else {
            $byDate[$date]['total_out'] += (int)$row['units'];
        }
    }
    ksort($byDate);
    foreach ($byDate as $date => $totals) {
        $labels[] = $date;
        $dataIn[] = $totals['total_in'];
        $dataOut[] = $totals['total_out'];
    }
} catch (Exception $e) {
    error_log('Inventory chart data failed: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inventory & Daily Stock Report - Lifeline Blood Bank</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { line-height: 1.6; background: #f5f5f5; }
        .container { width: 85%; margin: auto; }

        header { background: #7a1538; padding: 20px 0; color: white; }
        .nav { display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; }
        .logo img { width: 40px; margin-right: 10px; }
        .menu { list-style: none; display: flex; gap: 30px; }
        .menu li { position: relative; }
        .menu li a { text-decoration: none; color: white; font-weight: bold; }
        .dropdown-menu {
            display: none; position: absolute; top: 35px; left: 0;
            background: #f2f2f2; list-style: none; padding: 0; margin: 0; width: 180px;
        }
        .dropdown-menu li { border-bottom: 1px solid #ddd; }
        .dropdown-menu li a { color: #7a1538; padding: 10px; display: block; }
        .dropdown-menu li a:hover { background: #ddd; }
        .dropdown-menu.show { display: block; }

        .page-section { padding: 50px 0; }
        .layout { display: flex; gap: 30px; flex-wrap: wrap; align-items: flex-start; }
        .card {
            background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 25px; flex: 1; min-width: 300px;
        }
        h1 { font-size: 30px; margin-bottom: 10px; color: #7a1538; }
        h2 { font-size: 24px; margin-bottom: 10px; color: #7a1538; }
        p { font-size: 16px; color: #555; margin-bottom: 10px; }

        label { display: block; margin-top: 10px; margin-bottom: 4px; font-weight: bold; }
        input, select, textarea {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px;
        }
        textarea { resize: vertical; min-height: 60px; }

        .row { display: flex; gap: 15px; flex-wrap: wrap; }
        .row > div { flex: 1; min-width: 200px; }

        .btn-primary {
            margin-top: 15px; padding: 10px 25px; background: #d32f2f; color: white;
            border: none; border-radius: 20px; font-size: 16px; cursor: pointer;
        }
        .btn-primary:hover { background: #b71c1c; }

        .success, .error {
            max-width: 600px; margin: 20px auto 0; padding: 10px 15px;
            border-radius: 5px; text-align: center;
        }
        .success { background: #c8e6c9; color: #256029; border: 1px solid #2e7d32; }
        .error   { background: #ffcdd2; color: #b71c1c; border: 1px solid #b71c1c; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        table th, table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        table th { background: #8a123d; color: #fff; }

        .chart-container { margin-top: 20px; }

        footer {
            background: #7a1538; color: white; padding: 40px 0; margin-top: 50px;
        }
        .footer-content { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 20px; }
        .footer-left { width: 40%; min-width: 260px; font-size: 18px; }
        .footer-links ul { list-style: none; padding: 0; margin: 0; font-size: 20px; }
        .footer-links li { margin-bottom: 8px; }
        .footer-links a { color: white; text-decoration: none; }
        .footer-bottom { text-align: center; margin-top: 20px; }

        @media (max-width: 768px) {
            .container { width: 92%; }
            .nav { flex-direction: column; gap: 12px; align-items: flex-start; }
            .menu { flex-wrap: wrap; }
            .footer-content { flex-direction: column; }
            .footer-left { width: 100%; }
        }
        .footer-logo{ width:64px; height:64px; object-fit:cover; border-radius:50%; background:#fff; padding:3px; margin-bottom:12px; display:block; }
    </style>
</head>
<body>
<header>
    <div class="container">
        <nav class="nav">
            <div class="logo">
                <img decoding="async" src="assets/img/logo.jpg" alt="logo" border="0">
                <h2>Lifeline Blood Bank</h2>
            </div>
            <ul class="menu">
                <li><a href="index.php">HOME</a></li>
                <li><a href="about.php">ABOUT US</a></li>
                <li><a href="contact.php">CONTACT US</a></li>
                <li><a href="camp.php">CAMP</a></li>
                <li class="dropdown">
                    <a href="#" class="login-toggle">LOGIN</a>
                    <ul class="dropdown-menu">
                        <li><a href="donor.php">Donor Login</a></li>
                        <li><a href="request.php">Request</a></li>
                        <li><a href="find.php">Find Blood</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</header>

<?php if (!empty($successMsg)): ?>
    <div class="success"><?php echo htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
    <div class="error"><?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<section class="page-section">
    <div class="container layout">

        <!-- Left: Inventory form -->
        <div class="card">
            <h1>Inventory & Stock Movement</h1>
            <p>Record daily blood units received from donors and issued to patients or hospitals. Stock will be updated automatically.</p>

            <form method="post" action="inventory.php">
                <div class="row">
                    <div>
                        <label for="trans_date">Date</label>
                        <input type="date" id="trans_date" name="trans_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div>
                        <label for="trans_type">Transaction Type</label>
                        <select id="trans_type" name="trans_type" required>
                            <option value="">-- Select --</option>
                            <option value="IN">IN (Received from donor)</option>
                            <option value="OUT">OUT (Issued to patient)</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label for="source_type">Source Type</label>
                        <select id="source_type" name="source_type" required>
                            <option value="">-- Select --</option>
                            <option value="Donor">Donor</option>
                            <option value="Patient">Patient</option>
                            <option value="Hospital">Hospital</option>
                            <option value="Camp">Camp</option>
                        </select>
                    </div>
                    <div>
                        <label for="source_name">Name (Donor / Patient / Hospital)</label>
                        <input type="text" id="source_name" name="source_name" required>
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label for="blood_bank">Blood Bank</label>
                        <select id="blood_bank" name="blood_bank" required>
                            <option value="">-- Select Blood Bank --</option>
                            <option value="LifeCare Blood Bank">LifeCare Blood Bank</option>
                            <option value="Red Cross Blood Bank">Red Cross Blood Bank</option>
                            <option value="Narayana Blood Centre">Narayana Blood Centre</option>
                            <option value="Victoria Hospital Blood Bank">Victoria Hospital Blood Bank</option>
                        </select>
                    </div>
                    <div>
                        <label for="blood_group">Blood Group</label>
                        <select id="blood_group" name="blood_group" required>
                            <option value="">-- Select --</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label for="component">Component</label>
                        <select id="component" name="component" required>
                            <option value="">-- Select Component --</option>
                            <option value="Whole Blood">Whole Blood</option>
                            <option value="Plasma">Plasma</option>
                            <option value="Platelets">Platelets</option>
                            <option value="RBC">RBC</option>
                        </select>
                    </div>
                    <div>
                        <label for="units">Units</label>
                        <input type="number" id="units" name="units" min="1" required>
                    </div>
                </div>

                <label for="remarks">Remarks (optional)</label>
                <textarea id="remarks" name="remarks" placeholder="Any extra details..."></textarea>

                <button type="submit" name="save_inventory" class="btn-primary">Save Inventory Entry</button>
            </form>
        </div>

        <!-- Right: Graph and recent transactions -->
        <div class="card">
            <h2>Daily IN vs OUT Summary</h2>
            <p>Graph of total units received and issued per day.</p>
            <div class="chart-container">
                <canvas id="inventoryChart"></canvas>
            </div>

            <h2 style="margin-top:20px;">Recent Transactions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Source</th>
                        <th>Blood Bank</th>
                        <th>Group</th>
                        <th>Component</th>
                        <th>Units</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($transactions)): ?>
                    <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tx['trans_date']); ?></td>
                            <td><?php echo htmlspecialchars($tx['trans_type']); ?></td>
                            <td><?php echo htmlspecialchars($tx['source_type'] . ' - ' . $tx['source_name']); ?></td>
                            <td><?php echo htmlspecialchars($tx['blood_bank_name']); ?></td>
                            <td><?php echo htmlspecialchars($tx['blood_group']); ?></td>
                            <td><?php echo htmlspecialchars($tx['component']); ?></td>
                            <td><?php echo (int)$tx['units']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No inventory transactions recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</section>

<footer>
    <div class="container footer-content">
        <div class="footer-left">
            <img src="assets/img/logo.jpg" alt="Lifeline Blood Bank logo" class="footer-logo">
            <h2>Lifeline Blood Bank</h2>
            <p>Thank you for supporting voluntary blood donation. Together, we can ensure that no patient suffers due to lack of blood.</p>
        </div>
        <div class="footer-links">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="contact.php">Contact Us</a></li>
                <li><a href="find.php">Find Blood</a></li>
            </ul>
        </div>
        <div class="footer-links">
            <h3>Other Links</h3>
            <ul>
                <li><a href="camp.php">Camp</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="inventory.php">Inventory</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?php echo date('Y'); ?> Lifeline Blood Bank. All rights reserved.
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.querySelector('.login-toggle');
    const menu = document.querySelector('.dropdown-menu');
    if (toggle && menu) {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            menu.classList.toggle('show');
        });
    }

    // Chart.js: IN vs OUT
    const labels = <?php echo json_encode($labels); ?>;
    const dataIn = <?php echo json_encode($dataIn); ?>;
    const dataOut = <?php echo json_encode($dataOut); ?>;

    const ctx = document.getElementById('inventoryChart');
    if (ctx && labels.length > 0) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'IN (Units Received)',
                        data: dataIn,
                        borderColor: 'green',
                        backgroundColor: 'rgba(0,128,0,0.1)',
                        tension: 0.2
                    },
                    {
                        label: 'OUT (Units Issued)',
                        data: dataOut,
                        borderColor: 'red',
                        backgroundColor: 'rgba(255,0,0,0.1)',
                        tension: 0.2
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Units' } },
                    x: { title: { display: true, text: 'Date' } }
                }
            }
        });
    }
});
</script>
</body>
</html>
