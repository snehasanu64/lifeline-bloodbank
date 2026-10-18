<!DOCTYPE html>
<html>
<head>
    <title>Blood Bank & Donor Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial;
        }

        body{
            line-height:1.6;
        }

        .container{
            width:85%;
            margin:auto;
        }

        /* HEADER */
        header{
            background:#7a1538;
            padding:20px 0;
            color:white;
        }

        .nav{
            display:flex;
            justify-content:space-between;
            align-items:center;
        }

        .logo{
            display:flex;
            align-items:center;
        }

        .logo img{
            width:40px;
            margin-right:10px;
        }

        /* MENU STYLE */
        .menu{
            list-style:none;
            display:flex;
            gap:30px;
        }

        .menu li{
            position:relative;
        }

        .menu li a{
            text-decoration:none;
            color:white;
            font-weight:bold;
        }

        /* DROPDOWN */
        .dropdown-menu{
            display:none;
            position:absolute;
            top:35px;
            left:0;
            background:#f2f2f2;
            list-style:none;
            padding:0;
            margin:0;
            width:180px;
        }

        .dropdown-menu li{
            border-bottom:1px solid #ddd;
        }

        .dropdown-menu li a{
            color:#7a1538;
            padding:10px;
            display:block;
        }

        .dropdown-menu li a:hover{
            background:#ddd;
        }

        /* SHOW DROPDOWN ON CLICK */
        .dropdown-menu.show {
            display:block;
        }

        .page-highlight{
            padding:22px 0 8px;
            background:#f5f5f5;
        }

        .page-highlight .highlight-box{
            background:linear-gradient(135deg, #7a1538, #a73b66);
            color:#fff;
            border-radius:14px;
            padding:18px 22px;
            box-shadow:0 8px 20px rgba(122,21,56,0.22);
        }

        .page-highlight h1{
            font-size:28px;
            margin-bottom:6px;
        }

        .page-highlight p{
            font-size:16px;
            opacity:0.95;
        }

        /* LOGIN OPTIONS SECTION */
        .login-section{
            padding:80px 0;
            background:#f5f5f5;
        }

        .login-wrapper{
            max-width:600px;
            margin:0 auto;
            background:white;
            padding:40px;
            border-radius:15px;
            box-shadow:0 0 15px rgba(0,0,0,0.15);
            text-align:center;
        }

        .login-wrapper h1{
            font-size:32px;
            margin-bottom:10px;
            color:#7a1538;
        }

        .login-wrapper p{
            font-size:18px;
            margin-bottom:25px;
            color:#555;
        }

        .login-buttons{
            display:flex;
            justify-content:space-around;
            flex-wrap:wrap;
            gap:20px;
        }

        .login-buttons a{
            display:inline-block;
            padding:12px 30px;
            border-radius:25px;
            text-decoration:none;
            font-size:18px;
            font-weight:bold;
            cursor:pointer;
        }

        .btn-donor{
            background:#7a1538;
            color:white;
        }

        .btn-request{
            background:#ffffff;
            color:#7a1538;
            border:2px solid #7a1538;
        }

        .btn-find{
            background:#d32f2f;
            color:white;
        }

        .login-note{
            margin-top:25px;
            font-size:16px;
            color:#777;
        }

        /* FOOTER */
        footer{
            background:#7a1538;
            color:white;
            padding:40px 0;
            margin-top:50px;
        }

        .footer-content{
            display:flex;
            justify-content:space-between;
        }

        .footer-left{
            width:40%;
            font-size:20px;
        }

        .footer-links ul{
            list-style:none;
            font-size:22px;
        }

        .footer-links li{
            margin-bottom:10px;
        }

        .footer-links a{
            color:white;
            text-decoration:none;
        }

        .footer-bottom{
            text-align:center;
            margin-top:15px;
        }

        @media(max-width:768px)
        {
            .login-buttons{
                flex-direction:column;
                align-items:center;
            }

            .footer-content{
                flex-direction:column;
                gap:20px;
            }

            .footer-left{
                width:100%;
            }
        }
        .footer-logo{ width:64px; height:64px; object-fit:cover; border-radius:50%; background:#fff; padding:3px; margin-bottom:12px; display:block; }
    </style>
</head>
<body>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- HEADER -->
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
            <h1>Login Options</h1>
            <p>Choose the right path to continue: donor login, blood request, or blood search.</p>
        </div>
    </div>
</section>

<!-- LOGIN OPTIONS -->
<section class="login-section">
    <div class="container">
        <div class="login-wrapper">
            <h1>Select Login Option</h1>
            <p>
                Choose how you want to continue. Donors can manage their profile,
                hospitals can raise requests, and visitors can search for available blood.
            </p>

            <div class="login-buttons">
                <a href="donor.php" class="btn-donor">Donor Login</a>
                <a href="request.php" class="btn-request">Raise Blood Request</a>
                <a href="find.php" class="btn-find">Find Blood</a>
            </div>

            <p class="login-note">
                You can also use the LOGIN dropdown in the top menu to quickly switch between options.
            </p>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="container footer-content">

        <div class="footer-left">
            <img src="assets/img/logo.jpg" alt="Lifeline Blood Bank logo" class="footer-logo">
            <h2>Lifeline Blood Bank</h2>
            <p>
                Thank you for supporting voluntary blood donation. Your contribution
                helps us respond faster during emergencies.
            </p>
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
        &copy; <?php echo date('Y'); ?> Lifeline Blood Bank. All rights reserved.
    </div>
</footer>

<!-- LOGIN DROPDOWN JS -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.querySelector('.login-toggle');
    const menu   = document.querySelector('.dropdown-menu');

    if (toggle && menu) {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            menu.classList.toggle('show');
        });
    }
});
</script>

</body>
</html>
