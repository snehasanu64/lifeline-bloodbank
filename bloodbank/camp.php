<?php
include('db_connect.php');
require_once __DIR__ . '/camps_helper.php';
$upcomingCamps = load_upcoming_camps($db);
?>
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
            background:white;
            color:#333;
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
            box-shadow:0 2px 10px rgba(0,0,0,0.1);
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

        .menu li a.active{
            border-bottom:2px solid #ffd5e3;
            padding-bottom:2px;
        }

        .menu li a:hover{
            color:#ffcccc;
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
            box-shadow:0 4px 8px rgba(0,0,0,0.1);
            border-radius:5px;
        }

        .dropdown-menu li{
            border-bottom:1px solid #ddd;
        }

        .dropdown-menu li:last-child{
            border-bottom:none;
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
        .dropdown-menu.show{
            display:block;
        }

        .page-highlight{
            padding:22px 0 8px;
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

        /* MAIN CONTENT */
        main{
            padding:50px 0;
        }

        .camp-intro{
            text-align:center;
            margin-bottom:50px;
            animation:fadeIn 1s ease-in;
        }

        .camp-intro h1{
            font-size:2.5em;
            margin-bottom:20px;
            color:#7a1538;
        }

        .camp-intro p{
            font-size:1.1em;
            max-width:800px;
            margin:0 auto 30px auto;
        }

        .stats{
            display:flex;
            justify-content:center;
            gap:50px;
            margin-top:30px;
        }

        .stat-item{
            text-align:center;
        }

        .stat-item h3{
            font-size:2em;
            color:#7a1538;
            margin-bottom:10px;
        }

        .stat-item p{
            font-size:1.1em;
            color:#555;
        }

        /* UPCOMING CAMPS */
        .upcoming-camps{
            margin-bottom:50px;
        }

        .upcoming-camps h2{
            text-align:center;
            margin-bottom:30px;
            color:#7a1538;
        }

        .camp-list{
            display:flex;
            justify-content:space-around;
            flex-wrap:wrap;
        }

        .camp-item{
            background:white;
            border-radius:10px;
            box-shadow:0 4px 20px rgba(0,0,0,0.1);
            padding:20px;
            margin:20px;
            width:320px;
            transition:0.3s;
        }

        .camp-item:hover{
            transform:translateY(-10px);
            box-shadow:0 8px 30px rgba(0,0,0,0.2);
        }

        .camp-item h3{
            color:#7a1538;
            margin-bottom:10px;
        }

        .camp-item p{
            margin:5px 0;
            color:#555;
        }

        .camp-item strong{
            color:#333;
        }

        .camp-club-note{
            margin-top:10px;
            text-align:center;
            color:#555;
            font-size:1rem;
        }

        .camp-club-note a{
            color:#7a1538;
            font-weight:bold;
            text-decoration:none;
        }

        .camp-club-note a:hover{
            text-decoration:underline;
        }

        .camp-gallery{
            margin-bottom:50px;
        }

        .camp-gallery h2{
            text-align:center;
            margin-bottom:30px;
            color:#7a1538;
        }

        .photo-slider{
            position:relative;
            border-radius:12px;
            overflow:hidden;
            border:1px solid #ead8de;
            box-shadow:0 6px 22px rgba(0,0,0,0.12);
            background:#fff;
        }

        .slides{
            display:flex;
            transition:transform 0.45s ease;
            will-change:transform;
            padding:8px 6px;
        }

        .slide{
            min-width:33.3333%;
            position:relative;
            padding:0 6px;
        }

        .slide img{
            width:100%;
            height:250px;
            object-fit:cover;
            display:block;
            border-radius:10px;
        }

        .slide-caption{
            position:absolute;
            left:0;
            right:0;
            bottom:0;
            color:#fff;
            font-size:18px;
            font-weight:bold;
            padding:14px 18px;
            background:linear-gradient(to top, rgba(0,0,0,0.65), rgba(0,0,0,0));
        }

        .slider-btn{
            position:absolute;
            top:50%;
            transform:translateY(-50%);
            width:38px;
            height:38px;
            border:none;
            border-radius:50%;
            background:rgba(122,21,56,0.86);
            color:#fff;
            font-size:21px;
            cursor:pointer;
            z-index:2;
        }

        .slider-btn:hover{
            background:#7a1538;
        }

        .slider-btn.prev{
            left:12px;
        }

        .slider-btn.next{
            right:12px;
        }

        .slider-dots{
            position:absolute;
            left:0;
            right:0;
            bottom:10px;
            display:flex;
            justify-content:center;
            gap:8px;
            z-index:2;
        }

        .slider-dots button{
            width:10px;
            height:10px;
            border:none;
            border-radius:50%;
            background:rgba(255,255,255,0.55);
            cursor:pointer;
        }

        .slider-dots button.active{
            background:#fff;
        }

        .camp-map{
            margin-top:15px;
            border-radius:5px;
            overflow:hidden;
        }

        .camp-map iframe{
            width:100%;
            height:200px;
            border:none;
        }

        /* HOW TO PARTICIPATE / ORGANIZE */
        .how-to-participate,
        .organize-camp{
            background:white;
            border-radius:10px;
            box-shadow:0 4px 20px rgba(0,0,0,0.1);
            padding:30px;
            margin-bottom:50px;
        }

        .how-to-participate h2,
        .organize-camp h2{
            color:#7a1538;
            margin-bottom:20px;
        }

        .how-to-participate ul{
            list-style:none;
            padding:0;
        }

        .how-to-participate li{
            padding:10px 0;
            border-bottom:1px solid #eee;
        }

        .how-to-participate li:last-child{
            border-bottom:none;
        }

        .how-to-participate li:before{
            content:"•";
            color:#7a1538;
            font-weight:bold;
            margin-right:8px;
        }

        /* OFFICE LOCATION MAP */
        .office-location{
            margin-bottom:50px;
        }

        .office-location h2{
            color:#7a1538;
            margin-bottom:20px;
        }

        .map-container{
            border-radius:10px;
            overflow:hidden;
            box-shadow:0 4px 20px rgba(0,0,0,0.1);
            max-width:1200px;
            margin:0 auto;
        }

        .map-container iframe{
            width:100%;
            height:400px;
            border:none;
        }

        /* FEEDBACK */
        .feedback-section{
            margin-bottom:50px;
        }

        .feedback-section h2{
            text-align:center;
            color:#7a1538;
            margin-bottom:30px;
        }

        .feedback-list{
            display:flex;
            justify-content:space-around;
            flex-wrap:wrap;
        }

        .feedback-item{
            background:white;
            border-radius:10px;
            box-shadow:0 4px 20px rgba(0,0,0,0.1);
            padding:20px;
            margin:20px;
            width:300px;
            text-align:center;
        }

        .feedback-item p{
            font-style:italic;
            margin-bottom:10px;
        }

        .feedback-item .name{
            font-weight:bold;
            color:#7a1538;
        }

        /* BLOOD BANK CLUBS */
        .club-section{
            margin-bottom:50px;
        }

        .club-section h2{
            text-align:center;
            color:#7a1538;
            margin-bottom:30px;
        }

        .club-list{
            display:flex;
            justify-content:space-around;
            flex-wrap:wrap;
            gap:20px;
        }

        .club-item{
            background:white;
            border-radius:10px;
            box-shadow:0 4px 20px rgba(0,0,0,0.1);
            padding:24px;
            width:320px;
            border-top:4px solid #7a1538;
            transition:transform 0.25s ease, box-shadow 0.25s ease;
        }

        .club-item:hover{
            transform:translateY(-6px);
            box-shadow:0 10px 24px rgba(0,0,0,0.14);
        }

        .club-item h3{
            color:#7a1538;
            margin-bottom:10px;
        }

        .club-item p{
            color:#555;
            margin:8px 0;
        }

        .club-item strong{
            color:#333;
        }

        /* FOOTER */
        footer{
            background:#7a1538;
            color:white;
            padding:70px 0;
            margin-top:50px;
        }

        .footer-content{
            display:flex;
            justify-content:space-between;
        }

        .footer-left{
            width:40%;
            font-size:23px;
        }

        .footer-links ul{
            list-style:none;
            font-size:25px;
        }

        .footer-links li{
            margin-bottom:10px;
        }

        .footer-links a{
            color:white;
            text-decoration:none;
            font-size:23px;
        }

        .footer-links a:hover{
            color:#ffcccc;
        }

        .footer-bottom{
            text-align:center;
            margin-top:20px;
        }

        @keyframes fadeIn{
            from{opacity:0; transform:translateY(20px);}
            to{opacity:1; transform:translateY(0);}
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

            .stats{
                flex-direction:column;
                gap:20px;
            }

            .camp-list{
                flex-direction:column;
                align-items:center;
            }

            .camp-item{
                width:100%;
                max-width:360px;
                margin:16px 0;
            }

            .camp-intro h1{
                font-size:2rem;
            }

            .camp-map iframe{
                height:180px;
            }

            .slide{
                min-width:100%;
            }

            .slide img{
                height:220px;
            }

            .footer-content{
                flex-direction:column;
                gap:20px;
            }

            .footer-left{
                width:100%;
            }

            .club-item{
                width:100%;
                max-width:360px;
            }
        }
        .footer-logo{ width:64px; height:64px; object-fit:cover; border-radius:50%; background:#fff; padding:3px; margin-bottom:12px; display:block; }
    </style>
</head>
<body>

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
            <li><a href="camp.php" class="active">CAMP</a></li>

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
            <h1>Blood Donation Camps</h1>
            <p>Find upcoming camps, join donor clubs, and help save lives across Bengaluru.</p>
        </div>
    </div>
</section>

<!-- MAIN CONTENT -->
<main>
    <div class="container">

        <!-- INTRO + STATS -->
        <section class="camp-intro">
            <h1>Blood Donation Camps</h1>
            <p>
                At Lifeline Blood Bank, we organize regular blood donation camps to ensure a steady supply of blood for those in need.
                Participating in a blood donation camp is a noble act that can save lives. Below, you'll find information about upcoming
                camps, how to participate, and how to organize your own camp.
            </p>

            <div class="stats">
                <div class="stat-item">
                    <h3>500+</h3>
                    <p>Lives Saved</p>
                </div>
                <div class="stat-item">
                    <h3>200+</h3>
                    <p>Donors</p>
                </div>
                <div class="stat-item">
                    <h3>10+</h3>
                    <p>Camps Organized</p>
                </div>
            </div>

            <p class="camp-club-note">Want to volunteer regularly? <a href="#blood-bank-clubs">Explore our Blood Bank Clubs</a>.</p>
        </section>

        <section class="camp-gallery">
            <h2>Recent Camp Moments</h2>
            <div class="photo-slider" data-slider="camp">
                <button class="slider-btn prev" type="button" aria-label="Previous slide">&#10094;</button>
                <div class="slides">
                    <div class="slide">
                        <img loading="lazy" decoding="async" src="assets/img/event1.jpg" alt="Camp photo 1" border="0">
                        <div class="slide-caption">Registration Desk and Donor Welcome</div>
                    </div>
                    <div class="slide">
                        <img loading="lazy" decoding="async" src="assets/img/event2.jpg" alt="Camp photo 2" border="0">
                        <div class="slide-caption">Health Screening Before Donation</div>
                    </div>
                    <div class="slide">
                        <img loading="lazy" decoding="async" src="assets/img/event3.jpg" alt="Camp photo 3" border="0">
                        <div class="slide-caption">Safe and Guided Donation Process</div>
                    </div>
                    <div class="slide">
                        <img loading="lazy" decoding="async" src="assets/img/bb2.jpg" alt="Camp photo 4" border="0">
                        <div class="slide-caption">Volunteer Support Team in Action</div>
                    </div>
                    <div class="slide">
                        <img loading="lazy" decoding="async" src="assets/img/bb3.jpg" alt="Camp photo 5" border="0">
                        <div class="slide-caption">Community Joins Hands to Save Lives</div>
                    </div>
                </div>
                <button class="slider-btn next" type="button" aria-label="Next slide">&#10095;</button>
                <div class="slider-dots"></div>
            </div>
        </section>

        <!-- UPCOMING CAMPS -->
        <section class="upcoming-camps">
            <h2>Upcoming Camps in Bangalore</h2>

            <div class="camp-list">

                <?php if (empty($upcomingCamps)) : ?>
                    <p>No upcoming camps are scheduled right now. Please check back soon.</p>
                <?php endif; ?>

                <?php foreach ($upcomingCamps as $camp) : ?>
                <div class="camp-item">
                    <h3><?php echo htmlspecialchars($camp['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars(date('F j, Y', strtotime($camp['next_date'])), ENT_QUOTES, 'UTF-8'); ?><?php
                        if (($camp['recurrence'] ?? 'none') === 'weekly') { echo ' (every week)'; }
                        elseif (($camp['recurrence'] ?? 'none') === 'monthly') { echo ' (every month)'; }
                    ?></p>
                    <p><strong>Time:</strong> <?php echo htmlspecialchars($camp['time'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($camp['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><?php echo htmlspecialchars($camp['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if (!empty($camp['map_query'])) : ?>
                    <div class="camp-map">
                        <iframe src="https://www.google.com/maps?q=<?php echo rawurlencode($camp['map_query']); ?>&z=15&output=embed"
                                loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

            </div>
        </section>

        <!-- BLOOD BANK CLUBS -->
        <section class="club-section" id="blood-bank-clubs">
            <h2>Blood Bank Clubs</h2>
            <div class="club-list">
                <div class="club-item">
                    <h3>Youth Donors Club</h3>
                    <p><strong>Who can join:</strong> College students and first-time donors</p>
                    <p><strong>Focus:</strong> Awareness drives, campus camps, and volunteer training</p>
                    <p><strong>Meeting:</strong> Every 2nd Saturday, 11:00 AM</p>
                </div>

                <div class="club-item">
                    <h3>Corporate Lifesavers Club</h3>
                    <p><strong>Who can join:</strong> Working professionals and company teams</p>
                    <p><strong>Focus:</strong> Office donation camps and emergency donor network</p>
                    <p><strong>Meeting:</strong> Every 3rd Friday, 5:30 PM</p>
                </div>

                <div class="club-item">
                    <h3>Community Heroes Club</h3>
                    <p><strong>Who can join:</strong> Local residents, families, and social groups</p>
                    <p><strong>Focus:</strong> Neighborhood blood drives and donor follow-up support</p>
                    <p><strong>Meeting:</strong> Last Sunday of every month, 10:00 AM</p>
                </div>
            </div>
        </section>

        <!-- HOW TO PARTICIPATE -->
        <section class="how-to-participate">
            <h2>How to Participate</h2>
            <ul>
                <li>Ensure you are 18–65 years old and in good health.</li>
                <li>Eat a healthy meal before donating.</li>
                <li>Bring a valid ID and wear comfortable clothing.</li>
                <li>The donation process takes about 10–15 minutes.</li>
                <li>Register online or on‑site at the camp.</li>
            </ul>
        </section>

        <!-- ORGANIZE YOUR OWN CAMP -->
        <section class="organize-camp">
            <h2>Organize Your Own Camp</h2>
            <p>
                If you're interested in organizing a blood donation camp, contact us for support and resources.
            </p>
            <p><strong>Email:</strong> lifeline@bloodbank.com</p>
            <p><strong>Phone:</strong> +91 99001 63000 / +91 99001 53000</p>
            <p>
                Our office is located at 88 Hope Avenue, Sunrise Block, Bengaluru, Karnataka, India (Demo Address).
                We are available on weekdays from 9 AM to 5 PM. On weekends, we organize blood donation camps.
            </p>
        </section>

        <!-- OFFICE LOCATION -->
        <section class="office-location">
            <h2>Our Office Location</h2>
            <div class="map-container">
                <iframe src="https://www.google.com/maps?q=12.944000,77.620000&z=14&output=embed"
                        loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </section>

        <!-- FEEDBACK -->
        <section class="feedback-section">
            <h2>Donor Feedback from Past Camps in Bangalore</h2>
            <div class="feedback-list">
                <div class="feedback-item">
                    <p>“Donating blood at the MG Road camp was a great experience. The staff was very professional and caring. I felt good knowing I helped save lives.”</p>
                    <div class="name">Ravi Kumar</div>
                </div>
                <div class="feedback-item">
                    <p>“The university camp was well‑organized. Refreshments were provided, and the process was quick. I'll definitely donate again.”</p>
                    <div class="name">Priya Sharma</div>
                </div>
                <div class="feedback-item">
                    <p>“As a first‑time donor, I was nervous, but the team made me feel comfortable. Proud to be part of this noble cause in Bangalore.”</p>
                    <div class="name">Amit Patel</div>
                </div>
                <div class="feedback-item">
                    <p>“The Electronic City camp was fantastic. The health check‑up was a bonus. Will recommend to colleagues.”</p>
                    <div class="name">Sneha</div>
                </div>
                <div class="feedback-item">
                    <p>“Great organization and friendly staff. Donating blood has never been easier. Thank you for saving lives!”</p>
                    <div class="name">Vikram</div>
                </div>
            </div>
        </section>

    </div>
</main>

<!-- FOOTER -->
<footer>
    <div class="container footer-content">

        <div class="footer-left">
            <img src="assets/img/logo.jpg" alt="Lifeline Blood Bank logo" class="footer-logo">
            <h2>Lifeline Blood Bank</h2>
            <p>
                Thank you for considering to donate blood and help save lives.
                Your selfless act could make all the difference to someone in need.
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

    document.querySelectorAll('.photo-slider').forEach(function (slider) {
        const slidesWrap = slider.querySelector('.slides');
        const slides = slider.querySelectorAll('.slide');
        const prev = slider.querySelector('.slider-btn.prev');
        const next = slider.querySelector('.slider-btn.next');
        const dotsWrap = slider.querySelector('.slider-dots');
        let current = 0;
        let timer = null;

        function getVisibleCount() {
            const slideWidth = slides[0] ? slides[0].getBoundingClientRect().width : 1;
            return Math.max(1, Math.min(slides.length, Math.round(slider.clientWidth / slideWidth)));
        }

        function getMaxIndex() {
            return Math.max(0, slides.length - getVisibleCount());
        }

        function renderDots() {
            dotsWrap.innerHTML = '';
            const maxIndex = getMaxIndex();
            for (let idx = 0; idx <= maxIndex; idx++) {
                const dot = document.createElement('button');
                dot.type = 'button';
                if (idx === current) dot.classList.add('active');
                dot.addEventListener('click', function () {
                    current = idx;
                    update();
                    restartAuto();
                });
                dotsWrap.appendChild(dot);
            }
        }

        function update() {
            const maxIndex = getMaxIndex();
            if (current > maxIndex) {
                current = maxIndex;
            }
            const slideWidth = slides[0] ? slides[0].getBoundingClientRect().width : 0;
            slidesWrap.style.transform = 'translateX(-' + (current * slideWidth) + 'px)';
            dotsWrap.querySelectorAll('button').forEach(function (dot, idx) {
                dot.classList.toggle('active', idx === current);
            });
        }

        function nextSlide() {
            const maxIndex = getMaxIndex();
            current = current >= maxIndex ? 0 : current + 1;
            update();
        }

        function prevSlide() {
            const maxIndex = getMaxIndex();
            current = current <= 0 ? maxIndex : current - 1;
            update();
        }

        function restartAuto() {
            clearInterval(timer);
            if (getMaxIndex() > 0) {
                timer = setInterval(nextSlide, 3500);
            }
        }

        prev.addEventListener('click', function () {
            prevSlide();
            restartAuto();
        });

        next.addEventListener('click', function () {
            nextSlide();
            restartAuto();
        });

        renderDots();
        update();
        restartAuto();

        window.addEventListener('resize', function () {
            renderDots();
            update();
            restartAuto();
        });

        slider.addEventListener('mouseenter', function () {
            clearInterval(timer);
        });

        slider.addEventListener('mouseleave', function () {
            restartAuto();
        });
    });
});
</script>

</body>
</html>
