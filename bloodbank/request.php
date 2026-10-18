<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$successMsg = $_SESSION['flash_success'] ?? "";
unset($_SESSION['flash_success']);
$errorMsg = "";

include('db_connect.php');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['createrequest'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errorMsg = "Invalid request. Please refresh and try again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $hospital = trim($_POST['hospital'] ?? '');
        $patient = trim($_POST['patient'] ?? '');
        $bloodgroup = trim($_POST['bloodgroup'] ?? '');
        $units = trim($_POST['units'] ?? '');
        $urgency = trim($_POST['urgency'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($phone !== '' && !preg_match('/^\d{10}$/', $phone)) {
            $errorMsg = "Phone number must be exactly 10 digits.";
        } elseif ($name && $phone && $hospital && $bloodgroup && $units && $urgency && $location) {
            try {
                $requestData = [
                    'requester_name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'patient_name' => $patient,
                    'hospital_name' => $hospital,
                    'blood_group' => $bloodgroup,
                    'units_needed' => (int)$units,
                    'urgency_level' => $urgency,
                    'location_area' => $location,
                    'message' => $message,
                    'request_status' => 'Pending',
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $db->add('blood_requests', $requestData);
                // Redirect so the form is cleared and a refresh can't resubmit it.
                $_SESSION['flash_success'] = "Thank you, $name. Your blood request has been recorded. Our team will review it shortly.";
                header('Location: request.php');
                exit();
            } catch (Exception $e) {
                error_log("Blood request insert failed: " . $e->getMessage());
                $errorMsg = "Unable to submit your request right now. Please try again later.";
            }
        } else {
            $errorMsg = "Please fill all required fields.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Blood Bank Donor Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            line-height: 1.6;
            background: #f5f5f5;
        }

        .container {
            width: 85%;
            margin: auto;
        }

        header {
            background: #7a1538;
            padding: 20px 0;
            color: white;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            width: 40px;
            margin-right: 10px;
        }

        .menu {
            list-style: none;
            display: flex;
            gap: 30px;
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

        .success {
            max-width: 600px;
            margin: 20px auto 0;
            padding: 10px 15px;
            border-radius: 5px;
            background: #c8e6c9;
            color: #256029;
            border: 1px solid #2e7d32;
            text-align: center;
        }

        .error {
            max-width: 600px;
            margin: 20px auto 0;
            padding: 10px 15px;
            border-radius: 5px;
            background: #ffcdd2;
            color: #b71c1c;
            border: 1px solid #b71c1c;
            text-align: center;
        }

        .request-section {
            padding: 60px 0;
        }

        .request-layout {
            display: flex;
            gap: 40px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .request-form,
        .request-info {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 30px;
            flex: 1;
            min-width: 300px;
        }

        .request-form h1 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #7a1538;
        }

        .request-form p {
            margin-bottom: 20px;
            font-size: 18px;
            color: #555;
        }

        .request-form .row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .request-form .row div {
            flex: 1;
            min-width: 200px;
        }

        label {
            display: block;
            margin-top: 10px;
            margin-bottom: 4px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn-submit {
            margin-top: 15px;
            padding: 10px 25px;
            background: #d32f2f;
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn-submit:hover {
            background: #b71c1c;
        }

        .request-info h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #7a1538;
        }

        .request-info p {
            font-size: 17px;
            color: #555;
            margin-bottom: 10px;
        }

        .request-info ul {
            list-style: none;
            padding: 0;
            font-size: 16px;
            color: #555;
        }

        .request-info ul li {
            margin-bottom: 6px;
        }

        .emergency-highlight {
            font-weight: bold;
            color: #d32f2f;
        }

        footer {
            background: #7a1538;
            color: white;
            padding: 50px 0;
            margin-top: 50px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
        }

        .footer-left {
            width: 40%;
            font-size: 20px;
        }

        .footer-links ul {
            list-style: none;
            font-size: 22px;
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

        @media(max-width:768px) {
            .container {
                width: 92%;
            }

            .nav {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .menu {
                width: 100%;
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .menu li {
                width: 100%;
            }

            .menu li a {
                display: block;
                padding: 6px 0;
            }

            .dropdown-menu {
                position: static;
                width: 100%;
                margin-top: 8px;
                box-shadow: none;
            }

            .request-layout,
            .footer-content {
                flex-direction: column;
                gap: 20px;
            }

            .request-form,
            .request-info {
                min-width: 0;
                width: 100%;
                padding: 22px;
            }

            .request-form .row {
                flex-direction: column;
            }

            .request-form .row div {
                min-width: 0;
                width: 100%;
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
                <h2>Lifeline Blood bank</h2>
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
        </div>
    </header>

    <section class="page-highlight">
        <div class="container">
            <div class="highlight-box">
                <h1>Emergency Blood Request</h1>
                <p>Submit urgent blood needs quickly and our team will coordinate with priority.</p>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/success_popup.php'; ?>

    <?php if (!empty($errorMsg)) : ?>
        <div class="error"><?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section class="request-section">
        <div class="container request-layout">

            <div class="request-form">
                <h1>Emergency Blood Request</h1>
                <p>Please fill the details below carefully. Our team will review your request and contact you as soon as possible. For life-threatening emergencies, also call the blood bank directly.</p>

                <form action="request.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="row">
                        <div>
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div>
                            <label for="phone">Mobile Number</label>
                            <input type="text" id="phone" name="phone" pattern="[0-9]{10}" maxlength="10" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')" title="Enter 10 digit mobile number" required>
                        </div>
                    </div>

                    <div class="row">
                        <div>
                            <label for="email">Email (optional)</label>
                            <input type="email" id="email" name="email">
                        </div>
                        <div>
                            <label for="patient">Patient Name (optional)</label>
                            <input type="text" id="patient" name="patient">
                        </div>
                    </div>

                    <label for="hospital">Hospital Name</label>
                    <select id="hospital" name="hospital" required>
                        <option value="">-- Select Hospital --</option>
                        <option value="LifeCare Blood Bank">LifeCare Blood Bank</option>
                        <option value="Red Cross Blood Bank">Red Cross Blood Bank</option>
                        <option value="Fortis Hospital">Fortis Hospital</option>
                        <option value="Apollo Hospitals">Apollo Hospitals</option>
                        <option value="Narayana Blood Centre">Narayana Blood Centre</option>
                        <option value="Victoria Hospital Blood Bank">Victoria Hospital Blood Bank</option>
                    </select>

                    <div class="row">
                        <div>
                            <label for="bloodgroup">Blood Group</label>
                            <select id="bloodgroup" name="bloodgroup" required>
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

                        <div>
                            <label for="units">Units Needed</label>
                            <input type="number" id="units" name="units" min="1" required>
                        </div>
                    </div>

                    <div class="row">
                        <div>
                            <label for="urgency">Urgency Level</label>
                            <select id="urgency" name="urgency" required>
                                <option value="">-- Select --</option>
                                <option value="Within 24 hours">Within 24 hours</option>
                                <option value="Within 12 hours">Within 12 hours</option>
                                <option value="Immediately / Emergency">Immediately / Emergency</option>
                            </select>
                        </div>

                        <div>
                            <label for="location">Location Area</label>
                            <input type="text" id="location" name="location" placeholder="e.g. Jayanagar, Bangalore" required>
                        </div>
                    </div>

                    <label for="message">Additional Details (optional)</label>
                    <textarea id="message" name="message" placeholder="Diagnosis, doctor name, any special instructions"></textarea>

                    <button type="submit" name="createrequest" class="btn-submit">Create Request</button>
                </form>
            </div>

            <div class="request-info">
                <h2>Important Notes</h2>
                <p><span class="emergency-highlight">For critical emergencies</span>, please do not rely only on the online form. Call the blood bank for faster coordination and visit the nearest hospital blood bank.</p>
                <ul>
                    <li>Keep patient's blood group report ready, if available.</li>
                    <li>Ensure a doctor's requisition is obtained from the hospital.</li>
                    <li>Provide accurate contact details so our staff can reach you quickly.</li>
                </ul>

                <h2>Contact for Emergency</h2>
                <p><span class="emergency-highlight">24x7 Emergency Phone:</span> +91 99001 63000 / +91 99001 53000</p>
                <p><span class="emergency-highlight">Emergency Email:</span> emergency@lifeline.com</p>

                <h2>Who can raise a request?</h2>
                <ul>
                    <li>Patient family members or friends.</li>
                    <li>Hospital staff and doctors.</li>
                    <li>NGOs or volunteers supporting the patient.</li>
                </ul>
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
    </script>

</body>
</html>
