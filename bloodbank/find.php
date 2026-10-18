<?php
include('db_connect.php');

$district   = $_GET['district'] ?? '';
$blood_bank = $_GET['blood_bank'] ?? '';
$blood_group = $_GET['blood_group'] ?? '';
$component  = $_GET['component'] ?? '';

$filters = [];

if ($district !== '') {
    $filters[] = ['field' => 'district', 'op' => 'EQUAL', 'value' => $district];
}
if ($blood_bank !== '') {
    $filters[] = ['field' => 'blood_bank_name', 'op' => 'EQUAL', 'value' => $blood_bank];
}
if ($blood_group !== '') {
    $filters[] = ['field' => 'blood_group', 'op' => 'EQUAL', 'value' => $blood_group];
}
if ($component !== '') {
    $filters[] = ['field' => 'component', 'op' => 'EQUAL', 'value' => $component];
}

require_once __DIR__ . '/inventory_helper.php';
refresh_stock_dates($db); // keeps batch dates current (runs once a day)

$rows = [];
try {
    $rows = $db->getWhere('blood_stock', $filters);
    // Hide stock that has already expired (rows without an expiry date are kept).
    $todayDate = date('Y-m-d');
    $rows = array_values(array_filter($rows, function ($r) use ($todayDate) {
        return empty($r['expiry_date']) || $r['expiry_date'] >= $todayDate;
    }));
    // Firestore only sorts by the field passed to orderBy, so do the
    // remaining tie-break sort (blood_bank_name, blood_group) in PHP.
    usort($rows, function ($a, $b) {
        return [$a['district'] ?? '', $a['blood_bank_name'] ?? '', $a['blood_group'] ?? '']
            <=> [$b['district'] ?? '', $b['blood_bank_name'] ?? '', $b['blood_group'] ?? ''];
    });
} catch (Exception $e) {
    error_log("Find blood query failed: " . $e->getMessage());
}

$total = count($rows);
// Note: filtering on multiple fields together with orderBy needs a
// Firestore composite index — the first time this runs with filters +
// sorting, Firestore's error message gives you a direct link to create it.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Blood</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f3f3;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 95%;
            margin: auto;
        }

        header {
            background: #7a1538;
            padding: 18px 0;
            color: white;
            position: relative;
            z-index: 9999;
        }

        .nav {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-direction: column;
                gap: 12px;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            width: 42px;
            margin-right: 10px;
        }

        .menu {
            list-style: none;
            display: flex;
            gap: 28px;
            margin: 0;
            padding: 0;
        }

        .menu li {
            position: relative;
        }

        .menu li a {
            text-decoration: none;
            color: white;
            font-weight: bold;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 35px;
            left: 0;
            background: #f2f2f2;
            list-style: none;
            padding: 0;
            margin: 0;
            width: 180px;
            z-index: 10000;
        }

        .dropdown-menu li {
            border-bottom: 1px solid #ddd;
        }

        .dropdown-menu li a {
            color: #7a1538;
            padding: 10px;
            display: block;
        }

        .dropdown-menu li a:hover {
            background: #ddd;
        }

        .dropdown-menu.show {
            display: block;
        }

        .page-highlight {
            padding: 22px 0 8px;
        }

        .page-highlight .highlight-box {
            background: linear-gradient(135deg, #7a1538, #a73b66);
            color: #fff;
            border-radius: 14px;
            padding: 18px 22px;
            box-shadow: 0 8px 20px rgba(122,21,56,0.22);
        }

        .page-highlight h1 {
            font-size: 28px;
            margin-bottom: 6px;
        }

        .page-highlight p {
            font-size: 16px;
            opacity: 0.95;
        }

        .search-box,
        .results-box {
            background: #fff;
            margin-top: 28px;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.08);
        }

        .search-box h2,
        .results-box h3 {
            color: #7a1538;
            margin-top: 0;
        }

        .form-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .form-row input,
        .form-row select {
            flex: 1;
            min-width: 220px;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .search-btn,
        .reset-btn {
            border: none;
            padding: 11px 22px;
            border-radius: 22px;
            cursor: pointer;
            color: white;
        }

        .search-btn {
            background: #009688;
        }

        .search-btn:hover {
            background: #007d71;
        }

        .reset-btn {
            background: #7a1538;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            line-height: 22px;
        }

        .reset-btn:hover {
            background: #5f102b;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .results-header input {
            padding: 9px;
            width: 260px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .action-buttons button {
            padding: 8px 12px;
            border: none;
            background: #7a1538;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 5px;
        }

        .action-buttons button:hover {
            background: #5e102c;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        table th,
        table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        table th {
            background: #8a123d;
            color: white;
        }

        .row-request-btn {
            display: inline-block;
            background: #7a1538;
            color: #fff;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 18px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .row-request-btn:hover {
            background: #5f102b;
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 18px;
        }

        .table-footer {
            margin-top: 10px;
            font-size: 14px;
            color: #444;
        }

        .filter-note {
            font-size: 14px;
            color: #555;
            margin-top: -5px;
            margin-bottom: 15px;
        }

        footer {
            background: #7a1538;
            color: white;
            padding: 45px 0;
            margin-top: 45px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-left {
            width: 40%;
            min-width: 260px;
        }

        .footer-links ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 20px;
        }

        @media(max-width: 768px) {
            .nav {
                flex-direction: column;
                gap: 15px;
            }

            .menu {
                flex-wrap: wrap;
                justify-content: center;
            }

            .footer-content {
                flex-direction: column;
            }

            .footer-left {
                width: 100%;
            }
        }
        .footer-logo{ width:64px; height:64px; object-fit:cover; border-radius:50%; background:#fff; padding:3px; margin-bottom:12px; display:block; }
    </style>
</head>
<body>

<header>
    <div class="container nav">
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
                <a href="#" class="login-toggle">LOGIN ▼</a>
                <ul class="dropdown-menu">
                    <li><a href="donor.php">Donor Login</a></li>
                    <li><a href="request.php">Request</a></li>
                    <li><a href="find.php">Find Blood</a></li>
                </ul>
            </li>
        </ul>
    </div>
</header>

<section class="page-highlight">
    <div class="container">
        <div class="highlight-box">
            <h1>Find Blood Availability</h1>
            <p>Search blood stock by district, bank, group, and component in one place.</p>
        </div>
    </div>
</section>

<div class="container">
    <div class="search-box">
        <h2>Search Blood Availability</h2>
        <p class="filter-note">All records are shown.</p>

        <form method="GET" action="">
            <div class="form-row">
                <input type="text" value="Karnataka" readonly>

                <select name="district">
                    <option value="">Select District</option>
                    <option value="Bangalore" <?php if($district=="Bangalore") echo "selected"; ?>>Bangalore</option>
                    <option value="Bangalore South" <?php if($district=="Bangalore South") echo "selected"; ?>>Bangalore South</option>
                    <option value="Bangalore North" <?php if($district=="Bangalore North") echo "selected"; ?>>Bangalore North</option>
                </select>

                <select name="blood_bank">
                    <option value="">Select Blood Bank</option>
                    <option value="LifeCare Blood Bank" <?php if($blood_bank=="LifeCare Blood Bank") echo "selected"; ?>>LifeCare Blood Bank</option>
                    <option value="Red Cross Blood Bank" <?php if($blood_bank=="Red Cross Blood Bank") echo "selected"; ?>>Red Cross Blood Bank</option>
                    <option value="Narayana Blood Centre" <?php if($blood_bank=="Narayana Blood Centre") echo "selected"; ?>>Narayana Blood Centre</option>
                    <option value="Victoria Hospital Blood Bank" <?php if($blood_bank=="Victoria Hospital Blood Bank") echo "selected"; ?>>Victoria Hospital Blood Bank</option>
                </select>
            </div>

            <div class="form-row">
                <select name="blood_group">
                    <option value="">Select Blood Group</option>
                    <option value="A+" <?php if($blood_group=="A+") echo "selected"; ?>>A+</option>
                    <option value="A-" <?php if($blood_group=="A-") echo "selected"; ?>>A-</option>
                    <option value="B+" <?php if($blood_group=="B+") echo "selected"; ?>>B+</option>
                    <option value="B-" <?php if($blood_group=="B-") echo "selected"; ?>>B-</option>
                    <option value="O+" <?php if($blood_group=="O+") echo "selected"; ?>>O+</option>
                    <option value="O-" <?php if($blood_group=="O-") echo "selected"; ?>>O-</option>
                    <option value="AB+" <?php if($blood_group=="AB+") echo "selected"; ?>>AB+</option>
                    <option value="AB-" <?php if($blood_group=="AB-") echo "selected"; ?>>AB-</option>
                </select>

                <select name="component">
                    <option value="">Select Component</option>
                    <option value="Whole Blood" <?php if($component=="Whole Blood") echo "selected"; ?>>Whole Blood</option>
                    <option value="Plasma" <?php if($component=="Plasma") echo "selected"; ?>>Plasma</option>
                    <option value="Platelets" <?php if($component=="Platelets") echo "selected"; ?>>Platelets</option>
                    <option value="RBC" <?php if($component=="RBC") echo "selected"; ?>>RBC</option>
                </select>

                <button type="submit" class="search-btn">Search</button>
                <a href="find.php" class="reset-btn">Reset</a>
            </div>
        </form>
    </div>

    <div class="results-box">
        <div class="results-header">
            <h3>Search Results</h3>
            <input type="text" id="tableSearch" placeholder="Search in table...">

            <div class="action-buttons">
                <button type="button" onclick="copyTable()">Copy</button>
                <button type="button" onclick="downloadCSV()">CSV</button>
                <button type="button" onclick="window.print()">Print</button>
            </div>
        </div>

        <table id="bloodTable">
            <thead>
                <tr>
                    <th>S.No.</th>
                    <th>Blood Bank</th>
                    <th>State</th>
                    <th>District</th>
                    <th>City</th>
                    <th>Blood Group</th>
                    <th>Component</th>
                    <th>Units Available</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($total > 0) {
                    $sn = 1;
                    foreach ($rows as $row) {
                        $requestLink = "request.php?bloodgroup=" . rawurlencode((string)$row['blood_group'])
                            . "&component=" . rawurlencode((string)$row['component'])
                            . "&blood_bank=" . rawurlencode((string)$row['blood_bank_name']);
                        echo "<tr>
                                <td>".($sn++)."</td>
                                <td>".htmlspecialchars($row['blood_bank_name'], ENT_QUOTES, 'UTF-8')."</td>
                                <td>".htmlspecialchars($row['state'], ENT_QUOTES, 'UTF-8')."</td>
                                <td>".htmlspecialchars($row['district'], ENT_QUOTES, 'UTF-8')."</td>
                                <td>".htmlspecialchars($row['city'], ENT_QUOTES, 'UTF-8')."</td>
                                <td>".htmlspecialchars($row['blood_group'], ENT_QUOTES, 'UTF-8')."</td>
                                <td>".htmlspecialchars($row['component'], ENT_QUOTES, 'UTF-8')."</td>
                                <td>".htmlspecialchars((string)$row['units_available'], ENT_QUOTES, 'UTF-8')."</td>
                                <td><a class='row-request-btn' href='".htmlspecialchars($requestLink, ENT_QUOTES, 'UTF-8')."'>Request Blood</a></td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='9' class='no-data'>No matching records found</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="table-footer">
            <?php
            if ($total > 0) {
                echo "Showing 1 to $total of $total entries";
            } else {
                echo "Showing 0 to 0 of 0 entries";
            }
            ?>
        </div>
    </div>
</div>

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
            </ul>
        </div>

        <div class="footer-links">
            <h3>Other Links</h3>
            <ul>
                <li><a href="camp.php">Camp</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        &copy; <?php echo date("Y"); ?> Lifeline Blood Bank. All rights reserved.
    </div>
</footer>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const toggle = document.querySelector(".login-toggle");
    const menu = document.querySelector(".dropdown-menu");

    if (toggle && menu) {
        toggle.addEventListener("click", function (e) {
            e.preventDefault();
            menu.classList.toggle("show");
        });
    }
});

document.getElementById("tableSearch").addEventListener("keyup", function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll("#bloodTable tbody tr");

    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});

function copyTable() {
    let table = document.getElementById("bloodTable").innerText;
    navigator.clipboard.writeText(table);
    alert("Table copied successfully!");
}

function downloadCSV() {
    let rows = document.querySelectorAll("#bloodTable tr");
    let csv = [];

    rows.forEach(row => {
        let cols = row.querySelectorAll("td, th");
        let data = [];
        cols.forEach(col => data.push('"' + col.innerText.replace(/"/g, '""') + '"'));
        csv.push(data.join(","));
    });

    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = "blood_stock.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>

</body>
</html>
