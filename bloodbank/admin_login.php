<?php
session_start();
include('db_connect.php');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errorMsg = "Invalid request. Please refresh and try again.";
    } else {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $password = trim($_POST['password'] ?? '');

        $maxAttempts = 5;
        $lockSeconds = 15 * 60;
        $attemptId = 'ip_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        try {
            $attempts = $db->getById('login_attempts', $attemptId);
            $lockedUntil = (int)($attempts['locked_until'] ?? 0);

            if ($lockedUntil > time()) {
                $minutes = (int)ceil(($lockedUntil - time()) / 60);
                $errorMsg = "Too many failed attempts. Try again in {$minutes} minute(s).";
            } else {
                // Admin accounts live in Firestore ('admins'), passwords are hashed.
                $admin = ($username !== '' && preg_match('/^[a-z0-9_.-]{1,40}$/', $username))
                    ? $db->getById('admins', 'admin_' . $username)
                    : null;

                // First run only: create the default admin account from the ADMIN_BOOTSTRAP_PASSWORD env var (if set).
                // Change the password afterwards (Dashboard > Change Password).
                $bootstrap = (string)getenv('ADMIN_BOOTSTRAP_PASSWORD');
                if (!$admin && $username === 'admin' && $bootstrap !== '' && hash_equals($bootstrap, $password)
                    && !$db->getById('admins', 'admin_admin')) {
                    $db->set('admins', 'admin_admin', [
                        'username' => 'admin',
                        'display_name' => 'Administrator',
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $admin = $db->getById('admins', 'admin_admin');
                }

                if ($admin && password_verify($password, $admin['password'] ?? '')) {
                    if ($attempts) {
                        $db->delete('login_attempts', $attemptId);
                    }
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['display_name'] ?? 'Administrator';
                    header("Location: admin_dashboard.php");
                    exit();
                }

                $count = (int)($attempts['count'] ?? 0) + 1;
                $newLock = $count >= $maxAttempts ? time() + $lockSeconds : 0;
                $db->set('login_attempts', $attemptId, [
                    'count' => $newLock ? 0 : $count,
                    'locked_until' => $newLock,
                    'last_attempt' => date('Y-m-d H:i:s'),
                ]);
                $errorMsg = $newLock
                    ? "Too many failed attempts. Try again in 15 minutes."
                    : "Invalid username or password. (" . ($maxAttempts - $count) . " attempt(s) left)";
            }
        } catch (Exception $e) {
            error_log("Admin login failed: " . $e->getMessage());
            $errorMsg = "Login is temporarily unavailable. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Blood Bank Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f9f2f5 0%, #f3dce6 100%);
            min-height: 100vh;
            padding: 0;
        }

        .container {
            width: 85%;
            margin: auto;
        }

        header {
            background: #7a1538;
            padding: 20px 0;
            color: white;
            position: relative;
            z-index: 9999;
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
            width: 46px;
            margin-right: 10px;
            border-radius: 0;
            border: 0;
            padding: 0;
            background: transparent;
        }

        .logo h2 {
            font-size: 34px;
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

        .admin-login-main {
            min-height: calc(100vh - 92px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px 20px 34px;
            flex-direction: column;
            gap: 14px;
        }

        .page-highlight {
            width: 100%;
        }

        .page-highlight .highlight-box {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #fff 0%, #fff7fa 55%, #fdf1f5 100%);
            color: #4a2431;
            border-radius: 20px;
            padding: 14px 16px;
            box-shadow: 0 18px 36px rgba(122, 21, 56, 0.14);
            border: 1px solid #ead9e0;
            border-left: 10px solid #7a1538;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 14px;
            align-items: stretch;
        }

        .page-highlight .highlight-box::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at top right, rgba(122, 21, 56, 0.1), transparent 36%),
                repeating-linear-gradient(-35deg, rgba(122, 21, 56, 0.03) 0, rgba(122, 21, 56, 0.03) 10px, rgba(122, 21, 56, 0) 10px, rgba(122, 21, 56, 0) 24px);
            pointer-events: none;
        }

        .highlight-left,
        .highlight-meta {
            position: relative;
            z-index: 1;
        }

        .highlight-kicker {
            display: inline-block;
            background: linear-gradient(135deg, #7a1538, #c02455);
            color: white;
            padding: 5px 11px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 8px;
            letter-spacing: 0.8px;
        }

        .highlight-left h1 {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 22px;
            margin-bottom: 8px;
            letter-spacing: 0.2px;
            color: #7a1538;
            line-height: 1.15;
        }

        .highlight-left p {
            font-size: 14px;
            color: #6f5b63;
            max-width: 620px;
        }

        .highlight-actions {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 7px;
        }

        .quick-info {
            background: #fff;
            border: 1px solid #ead3db;
            border-radius: 12px;
            padding: 8px 10px;
            box-shadow: 0 8px 18px rgba(122, 21, 56, 0.06);
        }

        .quick-info strong {
            display: block;
            color: #7a1538;
            font-size: 12px;
            line-height: 1.2;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 2px;
        }

        .quick-info span {
            color: #6f5b63;
            font-size: 13px;
        }

        .highlight-meta {
            display: grid;
            gap: 8px;
            align-content: start;
        }

        .highlight-chip {
            background: linear-gradient(135deg, #8a123d, #b11d4a);
            color: #fff;
            border-radius: 16px;
            padding: 12px 14px;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12);
        }

        .highlight-chip strong {
            display: block;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .highlight-chip span {
            display: block;
            font-size: 13px;
            opacity: 0.95;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 18px 36px rgba(122, 21, 56, 0.14);
            border: 1px solid #ead9e0;
            border-left: 8px solid #7a1538;
            padding: 40px;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-section h1 {
            color: #7a1538;
            font-size: 30px;
            margin-bottom: 8px;
        }

        .logo-section img {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            border: 2px solid #ead9e0;
            padding: 6px;
            margin-bottom: 10px;
            background: #fff;
        }

        .logo-section p {
            color: #888;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #7a1538;
            box-shadow: 0 0 0 3px rgba(122, 21, 56, 0.1);
        }

        .error-msg {
            background: #ffe7e7;
            color: #9e1f1f;
            border: 1px solid #e2a0a0;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #7a1538, #c02455);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(122, 21, 56, 0.2);
        }

        .demo-credentials {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 13px;
            color: #666;
        }

        .demo-credentials strong {
            display: block;
            color: #333;
            margin-bottom: 6px;
        }

        .demo-credentials code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }

        @media(max-width: 900px) {
            .nav {
                flex-direction: column;
                gap: 12px;
            }

            .menu {
                gap: 16px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .logo h2 {
                font-size: 34px;
            }

            .admin-login-main {
                min-height: auto;
                padding: 26px 14px;
            }

            .page-highlight .highlight-box {
                grid-template-columns: 1fr;
            }

            .highlight-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="container nav">
        <div class="logo">
            <img decoding="async" src="assets/img/logo.jpg" alt="logo" border="0">
            <h2>Lifeline Blood bank </h2>
        </div>

        <ul class="menu">
            <li><a href="index.php">HOME</a></li>
            <li><a href="about.php">ABOUT US</a></li>
            <li><a href="contact.php">CONTACT US</a></li>
            <li><a href="camp.php">CAMP</a></li>
            <li><a href="admin_login.php" class="active">ADMIN</a></li>

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

<main class="admin-login-main">

<section class="page-highlight">
    <div class="container">
        <div class="highlight-box">
            <div class="highlight-left">
                <span class="highlight-kicker">ADMIN ACCESS</span>
                <h1>Control Center for Daily Operations</h1>
                <p>Track donors, requests, and stock updates quickly from one dashboard.</p>

            </div>
            <div class="highlight-meta">
                <div class="highlight-chip">
                    <strong>Live Inventory Watch</strong>
                    <span>Monitor low-stock groups and respond without delay</span>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="login-container">
    <div class="logo-section">
        <img decoding="async" src="assets/img/logo.jpg" alt="Lifeline Blood Bank Logo" border="0">
        <h1>Lifeline Blood Bank</h1>
        <p>Admin Portal</p>
    </div>

    <?php if (!empty($errorMsg)) : ?>
        <div class="error-msg"><?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="admin_login.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" name="login" class="login-btn">Sign In</button>
    </form>
</div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var loginToggle = document.querySelector('.login-toggle');
    var dropdownMenu = document.querySelector('.dropdown-menu');

    if (loginToggle && dropdownMenu) {
        loginToggle.addEventListener('click', function (e) {
            e.preventDefault();
            dropdownMenu.classList.toggle('show');
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.dropdown')) {
                dropdownMenu.classList.remove('show');
            }
        });
    }
});
</script>

</body>
</html>
