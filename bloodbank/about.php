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
            background:#fff;
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
            position:relative;
            z-index:999;
            box-shadow:0 3px 12px rgba(0,0,0,0.12);
        }

        .nav{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
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
            border-bottom:2px solid #ffd6e4;
            padding-bottom:2px;
        }

        .menu li a:hover{
            opacity:0.9;
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
            border-radius:8px;
            overflow:hidden;
            box-shadow:0 8px 18px rgba(0,0,0,0.14);
            z-index:20;
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

        .dropdown-menu.show {
            display:block;
        }

        .hero-band{
            padding:34px 0 14px;
        }

        .hero-band .hero-wrap{
            background:linear-gradient(135deg, #7a1538, #a43763);
            color:#fff;
            border-radius:18px;
            padding:34px;
            box-shadow:0 10px 24px rgba(122,21,56,0.2);
        }

        .hero-wrap h1{
            font-size:44px;
            line-height:1.1;
            margin-bottom:10px;
        }

        .hero-wrap p{
            max-width:900px;
            font-size:18px;
            opacity:0.96;
        }

        .hero-tags{
            margin-top:16px;
            display:flex;
            flex-wrap:wrap;
            gap:10px;
        }

        .hero-tags span{
            border:1px solid rgba(255,255,255,0.45);
            background:rgba(255,255,255,0.12);
            padding:7px 12px;
            border-radius:18px;
            font-size:14px;
            font-weight:bold;
        }

        /* QUICK FACTS */
        .quick-facts{
            padding:26px 0 6px;
        }

        .facts-grid{
            display:grid;
            grid-template-columns:repeat(4, 1fr);
            gap:18px;
        }

        .fact-card{
            background:#fff;
            border:1px solid #ead8de;
            border-radius:14px;
            box-shadow:0 8px 20px rgba(0,0,0,0.08);
            padding:24px 18px;
            text-align:center;
            min-height:150px;
            display:flex;
            flex-direction:column;
            justify-content:center;
            transition:transform 0.22s ease, box-shadow 0.22s ease;
        }

        .fact-card:hover{
            transform:translateY(-4px);
            box-shadow:0 12px 24px rgba(0,0,0,0.12);
        }

        .fact-card h3{
            color:#7a1538;
            font-size:36px;
            margin-bottom:4px;
            line-height:1.1;
        }

        .fact-card p{
            color:#5e5558;
            font-size:16px;
        }

        /* ABOUT SECTION */
        .about-section{
            padding:40px 0 35px;
        }

        .about-shell{
            display:grid;
            grid-template-columns:1.15fr 0.85fr;
            gap:24px;
            align-items:stretch;
        }

        .about-main,
        .about-side{
            background:#fff;
            border:1px solid #ead8de;
            border-radius:16px;
            padding:30px;
            box-shadow:0 8px 20px rgba(0,0,0,0.08);
        }

        .about-main h2{
            font-size:40px;
            line-height:1.1;
            margin-bottom:16px;
            color:#7a1538;
        }

        .about-main p{
            margin-bottom:14px;
            font-size:18px;
            color:#5e5558;
        }

        .about-side img{
            width:100%;
            height:260px;
            object-fit:cover;
            border-radius:12px;
            margin-bottom:14px;
        }

        .about-side h3{
            color:#7a1538;
            font-size:24px;
            margin-bottom:8px;
        }

        .action-row{
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-top:14px;
        }

        .action-row a{
            display:inline-block;
            text-decoration:none;
            padding:10px 18px;
            border-radius:22px;
            font-weight:bold;
            font-size:15px;
        }

        .action-row .primary{
            background:#d32f2f;
            color:#fff;
            transition:background 0.2s ease, transform 0.2s ease;
        }

        .action-row .primary:hover{
            background:#b92323;
            transform:translateY(-1px);
        }

        .action-row .secondary{
            border:2px solid #7a1538;
            color:#7a1538;
            transition:background 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .action-row .secondary:hover{
            background:#7a1538;
            color:#fff;
            transform:translateY(-1px);
        }

        .about-grid{
            display:grid;
            grid-template-columns:1fr 1.05fr;
            gap:28px;
            align-items:start;
        }

        .photo-stack{
            background:#fff;
            border:1px solid #ead8de;
            border-radius:16px;
            padding:14px;
            box-shadow:0 8px 20px rgba(0,0,0,0.08);
        }

        .photo-stack .main-img{
            width:100%;
            height:570px;
            object-fit:cover;
            border-radius:14px;
            display:block;
        }

        .photo-row{
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:10px;
            margin-top:12px;
        }

        .photo-row img{
            width:100%;
            height:220px;
            object-fit:cover;
            border-radius:12px;
            display:block;
        }

        .about-content{
            background:#fff;
            border:1px solid #ead8de;
            border-radius:16px;
            padding:34px;
            box-shadow:0 8px 20px rgba(0,0,0,0.08);
        }

        .section-label{
            color:#7a1538;
            font-size:14px;
            font-weight:bold;
            text-transform:uppercase;
            letter-spacing:1px;
            margin-bottom:8px;
        }

        .about-content h2{
            font-size:42px;
            line-height:1.1;
            margin-bottom:18px;
            color:#7a1538;
        }

        .about-content p{
            margin-bottom:14px;
            font-size:18px;
            color:#5e5558;
        }

        .info-list{
            margin:16px 0 20px 20px;
            color:#5e5558;
            font-size:17px;
        }

        .info-list li{
            margin-bottom:8px;
        }

        .mini-panel{
            margin-top:22px;
            border-top:1px solid #ead8de;
            padding-top:18px;
        }

        .mini-panel h3{
            color:#7a1538;
            font-size:24px;
            margin-bottom:8px;
        }

        /* STORY STRIP */
        .story-strip{
            padding:25px 0 55px;
        }

        .story-grid{
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:18px;
        }

        .story-card{
            background:#f9f9f9;
            border:1px solid #ead8de;
            border-radius:14px;
            padding:30px;
            transition:transform 0.2s ease, box-shadow 0.2s ease;
        }

        .story-card:hover{
            transform:translateY(-4px);
            box-shadow:0 10px 22px rgba(0,0,0,0.1);
        }

        .story-card h3{
            color:#7a1538;
            font-size:24px;
            margin-bottom:8px;
        }

        .story-card p,
        .story-card li{
            color:#5e5558;
            font-size:16px;
        }

        .story-card ul{
            margin-left:18px;
        }

        .cta-banner{
            padding-bottom:15px;
        }

        .cta-box{
            background:#fff6f8;
            border:1px solid #ead8de;
            border-radius:14px;
            padding:24px;
            box-shadow:0 8px 20px rgba(0,0,0,0.08);
        }

        .cta-box p{
            color:#5e5558;
            font-size:18px;
        }

        /* FEATURE PHOTO WALL */
        .gallery-section{
            padding:45px 0 80px;
        }

        .gallery-header{
            display:flex;
            justify-content:space-between;
            gap:16px;
            align-items:end;
            margin-bottom:18px;
        }

        .gallery-header h2{
            font-size:34px;
            color:#7a1538;
        }

        .gallery-header p{
            color:#5e5558;
            font-size:17px;
            max-width:720px;
        }

        .photo-slider{
            position:relative;
            border-radius:14px;
            overflow:hidden;
            border:1px solid #ead8de;
            box-shadow:0 10px 18px rgba(0,0,0,0.08);
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
            padding:14px 18px;
            color:#fff;
            background:linear-gradient(to top, rgba(0,0,0,0.65), rgba(0,0,0,0));
            font-size:17px;
            font-weight:bold;
        }

        .slider-btn{
            position:absolute;
            top:50%;
            transform:translateY(-50%);
            width:38px;
            height:38px;
            border:none;
            border-radius:50%;
            background:rgba(122,21,56,0.85);
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

        /* FOOTER */
        footer{
            background:#7a1538;
            color:white;
            padding:50px 0 20px;
        }

        .footer-content{
            display:flex;
            justify-content:space-between;
            gap:20px;
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
            font-size:22px;
        }

        .footer-links a{
            color:white;
            text-decoration:none;
            font-size:20px;
        }

        .footer-bottom{
            text-align:center;
            margin-top:20px;
            border-top:1px solid rgba(255,255,255,0.25);
            padding-top:16px;
        }

        @media(max-width:900px){
            .facts-grid,
            .story-grid,
            .about-grid{
                grid-template-columns:1fr;
            }

            .about-shell{
                grid-template-columns:1fr;
            }

            .slide{
                min-width:50%;
            }

            .slide img{
                height:230px;
            }
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

            .slide{
                min-width:100%;
            }

            .slide img{
                height:220px;
            }

            .gallery-header{
                flex-direction:column;
                align-items:flex-start;
            }

            .about-content,
            .photo-stack,
            .story-card{
                padding:24px;
            }

            .about-main,
            .about-side,
            .hero-band .hero-wrap{
                padding:24px;
            }

            .about-content h2{
                font-size:32px;
            }

            .hero-wrap h1{
                font-size:32px;
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

<!-- HEADER -->
<header>
    <div class="container nav">
        <div class="logo">
            <img decoding="async" src="assets/img/logo.jpg" alt="logo" border="0">
            <h2>Lifeline Blood bank </h2>
        </div>

        <ul class="menu">
            <li><a href="index.php">HOME</a></li>
            <li><a href="about.php" class="active">ABOUT US</a></li>
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

<section class="hero-band">
    <div class="container">
        <div class="hero-wrap">
            <h1>About Lifeline Blood Bank</h1>
            <p>We are a people-first blood support network focused on speed, safety, and compassion. Our mission is simple: connect every patient in need with reliable blood support, without delay.</p>
            <div class="hero-tags">
                <span>Safe Collection</span>
                <span>Rapid Response</span>
                <span>Community Driven</span>
            </div>
        </div>
    </div>
</section>

<section class="quick-facts">
    <div class="container facts-grid">
        <div class="fact-card">
            <h3>24x7</h3>
            <p>Emergency support coordination</p>
        </div>
        <div class="fact-card">
            <h3>100%</h3>
            <p>Screening and safety standards</p>
        </div>
        <div class="fact-card">
            <h3>Fast</h3>
            <p>Quick response during critical needs</p>
        </div>
        <div class="fact-card">
            <h3>Trusted</h3>
            <p>Community donors and hospital network</p>
        </div>
    </div>
</section>

<!-- ABOUT SECTION 1 -->
<section class="about-section">
    <div class="container about-shell">
        <div class="about-main">
            <div class="section-label">Who We Are</div>
            <h2>Supporting people when every minute matters</h2>
            <p>
                Lifeline Blood Bank is a community-focused organization that connects voluntary blood donors
                with patients who urgently need blood and blood components. We work closely with hospitals,
                clinics, and emergency services to make sure that no life is lost due to lack of blood.
            </p>
            <p>
                Our team follows strict medical standards at every step: donor screening, blood collection,
                testing, storage, and distribution. Every unit is monitored and documented from start to finish.
            </p>

            <h3>What We Do</h3>
            <ul class="info-list">
                <li>Organize regular blood donation camps in colleges, IT parks, and residential areas.</li>
                <li>Maintain a well-managed database of voluntary donors for quick access during emergencies.</li>
                <li>Educate the public about the importance of regular blood donation and healthy lifestyles.</li>
                <li>Support hospitals with a reliable supply of red cells, platelets, and plasma.</li>
            </ul>
        </div>

        <aside class="about-side">
            <img loading="lazy" decoding="async" src="assets/img/about1.jpg" alt="About Lifeline Blood Bank" border="0">
            <h3>Our Values</h3>
            <p>
                We believe every donor is a hero. Our work is guided by compassion, transparency,
                and respect for human life. We treat donors and patients with equal care and dignity.
            </p>
            <div class="action-row">
                <a href="donate.php" class="primary">Become a Donor</a>
                <a href="camp.php" class="secondary">Join Next Camp</a>
                <a href="request.php" class="secondary">Raise Request</a>
            </div>
        </aside>
    </div>
</section>

<!-- SECTION 2 - MISSION -->
<section class="story-strip">
    <div class="container story-grid">
        <div class="story-card">
            <h3>A Legacy of Service</h3>
            <p>
                Lifeline Blood Bank has grown through compassion, commitment, and trusted partnerships.
                Every donation helps us respond faster to critical cases across the city.
            </p>
            <ul>
                <li>Supported emergency surgeries and accident cases.</li>
                <li>Partnered with organizations for mega donation drives.</li>
                <li>Built strong relationships with regular donors.</li>
            </ul>
        </div>

        <div class="story-card">
            <h3>Our Mission</h3>
            <p>
                Our mission is to ensure a safe, reliable, and timely supply of blood and blood components
                to patients in need, while encouraging voluntary donation as a regular social responsibility.
            </p>
            <p>
                We aim to create a culture where donating blood becomes a normal part of life.
            </p>
        </div>

        <div class="story-card">
            <h3>Why Regular Donation Matters</h3>
            <p>
                Demand remains constant for surgeries, deliveries, trauma, cancer care, and chronic disorders.
                One donation can help multiple patients when separated into components.
            </p>
            <ul>
                <li>Regular donors stabilize emergency inventory.</li>
                <li>Many patients require scheduled transfusions.</li>
                <li>Voluntary donors reduce dependence on replacement donors.</li>
            </ul>
        </div>
    </div>
</section>

<!-- SECTION 3 - PHOTO WALL -->
<section class="gallery-section">
    <div class="container">
        <div class="gallery-header">
            <div>
                <h2>Our Events & Camps</h2>
                <p>A photo-first view of our donation drives, volunteer teams, and community outreach moments.</p>
            </div>
        </div>

        <div class="photo-slider" data-slider="events">
            <button class="slider-btn prev" type="button" aria-label="Previous slide">&#10094;</button>
            <div class="slides">
                <div class="slide">
                    <img loading="lazy" decoding="async" src="assets/img/event1.jpg" alt="Event camp photo 1" border="0">
                    <div class="slide-caption">Community Donation Event</div>
                </div>
                <div class="slide">
                    <img loading="lazy" decoding="async" src="assets/img/event2.jpg" alt="Event camp photo 2" border="0">
                    <div class="slide-caption">Volunteer Team at Work</div>
                </div>
                <div class="slide">
                    <img loading="lazy" decoding="async" src="assets/img/event3.jpg" alt="Event camp photo 3" border="0">
                    <div class="slide-caption">Donation Drive Highlights</div>
                </div>
                <div class="slide">
                    <img loading="lazy" decoding="async" src="assets/img/bb2.jpg" alt="Event camp photo 4" border="0">
                    <div class="slide-caption">Health Screening Zone</div>
                </div>
                <div class="slide">
                    <img loading="lazy" decoding="async" src="assets/img/bb4.jpg" alt="Donor care and support" border="0">
                    <div class="slide-caption">Donor Care and Support</div>
                </div>
                <div class="slide">
                    <img loading="lazy" decoding="async" src="assets/img/bb3.jpg" alt="Event camp photo 6" border="0">
                    <div class="slide-caption">Life-Saving Community Movement</div>
                </div>
            </div>
            <button class="slider-btn next" type="button" aria-label="Next slide">&#10095;</button>
            <div class="slider-dots"></div>
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
    const menu = document.querySelector('.dropdown-menu');

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
