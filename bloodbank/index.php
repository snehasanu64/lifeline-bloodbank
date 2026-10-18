<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lifeline Blood Bank | Donate Blood, Save a Life</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root{
            --wine:#7a1538;
            --wine-dark:#4e0d25;
            --rose:#c02455;
            --red:#d32f2f;
            --blush:#fff1f5;
            --ink:#2d1a22;
            --muted:#6f5b63;
            --card:#ffffff;
            --shadow:0 10px 30px rgba(122,21,56,0.12);
        }
        *{margin:0;padding:0;box-sizing:border-box;}
        html{scroll-behavior:smooth;}
        body{font-family:'Inter',Arial,sans-serif;line-height:1.65;background:#fff;color:var(--ink);}
        h1,h2,h3,h4{font-family:'Outfit','Inter',sans-serif;}
        .container{width:88%;max-width:1180px;margin:auto;}

        /* HEADER */
        header{
            background:rgba(122,21,56,0.94);
            backdrop-filter:blur(10px);
            padding:14px 0;
            color:white;
            position:sticky;
            top:0;
            z-index:9999;
            box-shadow:0 4px 18px rgba(0,0,0,0.15);
        }
        .nav{display:flex;justify-content:space-between;align-items:center;}
        .logo{display:flex;align-items:center;gap:10px;}
        .logo img{width:42px;height:42px;border-radius:50%;object-fit:cover;background:#fff;padding:2px;}
        .logo h2{font-size:21px;}
        .menu{list-style:none;display:flex;gap:28px;align-items:center;}
        .menu li{position:relative;}
        .menu li a{text-decoration:none;color:white;font-weight:600;font-size:14px;letter-spacing:.5px;padding:6px 0;}
        .menu li a.active{border-bottom:2px solid #ffd5e3;}
        .menu li a:hover{color:#ffd5e3;}
        .dropdown-menu{
            display:none;position:absolute;top:36px;right:0;background:#fff;list-style:none;
            width:190px;border-radius:12px;overflow:hidden;box-shadow:0 14px 30px rgba(0,0,0,0.25);z-index:10000;
        }
        .dropdown-menu li a{color:var(--wine);padding:11px 16px;display:block;font-size:14px;}
        .dropdown-menu li a:hover{background:var(--blush);color:var(--wine);}
        .dropdown-menu.show{display:block;}
        .menu-toggle{display:none;background:none;border:none;color:#fff;font-size:28px;cursor:pointer;}

        /* HERO */
        .hero{
            position:relative;
            min-height:640px;
            display:flex;
            align-items:center;
            color:white;
            background:url('assets/img/hero-bg.jpg') center/cover no-repeat;
            overflow:hidden;
        }
        .hero::before{
            content:"";position:absolute;inset:0;
            background:linear-gradient(110deg, rgba(78,13,37,0.93) 0%, rgba(122,21,56,0.8) 45%, rgba(0,0,0,0.35) 100%);
        }
        .hero .container{position:relative;z-index:2;display:grid;grid-template-columns:1.15fr .85fr;gap:40px;align-items:center;padding:60px 0;}
        .hero-badge{
            display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.15);
            border:1px solid rgba(255,255,255,0.3);padding:6px 14px;border-radius:999px;font-size:13px;font-weight:600;margin-bottom:18px;
        }
        .hero-badge i{width:8px;height:8px;background:#5dff9a;border-radius:50%;box-shadow:0 0 0 0 rgba(93,255,154,.7);animation:ping 1.8s infinite;}
        @keyframes ping{70%{box-shadow:0 0 0 10px rgba(93,255,154,0);}100%{box-shadow:0 0 0 0 rgba(93,255,154,0);}}
        .hero h1{font-size:clamp(36px,5.2vw,62px);line-height:1.08;margin-bottom:18px;font-weight:800;}
        .hero h1 em{font-style:normal;color:#ffb3c8;}
        .hero p.lead{font-size:19px;color:#ffe8ef;max-width:560px;margin-bottom:26px;}
        .hero-actions{display:flex;gap:14px;flex-wrap:wrap;}
        .btn{
            display:inline-block;padding:14px 30px;background:linear-gradient(135deg,#e53950,#d32f2f);color:white;text-decoration:none;
            border-radius:999px;font-weight:700;border:none;cursor:pointer;font-size:15px;font-family:inherit;
            box-shadow:0 10px 24px rgba(211,47,47,0.4);transition:transform .2s, box-shadow .2s;
        }
        .btn:hover{transform:translateY(-3px);box-shadow:0 14px 28px rgba(211,47,47,0.5);}
        .btn-outline{
            display:inline-block;padding:12px 28px;border:2px solid rgba(255,255,255,0.9);color:white;text-decoration:none;
            border-radius:999px;font-weight:700;font-size:15px;transition:all .2s;
        }
        .btn-outline:hover{background:white;color:var(--wine);transform:translateY(-3px);}
        .hero-trust{display:flex;gap:26px;margin-top:34px;flex-wrap:wrap;font-size:14px;color:#ffe8ef;}
        .hero-trust span::before{content:"✓ ";color:#5dff9a;font-weight:bold;}

        .hero-card{
            background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.28);backdrop-filter:blur(14px);
            border-radius:24px;padding:28px;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,0.3);
        }
        .drop{
            width:96px;height:96px;margin:0 auto 12px;background:linear-gradient(145deg,#ff5a6e,#b3122e);
            border-radius:50% 0 50% 50%;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;
            box-shadow:0 12px 30px rgba(0,0,0,0.35);animation:float 3.2s ease-in-out infinite;
        }
        .drop span{transform:rotate(45deg);font-size:34px;font-weight:800;font-family:'Outfit';}
        @keyframes float{50%{transform:rotate(-45deg) translateY(-12px);}}
        .hero-card h3{font-size:22px;margin-bottom:4px;}
        .hero-card p{font-size:14px;color:#ffe8ef;margin-bottom:16px;}
        .hero-card-stats{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
        .hero-card-stats div{background:rgba(255,255,255,0.14);border-radius:14px;padding:12px;}
        .hero-card-stats b{display:block;font-size:24px;font-family:'Outfit';}
        .hero-card-stats small{font-size:12px;color:#ffe8ef;}

        /* URGENT STRIP / MARQUEE */
        .urgent-strip{
            background:linear-gradient(90deg,var(--wine-dark),var(--wine),#9d1f4b);color:white;padding:14px 0;
            text-align:center;font-size:16px;font-weight:600;
        }
        .urgent-strip span{color:#ffd5de;}
        .urgent-strip a{color:#fff;text-decoration:underline;}

        /* SECTIONS */
        .section{padding:80px 0;}
        .light-bg{background:#fdf6f8;}
        .eyebrow{display:block;text-align:center;color:var(--rose);font-weight:700;letter-spacing:2px;font-size:12px;margin-bottom:8px;text-transform:uppercase;}
        .section h2.title{text-align:center;color:var(--wine);font-size:clamp(28px,3.6vw,40px);margin-bottom:12px;}
        .section p.sub{text-align:center;max-width:720px;margin:0 auto 42px;color:var(--muted);font-size:17px;}

        /* QUICK ACTIONS */
        .quick-actions{margin-top:-60px;position:relative;z-index:5;}
        .qa-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;}
        .qa-card{
            background:#fff;border-radius:18px;padding:24px 20px;text-decoration:none;color:var(--ink);
            box-shadow:var(--shadow);border-bottom:4px solid var(--wine);transition:transform .25s, box-shadow .25s;
        }
        .qa-card:hover{transform:translateY(-8px);box-shadow:0 18px 38px rgba(122,21,56,0.22);}
        .qa-icon{width:52px;height:52px;border-radius:14px;background:var(--blush);display:flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:12px;}
        .qa-card h3{color:var(--wine);font-size:18px;margin-bottom:4px;}
        .qa-card p{font-size:14px;color:var(--muted);}

        /* IMPACT */
        .impact-section{background:linear-gradient(135deg,#5a0c28,#8f1743);color:white;padding:70px 0;position:relative;overflow:hidden;}
        .impact-section::before{content:"";position:absolute;right:-80px;top:-120px;width:320px;height:320px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.18),transparent 70%);}
        .impact-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;position:relative;z-index:1;}
        .impact-card{background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);border-radius:18px;padding:30px 16px;text-align:center;}
        .impact-card h3{font-size:42px;line-height:1;margin-bottom:8px;}
        .impact-card p{color:#ffeaf0;font-size:15px;}
        .impact-section .title{color:#fff;}
        .impact-section .eyebrow{color:#ffb3c8;}

        /* TOOL CARDS */
        .tools{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;}
        .tool{background:#fff;border-radius:22px;padding:28px;box-shadow:var(--shadow);border-top:5px solid var(--wine);}
        .tool h3{color:var(--wine);font-size:21px;margin-bottom:6px;}
        .tool > p{font-size:14px;color:var(--muted);margin-bottom:16px;}
        .tool label{display:block;font-size:13px;font-weight:600;margin:10px 0 4px;color:var(--ink);}
        .tool select,.tool input{
            width:100%;padding:11px 12px;border:1.5px solid #e6cfd8;border-radius:12px;font-size:15px;font-family:inherit;background:#fff;
        }
        .tool select:focus,.tool input:focus{outline:none;border-color:var(--rose);box-shadow:0 0 0 3px rgba(192,36,85,.15);}
        .tool .btn{width:100%;margin-top:16px;padding:12px;box-shadow:none;}
        .result{margin-top:14px;padding:14px;border-radius:14px;background:var(--blush);font-size:14px;color:var(--ink);min-height:50px;}
        .result.ok{background:#e9f9ef;color:#145c2e;}
        .result.no{background:#fdecec;color:#8f1d1d;}
        .chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;}
        .chip{background:var(--wine);color:#fff;border-radius:999px;padding:3px 12px;font-weight:700;font-size:13px;}
        .chip.alt{background:#fff;color:var(--wine);border:1.5px solid var(--wine);}

        /* BLOOD GROUP PICKER */
        .bg-picker{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:6px;}
        .bg-picker button{
            padding:10px 0;border-radius:12px;border:1.5px solid #e6cfd8;background:#fff;font-weight:800;color:var(--wine);
            cursor:pointer;font-family:'Outfit';font-size:16px;transition:all .15s;
        }
        .bg-picker button:hover{background:var(--blush);}
        .bg-picker button.on{background:var(--wine);color:#fff;border-color:var(--wine);transform:scale(1.05);}

        /* STEPS */
        .steps-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:22px;position:relative;}
        .step-card{background:#fff;padding:28px 22px;border-radius:20px;box-shadow:var(--shadow);text-align:center;transition:transform .25s;}
        .step-card:hover{transform:translateY(-6px);}
        .step-number{
            width:52px;height:52px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;
            background:linear-gradient(135deg,var(--wine),var(--rose));color:#fff;font-weight:800;font-size:20px;margin-bottom:14px;font-family:'Outfit';
        }
        .step-card h3{color:var(--wine);margin-bottom:6px;font-size:19px;}
        .step-card p{color:var(--muted);font-size:15px;}

        /* BENEFITS / INFO */
        .info-panels{display:grid;grid-template-columns:repeat(2,1fr);gap:24px;}
        .info-panel{background:#fff;border-radius:20px;padding:28px;box-shadow:var(--shadow);}
        .info-panel.good{border-left:6px solid #2e9e5b;}
        .info-panel.bad{border-left:6px solid var(--red);}
        .info-panel h3{color:var(--wine);margin-bottom:12px;font-size:20px;}
        .info-panel ul{list-style:none;}
        .info-panel li{margin-bottom:10px;padding-left:26px;position:relative;color:#4a3a40;font-size:15px;}
        .info-panel.good li::before{content:"✔";position:absolute;left:0;color:#2e9e5b;font-weight:bold;}
        .info-panel.bad li::before{content:"✖";position:absolute;left:0;color:var(--red);font-weight:bold;}

        /* MYTH FLIP */
        .myth-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;}
        .flip{perspective:900px;height:210px;cursor:pointer;}
        .flip-inner{position:relative;width:100%;height:100%;transition:transform .6s;transform-style:preserve-3d;}
        .flip:hover .flip-inner,.flip.flipped .flip-inner{transform:rotateY(180deg);}
        .flip-face{
            position:absolute;inset:0;backface-visibility:hidden;border-radius:20px;padding:22px;display:flex;
            flex-direction:column;justify-content:center;text-align:center;box-shadow:var(--shadow);
        }
        .flip-front{background:#fff;border-top:5px solid var(--red);}
        .flip-front b{color:var(--red);font-size:12px;letter-spacing:2px;margin-bottom:8px;}
        .flip-front span{font-weight:600;font-size:16px;}
        .flip-back{background:linear-gradient(135deg,#1f8a4c,#2e9e5b);color:#fff;transform:rotateY(180deg);}
        .flip-back b{font-size:12px;letter-spacing:2px;margin-bottom:8px;color:#c7ffdc;}
        .flip-back span{font-size:15px;}

        /* CAMPAIGN CTA */
        .cta-banner{
            background:linear-gradient(135deg,var(--wine-dark),var(--wine) 60%,var(--rose));color:#fff;border-radius:28px;
            padding:50px;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;
            box-shadow:0 20px 50px rgba(122,21,56,0.35);position:relative;overflow:hidden;
        }
        .cta-banner::after{content:"🩸";position:absolute;right:-20px;bottom:-40px;font-size:200px;opacity:.1;}
        .cta-banner h2{font-size:clamp(26px,3.4vw,36px);margin-bottom:8px;}
        .cta-banner p{color:#ffe8ef;max-width:520px;}
        .cta-actions{display:flex;gap:12px;flex-wrap:wrap;position:relative;z-index:1;}
        .mini-btn{
            display:inline-block;text-decoration:none;background:#fff;color:var(--wine);padding:13px 24px;border-radius:999px;
            font-size:14px;font-weight:700;transition:all .2s;
        }
        .mini-btn:hover{transform:translateY(-3px);box-shadow:0 10px 20px rgba(0,0,0,0.25);}
        .mini-btn.ghost{background:transparent;color:#fff;border:2px solid rgba(255,255,255,.85);padding:11px 22px;}
        .mini-btn.ghost:hover{background:#fff;color:var(--wine);}

        /* STORIES */
        .stories-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;}
        .story-card{background:#fff;border-radius:20px;box-shadow:var(--shadow);padding:28px;position:relative;}
        .story-card::before{content:"\201C";position:absolute;top:6px;right:20px;font-size:70px;color:#f3cdd8;line-height:1;font-family:Georgia;}
        .stars{color:#f5a623;letter-spacing:2px;margin-bottom:8px;}
        .story-card p{color:#4a3a40;margin-bottom:16px;font-size:15px;}
        .story-who{display:flex;align-items:center;gap:12px;}
        .avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--wine),var(--rose));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;}
        .story-name{font-weight:700;color:var(--wine);font-size:15px;line-height:1.2;}
        .story-name small{display:block;color:var(--muted);font-weight:400;font-size:12px;}

        .stories-marquee{overflow:hidden;padding:10px 0 20px;-webkit-mask-image:linear-gradient(90deg,transparent,#000 6%,#000 94%,transparent);mask-image:linear-gradient(90deg,transparent,#000 6%,#000 94%,transparent);}
        .stories-track{display:flex;gap:24px;width:max-content;animation:storyScroll 50s linear infinite;}
        .stories-marquee:hover .stories-track{animation-play-state:paused;}
        .stories-track .story-card{width:340px;flex:none;}
        @keyframes storyScroll{to{transform:translateX(calc(-50% - 12px));}}

        /* FAQ */
        .faq{max-width:820px;margin:auto;}
        .faq-item{background:#fff;border-radius:16px;margin-bottom:12px;box-shadow:0 4px 14px rgba(122,21,56,0.08);overflow:hidden;}
        .faq-q{
            width:100%;text-align:left;background:none;border:none;padding:18px 22px;font-size:16px;font-weight:600;color:var(--wine);
            cursor:pointer;display:flex;justify-content:space-between;align-items:center;font-family:inherit;
        }
        .faq-q::after{content:"+";font-size:24px;transition:transform .25s;}
        .faq-item.open .faq-q::after{transform:rotate(45deg);}
        .faq-a{max-height:0;overflow:hidden;transition:max-height .35s ease;padding:0 22px;color:var(--muted);font-size:15px;}
        .faq-item.open .faq-a{max-height:200px;padding-bottom:18px;}

        /* REVEAL */
        .reveal{opacity:0;transform:translateY(26px);transition:opacity .6s ease, transform .6s ease;}
        .reveal.show{opacity:1;transform:none;}

        /* BACK TO TOP */
        #toTop{
            position:fixed;left:20px;bottom:24px;width:46px;height:46px;border-radius:50%;border:none;background:var(--wine);color:#fff;
            font-size:20px;cursor:pointer;box-shadow:0 8px 20px rgba(0,0,0,.25);opacity:0;pointer-events:none;transition:opacity .25s;z-index:9000;
        }
        #toTop.show{opacity:1;pointer-events:auto;}

        /* FOOTER */
        footer{background:linear-gradient(160deg,var(--wine-dark),var(--wine));color:white;padding:60px 0 24px;}
        .footer-content{display:grid;grid-template-columns:1.6fr 1fr 1fr 1.2fr;gap:30px;}
        .footer-logo{width:64px;height:64px;object-fit:cover;border-radius:50%;background:#fff;padding:3px;margin-bottom:12px;display:block;}
        .footer-content h3{margin-bottom:12px;font-size:18px;}
        .footer-content p{color:#ffd9e4;font-size:14px;}
        .footer-links ul{list-style:none;}
        .footer-links li{margin-bottom:8px;font-size:15px;}
        .footer-links a{color:#ffd9e4;text-decoration:none;}
        .footer-links a:hover{color:#fff;text-decoration:underline;}
        .footer-bottom{text-align:center;margin-top:36px;padding-top:18px;border-top:1px solid rgba(255,255,255,.2);font-size:14px;color:#ffd9e4;}

        /* CHATBOT */
        #chatbot-container{position:fixed;bottom:150px;right:20px;width:340px;z-index:99999;transform-origin:bottom right;animation:chatFloatIn .6s ease-out;display:none;}
        #chatbot-container.open{display:block;}
        #chat-header{
            background:linear-gradient(135deg,#7a1538,#a61d4d);color:white;padding:15px 16px;border-radius:16px 16px 0 0;cursor:pointer;
            font-weight:bold;display:flex;align-items:center;justify-content:space-between;box-shadow:0 6px 18px rgba(0,0,0,.25);
        }
        #chat-header .chat-title{display:flex;align-items:center;gap:10px;font-size:16px;}
        #chat-header .chat-icon{width:34px;height:34px;border-radius:50%;background:white;display:flex;align-items:center;justify-content:center;font-size:18px;}
        #chat-toggle-icon{font-size:18px;}
        #chat-body{display:block;background:white;border-radius:0 0 16px 16px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.2);border:1px solid #ddd;border-top:none;}
        #chat-messages{height:270px;overflow-y:auto;padding:12px;background:#fff7fa;}
        .bot-msg,.user-msg{margin:8px 0;padding:10px 12px;border-radius:12px;max-width:85%;clear:both;font-size:14px;line-height:1.5;position:relative;}
        .bot-msg{background:#f1f1f1;color:#333;float:left;border-top-left-radius:4px;border:1px solid #e6e6e6;padding-left:42px;}
        .bot-msg::before{content:"";position:absolute;left:10px;top:50%;transform:translateY(-50%);width:24px;height:24px;border-radius:50%;background:url('assets/img/bot-avatar.png') center/cover no-repeat #fff;border:1px solid #ddd;}
        .user-msg{background:#7a1538;color:white;float:right;border-top-right-radius:4px;padding-right:42px;}
        .user-msg::after{content:"";position:absolute;right:10px;top:50%;transform:translateY(-50%);width:24px;height:24px;border-radius:50%;background:url('assets/img/user-avatar.png') center/cover no-repeat;border:1px solid rgba(255,255,255,.55);}
        .typing-wrap{display:inline-flex;align-items:center;gap:4px;height:16px;}
        .typing-wrap span{width:6px;height:6px;border-radius:50%;background:#888;animation:dotBounce 1s infinite ease-in-out;}
        .typing-wrap span:nth-child(2){animation-delay:.15s;}
        .typing-wrap span:nth-child(3){animation-delay:.3s;}
        @keyframes dotBounce{0%,80%,100%{transform:scale(.7);opacity:.6;}40%{transform:scale(1);opacity:1;}}
        #chat-input-area{display:flex;border-top:1px solid #ddd;background:white;}
        #chat-input-area input{flex:1;padding:12px;border:none;outline:none;font-size:14px;}
        #chat-input-area button{background:#d32f2f;color:white;border:none;padding:0 18px;cursor:pointer;font-weight:bold;}
        #chat-input-area button:hover{background:#b71c1c;}
        #chat-open-btn{
            position:fixed;bottom:85px;right:20px;width:66px;height:66px;border-radius:50%;
            background:linear-gradient(135deg,#7a1538,#d32f2f 70%,#ff5a4f);color:white;display:flex;align-items:center;justify-content:center;
            font-size:29px;cursor:pointer;z-index:99998;border:2px solid rgba(255,255,255,.35);animation:chatPulse 2.2s infinite;
        }
        @keyframes chatPulse{
            0%{box-shadow:0 10px 24px rgba(0,0,0,.28),0 0 0 0 rgba(211,47,47,.45);}
            70%{box-shadow:0 10px 24px rgba(0,0,0,.28),0 0 0 14px rgba(211,47,47,0);}
            100%{box-shadow:0 10px 24px rgba(0,0,0,.28),0 0 0 0 rgba(211,47,47,0);}
        }
        @keyframes chatFloatIn{from{opacity:0;transform:translateY(14px) scale(.96);}to{opacity:1;transform:none;}}

        /* ACTIVITIES */
        .act-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;}
        .act-card{background:#fff;border-radius:22px;overflow:hidden;box-shadow:var(--shadow);display:flex;flex-direction:column;transition:transform .25s;}
        .act-card:hover{transform:translateY(-8px);}
        .act-img{height:170px;background-size:cover;background-position:center;position:relative;}
        .act-img::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,transparent 40%,rgba(78,13,37,.75));}
        .act-tag{position:absolute;left:14px;bottom:12px;z-index:1;color:#fff;font-weight:700;font-size:12px;letter-spacing:1.5px;text-transform:uppercase;background:rgba(192,36,85,.9);padding:4px 12px;border-radius:999px;}
        .act-body{padding:22px;flex:1;}
        .act-body h3{color:var(--wine);font-size:20px;margin-bottom:6px;}
        .act-body p{color:var(--muted);font-size:15px;margin-bottom:12px;}
        .act-meta{display:flex;gap:14px;flex-wrap:wrap;font-size:13px;font-weight:600;color:var(--rose);}
        .act-icons{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:30px;}
        .act-icon{background:var(--blush);border-radius:18px;padding:20px 14px;text-align:center;}
        .act-icon span{font-size:30px;display:block;margin-bottom:6px;}
        .act-icon b{display:block;color:var(--wine);font-family:'Outfit';}
        .act-icon small{color:var(--muted);}

        /* AI SECTION */
        .ai-section{background:linear-gradient(135deg,#2a0716,#5a0c28 55%,#7a1538);color:#fff;padding:84px 0;position:relative;overflow:hidden;}
        .ai-section::before,.ai-section::after{content:"";position:absolute;border-radius:50%;filter:blur(60px);opacity:.5;}
        .ai-section::before{width:380px;height:380px;background:#c02455;left:-120px;top:-100px;}
        .ai-section::after{width:320px;height:320px;background:#6a3dff;right:-100px;bottom:-100px;opacity:.3;}
        .ai-section .container{position:relative;z-index:1;}
        .ai-section .eyebrow{color:#ffb3c8;}
        .ai-section .title{color:#fff;}
        .ai-section .sub{color:#ffd9e4;}
        .ai-pill{display:inline-block;background:linear-gradient(90deg,#ff5a8a,#8b5cff);padding:2px 10px;border-radius:999px;font-size:11px;font-weight:800;letter-spacing:1px;margin-left:8px;vertical-align:middle;}
        .ai-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:34px;}
        .ai-card{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.2);backdrop-filter:blur(10px);border-radius:20px;padding:26px;transition:transform .25s,background .25s;}
        .ai-card:hover{transform:translateY(-6px);background:rgba(255,255,255,.14);}
        .ai-card .ic{width:50px;height:50px;border-radius:14px;background:linear-gradient(135deg,#ff5a8a,#8b5cff);display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:12px;}
        .ai-card h3{font-size:19px;margin-bottom:6px;}
        .ai-card p{font-size:14px;color:#ffd9e4;}
        .ai-demo{display:grid;grid-template-columns:1fr 1fr;gap:24px;}
        .ai-panel{background:#fff;color:var(--ink);border-radius:22px;padding:28px;box-shadow:0 20px 50px rgba(0,0,0,.35);}
        .ai-panel h3{color:var(--wine);font-size:20px;margin-bottom:4px;}
        .ai-panel > p{font-size:14px;color:var(--muted);margin-bottom:14px;}
        .ai-panel label{display:block;font-size:13px;font-weight:600;margin:10px 0 4px;}
        .ai-panel select{width:100%;padding:11px 12px;border:1.5px solid #e6cfd8;border-radius:12px;font-size:15px;font-family:inherit;}
        .ai-panel .btn{width:100%;margin-top:14px;padding:12px;box-shadow:none;background:linear-gradient(135deg,#c02455,#8b5cff);}
        .match-row{display:flex;align-items:center;gap:10px;margin-top:10px;font-size:14px;}
        .match-row b{width:38px;font-family:'Outfit';color:var(--wine);}
        .match-bar{flex:1;height:10px;background:#f3e4ea;border-radius:99px;overflow:hidden;}
        .match-bar i{display:block;height:100%;width:0;background:linear-gradient(90deg,#c02455,#ff7a9a);border-radius:99px;transition:width .9s ease;}
        .match-row em{font-style:normal;font-weight:700;width:42px;text-align:right;color:var(--muted);}
        .forecast{display:grid;grid-template-columns:repeat(8,1fr);gap:8px;align-items:end;height:170px;margin-top:10px;}
        .fc-col{display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;gap:4px;font-size:12px;font-weight:700;color:var(--wine);}
        .fc-bar{width:100%;border-radius:8px 8px 0 0;height:0;transition:height 1s ease;position:relative;}
        .fc-bar.ok{background:linear-gradient(180deg,#34c47c,#1f8a4c);}
        .fc-bar.mid{background:linear-gradient(180deg,#ffc34d,#e69a00);}
        .fc-bar.low{background:linear-gradient(180deg,#ff6a6a,#c62828);}
        .fc-legend{display:flex;gap:14px;flex-wrap:wrap;font-size:12px;color:var(--muted);margin-top:12px;}
        .fc-legend i{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:5px;}
        .ai-note{font-size:12px;color:var(--muted);margin-top:10px;}

        /* DID YOU KNOW */
        .know-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;}
        .know-card{background:#fff;border-radius:20px;padding:26px 20px;box-shadow:var(--shadow);text-align:center;border-bottom:4px solid var(--rose);}
        .know-card big{display:block;font-family:'Outfit';font-weight:800;font-size:44px;color:var(--wine);line-height:1;margin-bottom:6px;}
        .know-card p{font-size:14px;color:var(--muted);}
        .dist-wrap{margin-top:34px;background:#fff;border-radius:22px;padding:28px;box-shadow:var(--shadow);}
        .dist-wrap h3{color:var(--wine);margin-bottom:14px;}
        .dist-row{display:flex;align-items:center;gap:12px;margin-bottom:9px;font-size:14px;}
        .dist-row b{width:36px;font-family:'Outfit';color:var(--wine);}
        .dist-row .match-bar{height:12px;}
        .dist-row span{width:120px;color:var(--muted);font-size:12px;}

        .chat-chips{display:flex;gap:6px;flex-wrap:wrap;padding:8px 10px;background:#fff7fa;border-top:1px solid #f0dde4;}
        .chat-chips button{border:1px solid var(--rose);background:#fff;color:var(--wine);border-radius:999px;padding:4px 10px;font-size:12px;cursor:pointer;font-weight:600;}
        .chat-chips button:hover{background:var(--wine);color:#fff;}

        /* RESPONSIVE */
        @media(max-width:1000px){
            .act-grid,.ai-grid,.ai-demo{grid-template-columns:1fr;}
            .act-icons,.know-grid{grid-template-columns:repeat(2,1fr);}
            .hero .container{grid-template-columns:1fr;}
            .hero-card{max-width:420px;}
            .qa-grid,.myth-grid,.impact-grid,.steps-grid{grid-template-columns:repeat(2,1fr);}
            .tools,.stories-grid{grid-template-columns:1fr;}
            .footer-content{grid-template-columns:1fr 1fr;}
        }
        @media(max-width:768px){
            .menu-toggle{display:block;}
            .menu{display:none;position:absolute;top:100%;left:0;right:0;background:var(--wine);flex-direction:column;padding:16px 6%;gap:14px;align-items:flex-start;}
            .menu.open{display:flex;}
            .dropdown-menu{position:static;box-shadow:none;margin-top:8px;}
            .info-panels{grid-template-columns:1fr;}
            .section{padding:56px 0;}
            .cta-banner{padding:32px 24px;}
            .hero{min-height:auto;}
            .quick-actions{margin-top:-30px;}
            #chatbot-container{right:12px;bottom:130px;width:min(92vw,340px);}
            #chat-open-btn{right:12px;bottom:78px;width:60px;height:60px;font-size:26px;}
        }
        @media(max-width:520px){
            .qa-grid,.myth-grid,.impact-grid,.steps-grid,.footer-content,.act-icons,.know-grid{grid-template-columns:1fr;}
        }
        @media(prefers-reduced-motion:reduce){
            *{animation:none !important;transition:none !important;}
            .reveal{opacity:1;transform:none;}
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header>
    <div class="container nav">
        <div class="logo">
            <img src="assets/img/logo.jpg" alt="Lifeline logo">
            <h2>Lifeline Blood Bank</h2>
        </div>
        <button class="menu-toggle" aria-label="Toggle menu" onclick="document.querySelector('.menu').classList.toggle('open')">☰</button>
        <ul class="menu">
            <li><a href="index.php" class="active">HOME</a></li>
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

<!-- HERO -->
<section class="hero">
    <div class="container">
        <div>
            <span class="hero-badge"><i></i> Open today · Mon–Sat, 9 AM – 5 PM</span>
            <h1>Your one donation can <em>save three lives.</em></h1>
            <p class="lead">Join Lifeline Blood Bank's community of voluntary donors. Quick, safe, and guided by caring professionals every step of the way.</p>
            <div class="hero-actions">
                <a href="donate.php" class="btn">🩸 Donate Now</a>
                <a href="find.php" class="btn-outline">Find Blood</a>
            </div>
            <div class="hero-trust">
                <span>Sterile single-use kits</span>
                <span>Free health screening</span>
                <span>24/7 emergency line</span>
            </div>
        </div>
        <div class="hero-card">
            <div class="drop"><span>+</span></div>
            <h3>Every 2 seconds</h3>
            <p>someone needs blood. Be the reason they get it.</p>
            <div class="hero-card-stats">
                <div><b>12,500+</b><small>Units collected</small></div>
                <div><b>8,200+</b><small>Lives supported</small></div>
            </div>
        </div>
    </div>
</section>

<!-- QUICK ACTIONS -->
<section class="quick-actions">
    <div class="container qa-grid">
        <a href="donate.php" class="qa-card reveal"><div class="qa-icon">🩸</div><h3>Become a Donor</h3><p>Register & book your slot in a minute.</p></a>
        <a href="request.php" class="qa-card reveal"><div class="qa-icon">🚑</div><h3>Request Blood</h3><p>Raise an urgent request online.</p></a>
        <a href="find.php" class="qa-card reveal"><div class="qa-icon">🔍</div><h3>Find Blood</h3><p>Check availability by blood group.</p></a>
        <a href="camp.php" class="qa-card reveal"><div class="qa-icon">📍</div><h3>Donation Camps</h3><p>Find an upcoming camp near you.</p></a>
    </div>
</section>

<!-- EMERGENCY STRIP -->
<section class="urgent-strip" style="margin-top:50px;">
    🚨 Emergency need? Call <span>+91 99001 63000</span> or <a href="request.php">raise a request online</a>
</section>

<!-- IMPACT -->
<section class="impact-section reveal">
    <div class="container">
        <span class="eyebrow">Our Impact</span>
        <h2 class="title" style="margin-bottom:34px;">Numbers that matter</h2>
        <div class="impact-grid">
            <div class="impact-card"><h3 class="impact-count" data-target="12500" data-suffix="+">12,500+</h3><p>Units Collected</p></div>
            <div class="impact-card"><h3 class="impact-count" data-target="8200" data-suffix="+">8,200+</h3><p>Lives Supported</p></div>
            <div class="impact-card"><h3 class="impact-count" data-target="110" data-suffix="+">110+</h3><p>Camps Organized</p></div>
            <div class="impact-card"><h3 class="impact-count" data-target="24" data-suffix="/7">24/7</h3><p>Emergency Availability</p></div>
        </div>
    </div>
</section>

<!-- ACTIVITIES -->
<section class="section">
    <div class="container reveal">
        <span class="eyebrow">What We Do</span>
        <h2 class="title">Activities &amp; programs</h2>
        <p class="sub">Beyond collecting blood, Lifeline runs year-round programs that build a culture of voluntary donation.</p>
        <div class="act-grid">
            <div class="act-card">
                <div class="act-img" style="background-image:url('assets/img/event1.jpg')"><span class="act-tag">Camps</span></div>
                <div class="act-body"><h3>Community Donation Camps</h3><p>Mobile units set up in colleges, offices and neighbourhoods with trained staff and refreshments.</p>
                <div class="act-meta"><span>📍 110+ camps</span><span>🗓 Every month</span></div></div>
            </div>
            <div class="act-card">
                <div class="act-img" style="background-image:url('assets/img/event2.jpg')"><span class="act-tag">Awareness</span></div>
                <div class="act-body"><h3>Awareness &amp; School Drives</h3><p>Talks, posters and myth-busting sessions that educate young people about safe voluntary donation.</p>
                <div class="act-meta"><span>🎓 40+ campuses</span><span>🎤 Free sessions</span></div></div>
            </div>
            <div class="act-card">
                <div class="act-img" style="background-image:url('assets/img/event3.jpg')"><span class="act-tag">Emergency</span></div>
                <div class="act-body"><h3>Emergency Response Network</h3><p>A 24/7 donor network that is alerted when a rare group or urgent request needs a quick match.</p>
                <div class="act-meta"><span>⚡ Fast alerts</span><span>🩸 All groups</span></div></div>
            </div>
        </div>
        <div class="act-icons">
            <div class="act-icon"><span>🏅</span><b>Donor Recognition</b><small>Certificates &amp; milestones</small></div>
            <div class="act-icon"><span>🩺</span><b>Free Health Check</b><small>BP, Hb and more</small></div>
            <div class="act-icon"><span>🎂</span><b>World Donor Day</b><small>Annual celebration, 14 June</small></div>
            <div class="act-icon"><span>🤝</span><b>Volunteer Program</b><small>Join our camp crew</small></div>
        </div>
    </div>
</section>

<!-- INTERACTIVE TOOLS -->
<section class="section light-bg">
    <div class="container reveal">
        <span class="eyebrow">Smart Tools</span>
        <h2 class="title">Know before you go</h2>
        <p class="sub">Three quick tools to check compatibility, eligibility and your next donation date.</p>

        <div class="tools">
            <!-- Compatibility -->
            <div class="tool">
                <h3>🧬 Blood Compatibility</h3>
                <p>Pick a blood group to see who you can give to and receive from.</p>
                <div class="bg-picker" id="bgPicker"></div>
                <div class="result" id="compatResult">Select a blood group above.</div>
            </div>

            <!-- Eligibility -->
            <div class="tool">
                <h3>✅ Eligibility Check</h3>
                <p>A quick self-check. Final eligibility is confirmed by our staff.</p>
                <label for="elAge">Age (years)</label>
                <input type="number" id="elAge" min="1" max="100" placeholder="e.g. 28">
                <label for="elWeight">Weight (kg)</label>
                <input type="number" id="elWeight" min="1" max="250" placeholder="e.g. 62">
                <label for="elHealth">Feeling healthy today?</label>
                <select id="elHealth">
                    <option value="yes">Yes, no fever or infection</option>
                    <option value="no">No, I have a fever / cold / on antibiotics</option>
                </select>
                <button class="btn" onclick="checkEligibility()">Check Eligibility</button>
                <div class="result" id="eligResult">Fill in your details above.</div>
            </div>

            <!-- Next donation -->
            <div class="tool">
                <h3>📅 Next Donation Date</h3>
                <p>Whole-blood donors should wait before donating again.</p>
                <label for="lastDate">Last donation date</label>
                <input type="date" id="lastDate">
                <label for="gender">Gender</label>
                <select id="gender">
                    <option value="90">Male (wait 90 days)</option>
                    <option value="120">Female (wait 120 days)</option>
                </select>
                <button class="btn" onclick="nextDonation()">Calculate</button>
                <div class="result" id="nextResult">Choose your last donation date.</div>
            </div>
        </div>
    </div>
</section>

<!-- AI FEATURES -->
<section class="ai-section" id="ai">
    <div class="container reveal">
        <span class="eyebrow">Powered by AI <span class="ai-pill">NEW</span></span>
        <h2 class="title">Smarter blood banking</h2>
        <p class="sub">We use intelligent tools to match faster, plan stock better and answer your questions any time of day.</p>

        <div class="ai-grid">
            <div class="ai-card"><div class="ic">🤖</div><h3>AI Assistant</h3><p>Chat 24/7 for eligibility, camps, requests and first-time donor tips. Tap the 💬 button.</p></div>
            <div class="ai-card"><div class="ic">🎯</div><h3>Smart Donor Matching</h3><p>Ranks compatible blood groups by match quality and urgency so the best units are suggested first.</p></div>
            <div class="ai-card"><div class="ic">📈</div><h3>Demand Forecasting</h3><p>Predicts which blood groups may run low in the coming days, so we can call donors before shortages.</p></div>
            <div class="ai-card"><div class="ic">🔔</div><h3>Personalised Reminders</h3><p>Knows your waiting period and nudges you when you're eligible to donate again.</p></div>
            <div class="ai-card"><div class="ic">🚨</div><h3>Urgency Triage</h3><p>Prioritises emergency requests by blood group rarity, stock level and time needed.</p></div>
            <div class="ai-card"><div class="ic">📍</div><h3>Camp Recommendations</h3><p>Suggests the nearest upcoming camp and best time slot based on past turnout.</p></div>
        </div>

        <div class="ai-demo">
            <div class="ai-panel">
                <h3>🎯 Try Smart Match</h3>
                <p>Select the blood group a patient needs and see which donor groups are the best match.</p>
                <label for="aiNeed">Patient needs</label>
                <select id="aiNeed">
                    <option>O-</option><option>O+</option><option>A-</option><option>A+</option>
                    <option>B-</option><option>B+</option><option>AB-</option><option>AB+</option>
                </select>
                <label for="aiUrgency">Urgency</label>
                <select id="aiUrgency">
                    <option value="1">Planned</option>
                    <option value="2" selected>Urgent</option>
                    <option value="3">Critical</option>
                </select>
                <button class="btn" onclick="runAiMatch()">Find best donors</button>
                <div id="aiMatchOut"></div>
                <div class="ai-note">Demo using standard ABO/Rh compatibility rules. Final matching is done by cross-match in the lab.</div>
            </div>
            <div class="ai-panel">
                <h3>📈 Stock Forecast (next 7 days)</h3>
                <p>Illustrative projection of availability by blood group. Visit <a href="find.php" style="color:var(--rose);font-weight:700">Find Blood</a> for live data.</p>
                <div class="forecast" id="forecast"></div>
                <div class="fc-legend"><span><i style="background:#1f8a4c"></i>Healthy</span><span><i style="background:#e69a00"></i>Watch</span><span><i style="background:#c62828"></i>Donors needed</span></div>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="section">
    <div class="container reveal">
        <span class="eyebrow">Simple &amp; Safe</span>
        <h2 class="title">How donation works</h2>
        <p class="sub">From registration to recovery, every step is designed to keep donors comfortable and recipients safe.</p>
        <div class="steps-grid">
            <div class="step-card"><span class="step-number">1</span><h3>Register</h3><p>Create your donor profile and share your blood group.</p></div>
            <div class="step-card"><span class="step-number">2</span><h3>Screening</h3><p>Staff check vitals and eligibility for your safety.</p></div>
            <div class="step-card"><span class="step-number">3</span><h3>Donate</h3><p>The donation itself takes only about 10–15 minutes.</p></div>
            <div class="step-card"><span class="step-number">4</span><h3>Recover</h3><p>Rest, enjoy refreshments, and leave with a life-saving impact.</p></div>
        </div>
    </div>
</section>

<!-- DID YOU KNOW -->
<section class="section">
    <div class="container reveal">
        <span class="eyebrow">Good to Know</span>
        <h2 class="title">Blood facts at a glance</h2>
        <p class="sub">A few numbers that show why every donor counts.</p>
        <div class="know-grid">
            <div class="know-card"><big>3</big><p>Lives can be supported by a single donation (red cells, plasma, platelets).</p></div>
            <div class="know-card"><big>10 min</big><p>The actual blood draw takes only 10–15 minutes.</p></div>
            <div class="know-card"><big>42 days</big><p>Red cells can be stored up to 42 days, so regular donors are vital.</p></div>
            <div class="know-card"><big>5 days</big><p>Platelets last only about 5 days and are always in demand.</p></div>
        </div>
        <div class="dist-wrap">
            <h3>🧬 How common is each blood group? (India, approx.)</h3>
            <div id="distList"></div>
        </div>
    </div>
</section>

<!-- GUIDELINES -->
<section class="section light-bg">
    <div class="container reveal">
        <span class="eyebrow">Guidelines</span>
        <h2 class="title">Can you donate today?</h2>
        <p class="sub">Read this before visiting a camp so you know whether to donate now or wait.</p>
        <div class="info-panels">
            <div class="info-panel good">
                <h3>You can donate if…</h3>
                <ul>
                    <li>You are at least 18 years old.</li>
                    <li>You weigh 50 kg or more.</li>
                    <li>You feel healthy on the donation day.</li>
                    <li>Blood pressure and hemoglobin are normal.</li>
                    <li>You carry a valid photo ID.</li>
                </ul>
            </div>
            <div class="info-panel bad">
                <h3>Please wait if…</h3>
                <ul>
                    <li>You have fever, cold, or are on antibiotics.</li>
                    <li>You had recent surgery, tattoo, or piercing.</li>
                    <li>You are pregnant or recently delivered.</li>
                    <li>You have very low weight or hemoglobin.</li>
                    <li>Your previous donation gap isn't complete.</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- MYTHS -->
<section class="section">
    <div class="container reveal">
        <span class="eyebrow">Myth Busters</span>
        <h2 class="title">Myths vs. facts</h2>
        <p class="sub">Hover or tap a card to reveal the truth.</p>
        <div class="myth-grid">
            <div class="flip" onclick="this.classList.toggle('flipped')"><div class="flip-inner">
                <div class="flip-face flip-front"><b>MYTH</b><span>Donating makes you weak for days.</span></div>
                <div class="flip-face flip-back"><b>FACT</b><span>Most donors recover within hours with rest and fluids.</span></div>
            </div></div>
            <div class="flip" onclick="this.classList.toggle('flipped')"><div class="flip-inner">
                <div class="flip-face flip-front"><b>MYTH</b><span>Donating blood is unsafe.</span></div>
                <div class="flip-face flip-back"><b>FACT</b><span>Sterile, single-use kits make donation completely safe.</span></div>
            </div></div>
            <div class="flip" onclick="this.classList.toggle('flipped')"><div class="flip-inner">
                <div class="flip-face flip-front"><b>MYTH</b><span>Only rare blood groups are needed.</span></div>
                <div class="flip-face flip-back"><b>FACT</b><span>All blood groups are needed regularly.</span></div>
            </div></div>
            <div class="flip" onclick="this.classList.toggle('flipped')"><div class="flip-inner">
                <div class="flip-face flip-front"><b>MYTH</b><span>One donor doesn't make a difference.</span></div>
                <div class="flip-face flip-back"><b>FACT</b><span>One donation can support up to three patients.</span></div>
            </div></div>
        </div>
    </div>
</section>

<!-- CTA BANNER -->
<section class="section" style="padding-top:0;">
    <div class="container reveal">
        <div class="cta-banner">
            <div>
                <h2>Need blood urgently?</h2>
                <p>Raise a request, search availability, and reach our emergency team — all in one place.</p>
            </div>
            <div class="cta-actions">
                <a href="request.php" class="mini-btn">Raise Request</a>
                <a href="find.php" class="mini-btn ghost">Find Blood</a>
                <a href="contact.php" class="mini-btn ghost">Contact Team</a>
            </div>
        </div>
    </div>
</section>

<!-- STORIES -->
<section class="section light-bg">
    <div class="container reveal">
        <span class="eyebrow">Testimonials</span>
        <h2 class="title">Donor voices</h2>
        <p class="sub">Donating with Lifeline is often described as one of the most meaningful decisions people make.</p>
        <div class="stories-marquee"><div class="stories-track" id="storiesTrack">
            <div class="story-card"><div class="stars">★★★★★</div><p>"The process was smooth and the team made me feel comfortable from start to finish."</p>
                <div class="story-who"><div class="avatar">A</div><div class="story-name">Ananya<small>First-time Donor</small></div></div></div>
            <div class="story-card"><div class="stars">★★★★★</div><p>"I received an urgent call and was able to help within hours. It felt deeply rewarding."</p>
                <div class="story-who"><div class="avatar">R</div><div class="story-name">Raghav<small>Regular Donor</small></div></div></div>
            <div class="story-card"><div class="stars">★★★★★</div><p>"Our college camp was organized professionally. Many students registered again."</p>
                <div class="story-who"><div class="avatar">P</div><div class="story-name">Priya<small>Campus Coordinator</small></div></div></div>
            <div class="story-card"><div class="stars">★★★★★</div><p>"Quick screening, friendly staff and a free health check. I donate every three months now."</p>
                <div class="story-who"><div class="avatar">S</div><div class="story-name">Suresh<small>Donor, 12 donations</small></div></div></div>
            <div class="story-card"><div class="stars">★★★★★</div><p>"Volunteering at the camps showed me how much one unit of blood can change a family's day."</p>
                <div class="story-who"><div class="avatar">M</div><div class="story-name">Meera<small>Volunteer</small></div></div></div>
            <div class="story-card"><div class="stars">★★★★★</div><p>"When my brother needed blood urgently, Lifeline found a match in under an hour."</p>
                <div class="story-who"><div class="avatar">K</div><div class="story-name">Karthik<small>Recipient's Brother</small></div></div></div>
        </div></div>
    </div>
</section>

<!-- FAQ -->
<section class="section">
    <div class="container reveal">
        <span class="eyebrow">FAQ</span>
        <h2 class="title">Frequently asked questions</h2>
        <p class="sub">Quick answers to what donors ask us most.</p>
        <div class="faq">
            <div class="faq-item"><button class="faq-q">How long does donation take?</button><div class="faq-a">The donation itself takes 10–15 minutes. With registration, screening and rest, plan for about 45 minutes.</div></div>
            <div class="faq-item"><button class="faq-q">Does it hurt?</button><div class="faq-a">You'll feel a brief pinch when the needle goes in. Most donors report little to no discomfort afterwards.</div></div>
            <div class="faq-item"><button class="faq-q">How often can I donate?</button><div class="faq-a">Men can donate every 90 days and women every 120 days for whole blood.</div></div>
            <div class="faq-item"><button class="faq-q">What should I eat before donating?</button><div class="faq-a">Have a light, healthy meal and drink plenty of water. Avoid donating on an empty stomach.</div></div>
            <div class="faq-item"><button class="faq-q">What happens to my blood after donation?</button><div class="faq-a">It is tested, separated into red cells, plasma and platelets, and matched to patients who need it.</div></div>
        </div>
    </div>
</section>

<button id="toTop" aria-label="Back to top" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<!-- FOOTER -->
<footer>
    <div class="container footer-content">
        <div class="footer-left">
            <img src="assets/img/logo.jpg" alt="Lifeline Blood Bank logo" class="footer-logo">
            <h3>Lifeline Blood Bank</h3>
            <p>Thank you for supporting voluntary blood donation. Together, we can ensure no patient suffers due to lack of blood.</p>
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
                <li><a href="find.php">Find Blood</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </div>
        <div class="footer-links">
            <h3>Get in touch</h3>
            <ul>
                <li>📞 +91 99001 63000</li>
                <li>✉️ emergency@lifeline.com</li>
                <li>🕘 Mon–Sat, 9 AM – 5 PM</li>
            </ul>
        </div>
    </div>

    <!-- CHAT OPEN BUTTON -->
    <div id="chat-open-btn" onclick="toggleChat()" aria-label="Open blood bank assistant" title="Chat with assistant"><span>💬</span></div>

    <!-- CHATBOT -->
    <div id="chatbot-container">
        <div id="chat-header" onclick="toggleChat()">
            <div class="chat-title">
                <div class="chat-icon">🩸</div>
                <span>Blood Bank Assistant</span>
            </div>
            <span id="chat-toggle-icon">−</span>
        </div>
        <div id="chat-body">
            <div id="chat-messages">
                <div class="bot-msg">Hello 👋 Welcome to Lifeline Blood Bank. Ask me about donation, blood groups, requests, or contact support.</div>
            </div>
            <div class="chat-chips">
                <button onclick="quickAsk('Can I donate?')">Can I donate?</button>
                <button onclick="quickAsk('Blood group info')">Blood groups</button>
                <button onclick="quickAsk('Upcoming camps')">Camps</button>
                <button onclick="quickAsk('Request blood')">Request</button>
            </div>
            <div id="chat-input-area">
                <input type="text" id="userInput" placeholder="Ask something...">
                <button onclick="sendMessage()">Send</button>
            </div>
        </div>
    </div>

    <div class="container footer-bottom">
        &copy; <?php echo date('Y'); ?> Lifeline Blood Bank. All rights reserved.
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Login dropdown
    const toggle = document.querySelector('.login-toggle');
    const menu = document.querySelector('.dropdown-menu');
    if (toggle && menu) {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            menu.classList.toggle('show');
        });
    }

    // Scroll reveal
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });
    document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));

    // Counters
    const animateCount = (el) => {
        const target = parseInt(el.dataset.target, 10) || 0;
        const suffix = el.dataset.suffix || '';
        const start = performance.now();
        const step = (now) => {
            const p = Math.min((now - start) / 1400, 1);
            el.textContent = Math.floor(p * target).toLocaleString() + (p === 1 ? suffix : '');
            if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };
    // Counts up automatically each time the section scrolls into view
    const impact = document.querySelector('.impact-section');
    if (impact) {
        const co = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                document.querySelectorAll('.impact-count').forEach(animateCount);
            }
        }, { threshold: 0.2 });
        co.observe(impact);
    }

    // Testimonials: duplicate cards so the marquee loops seamlessly
    const track = document.getElementById('storiesTrack');
    if (track) {
        Array.from(track.children).forEach((c) => {
            const d = c.cloneNode(true);
            d.setAttribute('aria-hidden', 'true');
            track.appendChild(d);
        });
    }

    // FAQ accordion
    document.querySelectorAll('.faq-q').forEach((btn) => {
        btn.addEventListener('click', () => btn.parentElement.classList.toggle('open'));
    });

    // Back to top
    const toTop = document.getElementById('toTop');
    window.addEventListener('scroll', () => toTop.classList.toggle('show', window.scrollY > 500));

    // Blood compatibility
    const give = {
        'O-':['O-','O+','A-','A+','B-','B+','AB-','AB+'], 'O+':['O+','A+','B+','AB+'],
        'A-':['A-','A+','AB-','AB+'], 'A+':['A+','AB+'],
        'B-':['B-','B+','AB-','AB+'], 'B+':['B+','AB+'],
        'AB-':['AB-','AB+'], 'AB+':['AB+']
    };
    const picker = document.getElementById('bgPicker');
    const out = document.getElementById('compatResult');
    const chips = (arr, cls) => '<div class="chips">' + arr.map(g => '<span class="chip ' + cls + '">' + g + '</span>').join('') + '</div>';
    Object.keys(give).forEach((g) => {
        const b = document.createElement('button');
        b.textContent = g;
        b.addEventListener('click', () => {
            picker.querySelectorAll('button').forEach(x => x.classList.remove('on'));
            b.classList.add('on');
            const receive = Object.keys(give).filter(k => give[k].includes(g));
            out.innerHTML = '<b>' + g + ' can donate to:</b>' + chips(give[g], '') +
                            '<div style="margin-top:10px"><b>' + g + ' can receive from:</b></div>' + chips(receive, 'alt');
        });
        picker.appendChild(b);
    });
});

const COMPAT = {
    'O-':['O-','O+','A-','A+','B-','B+','AB-','AB+'], 'O+':['O+','A+','B+','AB+'],
    'A-':['A-','A+','AB-','AB+'], 'A+':['A+','AB+'],
    'B-':['B-','B+','AB-','AB+'], 'B+':['B+','AB+'],
    'AB-':['AB-','AB+'], 'AB+':['AB+']
};
// Smart match: exact group first, then Rh-compatible, then universal donor; urgency widens the pool
function runAiMatch() {
    const need = document.getElementById('aiNeed').value;
    const urgency = parseInt(document.getElementById('aiUrgency').value, 10);
    const rows = Object.keys(COMPAT).filter(d => COMPAT[d].includes(need)).map(d => {
        let score = 60;
        if (d === need) score = 98;
        else if (d.slice(0, -1) === need.slice(0, -1)) score = 88;      // same ABO, different Rh
        else if (d === 'O-') score = 80 + (urgency === 3 ? 10 : 0);     // universal donor in emergencies
        else if (d.endsWith('-')) score = 72;
        else score = 64;
        return { d, score: Math.min(score, 99) };
    }).sort((a, b) => b.score - a.score);
    const out = document.getElementById('aiMatchOut');
    out.innerHTML = rows.map(r =>
        '<div class="match-row"><b>' + r.d + '</b><div class="match-bar"><i data-w="' + r.score + '"></i></div><em>' + r.score + '%</em></div>'
    ).join('') + '<div class="ai-note" style="margin-top:12px"><b>Top suggestion:</b> ' + rows[0].d +
        (urgency === 3 ? ' — alerting nearby donors immediately.' : urgency === 2 ? ' — notifying matching donors today.' : ' — schedule at the next camp.') + '</div>';
    requestAnimationFrame(() => requestAnimationFrame(() =>
        out.querySelectorAll('.match-bar i').forEach(i => i.style.width = i.dataset.w + '%')));
}

// Forecast + distribution (illustrative data)
document.addEventListener('DOMContentLoaded', function () {
    const stock = [['O-',22],['O+',78],['A-',35],['A+',66],['B-',28],['B+',84],['AB-',18],['AB+',48]];
    const fc = document.getElementById('forecast');
    fc.innerHTML = stock.map(s => {
        const cls = s[1] >= 60 ? 'ok' : s[1] >= 35 ? 'mid' : 'low';
        return '<div class="fc-col">' + s[1] + '%<div class="fc-bar ' + cls + '" data-h="' + s[1] + '"></div>' + s[0] + '</div>';
    }).join('');
    const dist = [['B+',32,'most common'],['O+',27,''],['A+',21,''],['AB+',7,''],['O-',2,'universal donor'],['A-',2,''],['B-',2,''],['AB-',1,'rarest']];
    document.getElementById('distList').innerHTML = dist.map(d =>
        '<div class="dist-row"><b>' + d[0] + '</b><div class="match-bar"><i data-w="' + d[1] * 3 + '"></i></div><em style="font-style:normal;font-weight:700;width:44px">' + d[1] + '%</em><span>' + d[2] + '</span></div>').join('');
    const grow = new IntersectionObserver((es) => es.forEach(e => {
        if (!e.isIntersecting) return;
        e.target.querySelectorAll('.fc-bar').forEach(b => b.style.height = (b.dataset.h * 1.1) + 'px');
        e.target.querySelectorAll('.match-bar i').forEach(i => i.style.width = i.dataset.w + '%');
        grow.unobserve(e.target);
    }), { threshold: 0.3 });
    [fc, document.getElementById('distList')].forEach(el => grow.observe(el));
});

function quickAsk(text) {
    document.getElementById('userInput').value = text;
    sendMessage();
}

function setResult(id, html, cls) {
    const el = document.getElementById(id);
    el.className = 'result ' + (cls || '');
    el.innerHTML = html;
}

function checkEligibility() {
    const age = parseInt(document.getElementById('elAge').value, 10);
    const weight = parseFloat(document.getElementById('elWeight').value);
    const healthy = document.getElementById('elHealth').value === 'yes';
    if (!age || !weight) { setResult('eligResult', 'Please enter your age and weight.'); return; }
    const reasons = [];
    if (age < 18) reasons.push('Age should be at least 18 years.');
    if (weight < 50) reasons.push('Weight should be at least 50 kg.');
    if (!healthy) reasons.push('Please wait until you are fully recovered.');
    if (reasons.length) {
        setResult('eligResult', '❌ <b>Not eligible right now.</b><br>' + reasons.join('<br>'), 'no');
    } else {
        setResult('eligResult', '✅ <b>You look eligible!</b> <a href="donate.php" style="color:#145c2e;font-weight:700">Book your donation →</a>', 'ok');
    }
}

function nextDonation() {
    const v = document.getElementById('lastDate').value;
    if (!v) { setResult('nextResult', 'Please choose your last donation date.'); return; }
    const gap = parseInt(document.getElementById('gender').value, 10);
    const next = new Date(v);
    next.setDate(next.getDate() + gap);
    const fmt = next.toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' });
    const days = Math.ceil((next - new Date()) / 86400000);
    if (days <= 0) {
        setResult('nextResult', '✅ <b>You can donate today!</b> Your waiting period is over.', 'ok');
    } else {
        setResult('nextResult', '📅 <b>Next eligible date: ' + fmt + '</b><br>' + days + ' day' + (days === 1 ? '' : 's') + ' to go.');
    }
}

// Chatbot
function toggleChat() {
    const box = document.getElementById("chatbot-container");
    const openBtn = document.getElementById("chat-open-btn");
    const open = box.classList.toggle("open");
    openBtn.style.display = open ? "none" : "flex";
    if (open) document.getElementById("userInput").focus();
}

function sendMessage() {
    const input = document.getElementById("userInput");
    const message = input.value.trim().toLowerCase();
    const chatMessages = document.getElementById("chat-messages");
    if (message === "") return;

    const userDiv = document.createElement("div");
    userDiv.className = "user-msg";
    userDiv.textContent = input.value;
    chatMessages.appendChild(userDiv);

    const typingDiv = document.createElement("div");
    typingDiv.className = "bot-msg";
    typingDiv.innerHTML = '<div class="typing-wrap"><span></span><span></span><span></span></div>';
    chatMessages.appendChild(typingDiv);

    let botReply = "Sorry, I can help with donation, blood groups, requests, and contact information.";
    if (message.includes("camp")) {
        botReply = "Check the CAMP page for upcoming donation camps near you and book your slot.";
    } else if (message.includes("donate")) {
        botReply = "You can donate blood if you are healthy, aged 18 or above, weigh 50 kg or more, and carry a photo ID. Try the Eligibility Check on this page!";
    } else if (message.includes("blood group")) {
        botReply = "The main blood groups are A+, A-, B+, B-, AB+, AB-, O+, and O-.";
    } else if (message.includes("request")) {
        botReply = "You can raise an emergency blood request from the Request page in the LOGIN menu.";
    } else if (message.includes("contact")) {
        botReply = "You can contact Lifeline Blood Bank at +91 99001 63000 or emergency@lifeline.com.";
    } else if (message.includes("hello") || message.includes("hi")) {
        botReply = "Hello! How can I help you today?";
    }

    input.value = "";
    chatMessages.scrollTop = chatMessages.scrollHeight;
    setTimeout(function () {
        typingDiv.textContent = botReply;
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }, 700);
}

document.getElementById("userInput").addEventListener("keydown", function (e) {
    if (e.key === "Enter") { e.preventDefault(); sendMessage(); }
});
</script>

</body>
</html>
