<?php
session_start();
include('db_connect.php');

$errorMsg = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['login'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errorMsg = "Invalid request. Please refresh and try again.";
    } else {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        $donor = $db->getOneWhere('donors', 'email', $email);

        if ($donor && password_verify($password, $donor['password'])) {
            session_regenerate_id(true);
            $_SESSION['donor_email'] = $donor['email'];
            header("Location: donor_home.php");
            exit();
        } else {
            $errorMsg = "Invalid email or password.";
        }
    } catch (Exception $e) {
        error_log("Donor login failed: " . $e->getMessage());
        $errorMsg = "Unable to login right now. Please try again later.";
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

        .error {
            width: 400px;
            margin: 20px auto 0;
            padding: 10px;
            background: #ffcdd2;
            color: #b71c1c;
            border: 1px solid #b71c1c;
            border-radius: 5px;
            text-align: center;
        }

        .login-wrapper {
            height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            width: 400px;
            background: white;
            padding: 35px 40px;
            border-radius: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            text-align: center;
        }

        .login-box h2 {
            margin-bottom: 20px;
            color: #7a1538;
        }

        .login-box input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .login-box button {
            width: 100%;
            padding: 10px;
            background: #d32f2f;
            border: none;
            color: white;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }

        .login-box button:hover {
            background: #b71c1c;
        }

        .login-links {
            margin-top: 15px;
            font-size: 14px;
        }

        .login-links a {
            text-decoration: none;
            color: #7a1538;
            font-weight: bold;
            font-size: 16px;
        }

        @media(max-width:768px){
            .container{
                width:92%;
            }

            .nav{
                flex-direction:column;
                align-items:flex-start;
                gap:12px;
            }

            .menu{
                width:100%;
                flex-direction:column;
                gap:10px;
                align-items:flex-start;
            }

            .menu li{
                width:100%;
            }

            .menu li a{
                display:block;
                padding:6px 0;
            }

            .dropdown-menu{
                position:static;
                width:100%;
                margin-top:8px;
                box-shadow:none;
            }

            .error{
                width:92%;
            }

            .login-wrapper{
                height:auto;
                padding:30px 0 50px;
            }

            .login-box{
                width:100%;
                padding:28px 20px;
            }
        }
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
            <h1>Donor Login</h1>
            <p>Access your donor dashboard to view profile details and donation eligibility.</p>
        </div>
    </div>
</section>

<?php if (!empty($errorMsg)) { ?>
    <div class="error"><?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>

<div class="login-wrapper">
    <div class="login-box">
        <h2>DONOR LOGIN</h2>
        <form action="donor.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>

        <div class="login-links">
            <a href="#">Forgot Password?</a>
            &nbsp; | &nbsp;
            <a href="donate.php">Create Account</a>
        </div>
    </div>
</div>

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
