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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errorMsg = "Invalid request. Please refresh and try again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($phone !== '' && !preg_match('/^\d{10}$/', $phone)) {
            $errorMsg = "Phone number must be exactly 10 digits.";
        } elseif ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || (function_exists('checkdnsrr') && !checkdnsrr(substr(strrchr($email, '@'), 1), 'MX') && !checkdnsrr(substr(strrchr($email, '@'), 1), 'A')))) {
            $errorMsg = "Please enter a valid email address (check the spelling, e.g. gmail.com).";
        } elseif ($name && $email && $phone && $subject && $message) {
            try {
                $db->add('contact_messages', [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'subject' => $subject,
                    'message' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                require_once __DIR__ . '/mailer.php';
                mail_contact_received(['name' => $name, 'email' => $email, 'phone' => $phone, 'subject' => $subject, 'message' => $message]);
                // Redirect so the form is cleared and a refresh can't resubmit it.
                $_SESSION['flash_success'] = "Thank you, $name. Your message has been received. Our team will contact you soon.";
                header('Location: contact.php');
                exit();
            } catch (Exception $e) {
                error_log("Contact message insert failed: " . $e->getMessage());
                $errorMsg = "Unable to submit your message right now. Please try again later.";
            }
        } else {
            $errorMsg = "Please fill all fields.";
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

        .menu li a.active {
            border-bottom: 2px solid #ffd5e3;
            padding-bottom: 2px;
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

        .success {
            max-width: 500px;
            margin: 20px auto 0;
            padding: 10px 15px;
            border-radius: 5px;
            background: #c8e6c9;
            color: #256029;
            border: 1px solid #2e7d32;
            text-align: center;
        }

        .error {
            max-width: 500px;
            margin: 20px auto 0;
            padding: 10px 15px;
            border-radius: 5px;
            background: #ffcdd2;
            color: #b71c1c;
            border: 1px solid #b71c1c;
            text-align: center;
        }

        .contact-section {
            padding: 60px 0;
        }

        .contact-hero {
            background: linear-gradient(135deg, #7a1538, #9a2a54);
            color: white;
            border-radius: 12px;
            padding: 26px;
            margin-bottom: 24px;
            box-shadow: 0 6px 20px rgba(122,21,56,0.22);
        }

        .contact-hero h1 {
            font-size: 31px;
            margin-bottom: 8px;
        }

        .contact-hero p {
            max-width: 900px;
            opacity: 0.95;
        }

        .contact-grid {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .contact-heading {
            font-size: 32px;
            margin-bottom: 10px;
            color: #7a1538;
        }

        .card p {
            margin-bottom: 10px;
            font-size: 16px;
            color: #555;
        }

        .card h3 {
            margin-top: 18px;
            margin-bottom: 10px;
            color: #7a1538;
        }

        .card ul {
            list-style: none;
            padding: 0;
            font-size: 17px;
            color: #555;
        }

        .card ul li {
            margin-bottom: 6px;
        }

        .highlight {
            font-weight: bold;
            color: #333;
        }

        .info-strip {
            margin-top: 12px;
            padding: 12px 14px;
            border-radius: 8px;
            background: #f9edf2;
            border-left: 4px solid #7a1538;
            color: #6d1840;
            font-weight: bold;
        }

        .office-map {
            margin-top: 14px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #ead3db;
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
            background: #fff;
        }

        .office-map iframe {
            display: block;
            width: 100%;
            height: 260px;
            border: 0;
        }

        .map-caption {
            padding: 10px 12px;
            background: #fff9fb;
            color: #7a1538;
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #ead3db;
        }

        .quick-actions {
            margin-top: 18px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .quick-action {
            border: 1px solid #e8cfd9;
            border-radius: 8px;
            padding: 12px;
            background: #fff9fb;
        }

        .quick-action span {
            display: block;
            font-size: 13px;
            color: #7a1538;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .quick-action a {
            color: #333;
            text-decoration: none;
            font-size: 15px;
        }

        .faq-section {
            margin-top: 20px;
        }

        .faq-section h2 {
            margin-bottom: 10px;
            color: #7a1538;
        }

        .form-card h2 {
            font-size: 26px;
            margin-bottom: 15px;
            color: #7a1538;
        }

        .form-note {
            margin-bottom: 14px;
            color: #666;
        }

        form label {
            display: block;
            margin-top: 10px;
            margin-bottom: 4px;
            font-weight: bold;
        }

        form input,
        form textarea,
        form select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }

        form textarea {
            resize: vertical;
            min-height: 100px;
        }

        form button {
            margin-top: 15px;
            padding: 10px 25px;
            background: #7a1538;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 16px;
        }

        form button:hover {
            background: #5c0f2a;
        }

        .faq-item {
            margin-bottom: 10px;
        }

        .faq-item strong {
            display: block;
            margin-bottom: 4px;
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

            .info-grid {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 22px;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .contact-hero {
                padding: 20px;
            }

            .contact-hero h1 {
                font-size: 25px;
            }

            .footer-content {
                flex-direction: column;
                gap: 20px;
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
                <li><a href="contact.php" class="active">CONTACT US</a></li>
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

    <?php $successNote = 'A confirmation email is on its way.'; include __DIR__ . '/success_popup.php'; ?>

    <?php if (!empty($errorMsg)) : ?>
        <div class="error"><?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section class="contact-section">
        <div class="container">

            <div class="contact-hero">
                <h1>Contact Lifeline Blood Bank</h1>
                <p>Need blood support, donor assistance, or help organizing a camp? Reach out to our team and we will guide you as quickly as possible.</p>
            </div>

            <div class="contact-grid">

                <div class="card form-card">
                    <h2>Send Us an Enquiry</h2>
                    <p class="form-note">Fill this form and our team will contact you shortly. For urgent requests, please call directly.</p>
                    <form action="contact.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

                        <label for="email">Email ID</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

                        <label for="phone">Mobile Number</label>
                        <input type="text" id="phone" name="phone" pattern="[0-9]{10}" maxlength="10" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')" title="Enter 10 digit mobile number" value="<?php echo htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

                        <label for="subject">Subject</label>
                        <select id="subject" name="subject" required>
                            <option value="">-- Select Subject --</option>
                            <option value="Blood Requirement" <?php echo (($_POST['subject'] ?? '') === 'Blood Requirement') ? 'selected' : ''; ?>>Blood Requirement</option>
                            <option value="Donate / Organize Camp" <?php echo (($_POST['subject'] ?? '') === 'Donate / Organize Camp') ? 'selected' : ''; ?>>Donate / Organize Camp</option>
                            <option value="Feedback / Suggestion" <?php echo (($_POST['subject'] ?? '') === 'Feedback / Suggestion') ? 'selected' : ''; ?>>Feedback / Suggestion</option>
                            <option value="Other" <?php echo (($_POST['subject'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>

                        <label for="message">Message</label>
                        <textarea id="message" name="message" required><?php echo htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>

                        <button type="submit" name="send">Submit</button>
                    </form>
                </div>

                <div class="info-grid">

                <div class="card">
                    <h2 class="contact-heading">Reach Our Team</h2>
                    <p>Use the details below for emergency support, general enquiries, and donation-related communication.</p>

                    <h3> Office Address</h3>
                    <p class="highlight">
                        Lifeline Blood Bank<br>
                        2nd Main Road, 1st Cross, SSM Layout<br>
                        HAL 3rd Stage, Bangalore - 560075
                    </p>

                    <h3>Phone & Email</h3>
                    <ul>
                        <li><span class="highlight">Phone:</span> +91 99001 63000 / +91 99001 53000</li>
                        <li><span class="highlight">General Enquiries:</span> lifeline@bloodbank.com</li>
                        <li><span class="highlight">Emergency 24x7:</span> emergency@lifeline.com</li>
                        <li><span class="highlight">Donation Camp:</span> donate@lifeline.com</li>
                    </ul>

                    <div class="info-strip">For urgent blood requirement, call us directly instead of waiting for email response.</div>

                    <div class="quick-actions">
                        <div class="quick-action">
                            <span>Call Support</span>
                            <a href="tel:+919900163000">+91 99001 63000</a>
                        </div>
                        <div class="quick-action">
                            <span>Email Support</span>
                            <a href="mailto:lifeline@bloodbank.com">lifeline@bloodbank.com</a>
                        </div>
                    </div>

                </div>

                <div class="card">
                    <h3>Visiting Hours</h3>
                    <ul>
                        <li>Monday - Friday: 9:00 AM to 5:00 PM</li>
                        <li>Saturday: 9:00 AM to 1:00 PM</li>
                        <li>Sunday / Public Holidays: Emergency services only</li>
                    </ul>
                    <div class="info-strip">For urgent blood requirement, call us directly instead of waiting for email response.</div>

                    <div class="office-map">
                        <iframe
                            title="Lifeline Blood Bank office location map"
                            src="https://www.google.com/maps?q=2nd%20Main%20Road,%201st%20Cross,%20SSM%20Layout,%20HAL%203rd%20Stage,%20Bangalore%20560075&output=embed"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                        <div class="map-caption">Office Location: Lifeline Blood Bank, HAL 3rd Stage, Bangalore</div>
                    </div>
                </div>

                <div class="card faq-section">
                    <h2>Before You Contact</h2>
                    <div class="faq-item">
                        <strong>For blood requests:</strong>
                        <span>Keep patient blood group, hospital name, and required units ready.</span>
                    </div>
                    <div class="faq-item">
                        <strong>For camp organization:</strong>
                        <span>Share venue location, expected donors, and preferred date in your message.</span>
                    </div>
                    <div class="faq-item">
                        <strong>Response time:</strong>
                        <span>Most enquiries are answered within one working day.</span>
                    </div>
                </div>

                </div>

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
