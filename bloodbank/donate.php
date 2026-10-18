<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errorMsg = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['register'])) {
    include('db_connect.php');

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errorMsg = "Invalid request. Please refresh and try again.";
    } else {
        $name = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
        $phone = trim($_POST['phone']);
        $blood = trim($_POST['blood']);
        $age = trim($_POST['age']);
        $weight = trim($_POST['weight']);
        $lastdonation = trim($_POST['lastdonation']);
        $address = trim($_POST['address']);

        try {
            $existing = $db->getOneWhere('donors', 'email', $email);

            if (!preg_match('/^\d{10}$/', $phone)) {
                $errorMsg = "Phone number must be exactly 10 digits.";
            } elseif ($existing) {
                $errorMsg = "Email already registered!";
            } else {
                $db->add('donors', [
                    'username' => $name,
                    'email' => $email,
                    'password' => $password,
                    'phone' => $phone,
                    'bloodgroup' => $blood,
                    'age' => (int)$age,
                    'weight' => (int)$weight,
                    'lastdonation' => $lastdonation,
                    'address' => $address,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                header("Location: donor.php");
                exit();
            }
        } catch (Exception $e) {
            error_log("Donor registration failed: " . $e->getMessage());
            $errorMsg = "Unable to register right now. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Blood Bank Donor Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            line-height: 1.6;
            background: #fdfdfd;
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

        .menu li a {
            text-decoration: none;
            color: white;
            font-weight: bold;
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

        /* FLEX LAYOUT FOR CONTENT */
        .main-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 30px;
            margin-bottom: 50px;
            gap: 40px;
        }

        /* LEFT NOTE BOX */
        .info-note {
            flex: 1;
            background: linear-gradient(135deg, #fff8f9 0%, #ffffff 100%);
            padding: 30px;
            border-radius: 15px;
            border-left: 8px solid #7a1538;
            box-shadow: 0px 8px 25px rgba(122, 21, 56, 0.15);
        }

        .info-note h2 {
            color: #7a1538;
            margin-bottom: 25px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-note h2::before {
            content: "⚠️";
            font-size: 28px;
        }

        .requirement-section {
            margin-bottom: 25px;
        }

        .section-title {
            color: #7a1538;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #ffc0cb;
        }

        .info-note ul {
            list-style: none;
        }

        .info-note ul li {
            margin-bottom: 12px;
            padding-left: 28px;
            position: relative;
            font-size: 15px;
            color: #333;
            line-height: 1.5;
        }

        .info-note ul li::before {
            content: "✓";
            color: #27ae60;
            font-weight: bold;
            display: inline-block; 
            width: 1.2em;
            margin-left: -1.2em;
            font-size: 1.3em;
            vertical-align: middle;
        }

        .info-note ul li.warning {
            background: #fff3cd;
            padding: 12px 15px 12px 28px;
            border-radius: 8px;
            border-left: 3px solid #ff6b6b;
            margin-bottom: 12px;
            color: #d63031;
            font-weight: 500;
        }

        .info-note ul li.warning::before {
            content: "⚠";
            color: #ff6b6b;
            margin-left: -1.2em;
        }

        .info-note ul li.critical {
            background: #ffe0e0;
            padding: 12px 15px 12px 28px;
            border-radius: 8px;
            border-left: 3px solid #e74c3c;
            margin-bottom: 12px;
            color: #c0392b;
            font-weight: 600;
        }

        .info-note ul li.critical::before {
            content: "🔴";
            margin-left: -1.2em;
        }

        .info-note .urgent-box {
            background: #f8d7da;
            border: 2px solid #e74c3c;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            color: #721c24;
            font-weight: 500;
            text-align: center;
        }

        /* RIGHT REGISTER BOX */
        .register-box {
            flex: 1;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 0px 15px rgba(0,0,0,0.1);
        }

        .register-box h2 {
            text-align: center;
            color: #7a1538;
            margin-bottom: 25px;
            font-size: 26px;
        }

        .label {
            font-weight: bold;
            color: #333;
            margin-top: 15px;
            display: block;
        }

        .input-box, select, textarea {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #cdd4e0;
            margin-top: 5px;
            background: #f1f5ff;
            font-size: 15px;
        }

        textarea {
            resize: none;
            height: 80px;
        }

        .btn {
            width: 100%;
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 14px;
            margin-top: 25px;
            border-radius: 25px;
            font-size: 17px;
            cursor: pointer;
        }

        .btn:hover {
            background: #ff4a4a;
        }

        .login-btn {
            width: 100%;
            margin-top: 20px;
            text-align: center;
        }

        .login-btn a {
            text-decoration: none;
            background: #5b73ff;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: bold;
            display: inline-block;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-left: 5px solid #dc3545;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>

<header>
    <div class="container nav">
        <div class="logo">
            <img decoding="async" src="assets/img/logo.jpg" alt="logo">
            <h2>Lifeline Blood Bank</h2>
        </div>
        <ul class="menu">
            <li><a href="index.php">HOME</a></li>
            <li><a href="about.php">ABOUT US</a></li>
            <li><a href="contact.php">CONTACT US</a></li>
            <li><a href="camp.php">CAMP</a></li>
        </ul>
    </div>
</header>

<section class="page-highlight">
    <div class="container">
        <div class="highlight-box">
            <h1>Donor Registration</h1>
            <p>Join Lifeline as a volunteer donor and become part of our life-saving network.</p>
        </div>
    </div>
</section>

<div class="container">
    <div class="main-content">
        <div class="info-note">
            <h2>NOTE BEFORE DONATION</h2>
            
            <div class="requirement-section">
                <div class="section-title">📋 Eligibility Requirements</div>
                <ul>
                    <li>Age must be between 18 and 65 years</li>
                    <li>Minimum weight of 50 kg (110 lbs)</li>
                    <li>In generally good health on donation day</li>
                    <li>Normal blood pressure (systolic 90-180, diastolic 50-100)</li>
                    <li>Hemoglobin level within normal range as checked by staff</li>
                </ul>
            </div>

            <div class="requirement-section">
                <div class="section-title">⚠️ Medical Conditions & Restrictions</div>
                <ul>
                    <li class="warning">Recent vaccinations (may have waiting period)</li>
                    <li class="warning">Must NOT be on any antibiotics or blood-thinning medications</li>
                    <li class="warning">Not currently pregnant or breastfeeding</li>
                    <li class="warning">Recent surgeries, tattoos, or piercings (within 6-12 months)</li>
                    <li class="warning">Avoid smoking and alcohol 24 before donation</li>
                    <li class="warning">Do not donate if you have cold, flu, or fever</li>
                    <li class="critical">Do not have tattoos, piercings, or recent blood transfusions without disclosure</li>
                </ul>
            </div>

            <div class="requirement-section">
                <div class="section-title">✅ Preparation Guidelines</div>
                <ul>
                    <li>Eat a healthy meal 2-3 hours before donation</li>
                    <li>Drink plenty of water to stay hydrated (8-10 glasses)</li>
                    <li>Wear comfortable, loose-fitting clothing</li>
                    <li>Bring a valid photo ID (Aadhar, Passport, Driving License, PAN)</li>
                </ul>
            </div>

            <div class="requirement-section">
                <div class="section-title">⏱️ Donation Duration & Recovery</div>
                <ul>
                    <li>Donation process takes approximately 8-10 minutes</li>
                    <li>Entire visit typically 1-2 hours (including screening & recovery)</li>
                    <li>Rest for 10-15 minutes after donation</li>
                    <li>Can donate again after 56 days (8 weeks)</li>
                </ul>
            </div>

            <div class="urgent-box">
                ⚠️ <strong>IMPORTANT:</strong> If you have any doubt about your eligibility, please consult with our medical staff before donation. Your health and safety are our priority!
            </div>
        </div>

        <div class="register-box">
            <h2>Register New Donor</h2>

            <?php if ($errorMsg != "") { ?>
                <div class="error"><?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php } ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                
                <label class="label">Username</label>
                <input type="text" name="username" class="input-box" required>

                <label class="label">Email</label>
                <input type="email" name="email" class="input-box" required>

                <label class="label">Password</label>
                <input type="password" name="password" class="input-box" required>

                <label class="label">Phone</label>
                <input type="text" name="phone" class="input-box" placeholder="10-digit number" pattern="\d{10}" maxlength="10" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')" title="Enter 10 digit mobile number" required>

                <label class="label">Blood Group</label>
                <select name="blood" required>
                    <option value="">Select</option>
                    <option value="O+">O+</option>
                    <option value="A+">A+</option>
                    <option value="B+">B+</option>
                    <option value="AB+">AB+</option>
                    <option value="O-">O-</option>
                    <option value="A-">A-</option>
                    <option value="B-">B-</option>
                    <option value="AB-">AB-</option>
                </select>

                <label class="label">Age</label>
                <input type="number" name="age" class="input-box" 
                    min="18" 
                    oninvalid="this.setCustomValidity('You must be atleast 18 years old to donate blood')" 
                    oninput="this.setCustomValidity('')" 
                    required>

                <label class="label">Weight (kg)</label>
                <input type="number" name="weight" class="input-box" 
                    min="50" 
                    oninvalid="this.setCustomValidity('You must be atleast 50kg to donate blood')" 
                    oninput="this.setCustomValidity('')" 
                    required>

                <label class="label">Last Donated Date</label>
                <input type="date" name="lastdonation" class="input-box" required>

                <label class="label">Address</label>
                <textarea name="address" required></textarea>

                <button type="submit" name="register" class="btn">Register</button>

                <div class="login-btn">
                    <a href="donor.php">Already have account? Login</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>