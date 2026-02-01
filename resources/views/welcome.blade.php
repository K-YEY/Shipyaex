<!DOCTYPE html>
<html lang="ar" dir="rtl" id="html-tag">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Shipya | شحن بذكاء</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #DC2626;
            --primary-dark: #991B1B;
            --secondary: #0F172A;
            --accent: #3B82F6;
            --bg-light: #F8FAFC;
            --card-bg: rgba(255, 255, 255, 0.85);
            --text-main: #1E293B;
            --text-muted: #64748B;
            --glass: rgba(255, 255, 255, 0.7);
            --border: rgba(226, 232, 240, 0.8);
            --radius: 20px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        [lang="en"] { font-family: 'Outfit', sans-serif; }
        [lang="ar"] { font-family: 'Cairo', sans-serif; }

        body {
            background-color: var(--bg-light);
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* Modern Background */
        .ambient-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            background: radial-gradient(circle at 0% 0%, #fff5f5 0%, transparent 50%),
                        radial-gradient(circle at 100% 100%, #f0f7ff 0%, transparent 50%);
        }

        .blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: var(--primary);
            filter: blur(120px);
            opacity: 0.05;
            border-radius: 50%;
            animation: move 25s infinite alternate;
        }

        @keyframes move {
            from { transform: translate(-10%, -10%); }
            to { transform: translate(20%, 20%); }
        }

        /* Navbar */
        nav {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 1.2rem 5%;
            background: var(--glass);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }

        .logo-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .logo-text {
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--primary);
            letter-spacing: -1px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .lang-btn {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .login-btn {
            background: var(--primary);
            color: white;
            padding: 12px 28px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 10px 20px rgba(220, 38, 38, 0.2);
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(220, 38, 38, 0.3);
        }

        /* Hero */
        .hero {
            padding: 160px 5% 80px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 4rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .hero-content h1 {
            font-size: 4rem;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            color: var(--secondary);
            font-weight: 900;
        }

        .hero-content p {
            font-size: 1.25rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            max-width: 600px;
        }

        .hero-image {
            position: relative;
        }

        .hero-image img {
            width: 100%;
            border-radius: 30px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.1);
        }

        /* Pricing Section */
        .pricing {
            padding: 100px 5%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title h2 {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--secondary);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2.5rem;
        }

        .plan-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 30px;
            padding: 3rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .plan-card.featured {
            border-color: var(--primary);
            box-shadow: 0 30px 60px rgba(220, 38, 38, 0.1);
            transform: scale(1.03);
            z-index: 10;
        }

        .plan-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: var(--primary);
            color: white;
            padding: 6px 15px;
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 800;
        }

        .plan-name {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--secondary);
        }

        .plan-limit {
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .price-groups {
            flex-grow: 1;
        }

        .price-group {
            background: white;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.2rem;
        }

        .price-value {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .price-value span { font-size: 1rem; color: var(--text-muted); font-weight: 600; }

        .price-locations {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .cta-pricing {
            margin-top: 2rem;
            display: block;
            text-align: center;
            background: var(--secondary);
            color: white;
            padding: 18px;
            border-radius: 18px;
            text-decoration: none;
            font-weight: 800;
            font-size: 1.1rem;
        }

        .featured .cta-pricing {
            background: var(--primary);
        }

        /* Footer */
        footer {
            background: var(--secondary);
            color: white;
            padding: 80px 5% 40px;
            margin-top: 100px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 4rem;
            max-width: 1400px;
            margin: 0 auto 60px;
        }

        .footer-logo {
            font-size: 2rem;
            font-weight: 900;
            color: white;
            margin-bottom: 1.5rem;
        }

        .footer-desc {
            color: #94A3B8;
            line-height: 1.8;
        }

        .footer-links h4 {
            margin-bottom: 2rem;
            font-size: 1.2rem;
        }

        .footer-links ul { list-style: none; }
        .footer-links ul li { margin-bottom: 12px; }
        .footer-links ul li a { color: #94A3B8; text-decoration: none; }
        .footer-links ul li a:hover { color: var(--primary); }

        .copyright {
            text-align: center;
            padding-top: 40px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #64748B;
        }

        /* Mobile */
        @media (max-width: 1024px) {
            .hero { grid-template-columns: 1fr; text-align: center; padding-top: 120px; }
            .hero-content h1 { font-size: 3rem; }
            .hero-content p { margin: 0 auto 2.5rem; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 768px) {
            .footer-grid { grid-template-columns: 1fr; }
            .section-title h2 { font-size: 2.2rem; }
            .plan-card { padding: 2rem; }
        }

        /* Animation */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease-out;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body class="dir-rtl" dir="rtl">
    <div class="ambient-bg">
        <div class="blob"></div>
    </div>

    <nav>
        <a href="{{ url('/') }}" class="logo-meta">
            <span class="logo-text">ShipManager</span>
        </a>
        <div class="nav-actions">
            <button class="lang-btn" onclick="toggleLang()">
                <span id="lang-label">English</span>
                <span>🌐</span>
            </button>
            <a href="{{ url('/admin') }}" class="login-btn" id="login-text">دخول المنصة</a>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-content">
            <h1 data-en="Next-Gen Shipping Solution" data-ar="مستقبل الشحن في مصر">مستقبل الشحن في مصر</h1>
            <p data-en="Fast, safe, and intelligent fulfillment for your business delivery needs across Egypt." 
               data-ar="أسرع وأذكى منظومة شحن متكاملة لخدمة تجارتك في كل محافظات مصر بأمان تام وتكنولوجيا متطورة.">
               أسرع وأذكى منظومة شحن متكاملة لخدمة تجارتك في كل محافظات مصر بأمان تام وتكنولوجيا متطورة.
            </p>
            <div style="display: flex; gap: 1rem;">
                <a href="{{ url('/admin') }}" class="login-btn" style="padding: 16px 40px;" data-en="Start Now" data-ar="ابدأ معانا">ابدأ معانا</a>
                <a href="#pricing" class="login-btn" style="background: white; color: var(--secondary); border: 2px solid var(--border);" data-en="View Prices" data-ar="شوف أسعارنا">شوف أسعارنا</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&q=80&w=1000" alt="Shipping Future">
        </div>
    </header>

    <section class="pricing" id="pricing">
        <div class="section-title">
            <h2 data-en="Our Flexible Plans" data-ar="خطط وباقات الشحن">خطط وباقات الشحن</h2>
            <p data-en="Transparent pricing tailored to your business scale" data-ar="أسعار واضحة ومناسبة لحجم شغلك، مهما كان عدد أوردراتك">
                أسعار واضحة ومناسبة لحجم شغلك، مهما كان عدد أوردراتك
            </p>
        </div>

        <div class="pricing-grid">
            @foreach($plans as $plan)
            <div class="plan-card {{ $loop->index == 1 ? 'featured' : '' }} reveal">
                @if($loop->index == 1)
                <div class="plan-badge" data-en="Best Seller" data-ar="الأكثر طلباً">الأكثر طلباً</div>
                @endif
                <h3 class="plan-name">{{ $plan['name'] }}</h3>
                <div class="plan-limit">
                    <span>📦</span>
                    <span>{{ $plan['order_count'] }}</span>
                    <span data-en="Orders / Month" data-ar="أوردر / شهر">أوردر / شهر</span>
                </div>

                <div class="price-groups">
                    @foreach($plan['groups'] as $group)
                    <div class="price-group">
                        <div class="price-value">{{ $group['price'] }} <span>ج.م</span></div>
                        <div class="price-locations">
                            <strong>{{ $group['governorates'] }}</strong>
                        </div>
                    </div>
                    @endforeach
                </div>

                <a href="{{ url('/admin') }}" class="cta-pricing" data-en="Choose Plan" data-ar="اختيار الباقة">اختيار الباقة</a>
            </div>
            @endforeach
        </div>
    </section>

    <footer>
        <div class="footer-grid">
            <div class="footer-info">
                <div class="footer-logo">ShipManager</div>
                <p class="footer-desc" data-en="Leading the logistics revolution in Egypt with precision and care." data-ar="نقود ثورة الخدمات اللوجستية في مصر باحترافية وعناية فائقة بكل طرد.">
                    نقود ثورة الخدمات اللوجستية في مصر باحترافية وعناية فائقة بكل طرد.
                </p>
            </div>
            <div class="footer-links">
                <h4 data-en="Company" data-ar="الشركة">الشركة</h4>
                <ul>
                    <li><a href="#" data-en="About Us" data-ar="عننا">عننا</a></li>
                    <li><a href="#" data-en="Careers" data-ar="وظائف">وظائف</a></li>
                    <li><a href="/guide.html" data-en="User Guide" data-ar="دليل المستخدم">دليل المستخدم</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4 data-en="Support" data-ar="الدعم">الدعم</h4>
                <ul>
                    <li><a href="#" data-en="Help Center" data-ar="مركز المساعدة">مركز المساعدة</a></li>
                    <li><a href="#" data-en="Contact" data-ar="اتصل بنا">اتصل بنا</a></li>
                    <li><a href="#" data-en="FAQ" data-ar="الأسئلة المتكررة">الأسئلة المتكررة</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4 data-en="Connect" data-ar="تواصل">تواصل</h4>
                <p style="color: #94A3B8; margin-bottom: 1rem;">info@shipmanager.com</p>
                <p style="color: #94A3B8; font-size: 1.5rem; font-weight: 800;">19999</p>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2026 ShipManager. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function toggleLang() {
            const html = document.getElementById('html-tag');
            const langLabel = document.getElementById('lang-label');
            const isAr = html.getAttribute('dir') === 'rtl';

            if (isAr) {
                html.setAttribute('dir', 'ltr');
                html.setAttribute('lang', 'en');
                langLabel.innerText = 'العربية';
                translate('en');
            } else {
                html.setAttribute('dir', 'rtl');
                html.setAttribute('lang', 'ar');
                langLabel.innerText = 'English';
                translate('ar');
            }
        }

        function translate(lang) {
            document.querySelectorAll('[data-' + lang + ']').forEach(el => {
                el.innerText = el.getAttribute('data-' + lang);
            });
        }

        // Reveal Animation
        function reveal() {
            var reveals = document.querySelectorAll(".reveal");
            for (var i = 0; i < reveals.length; i++) {
                var windowHeight = window.innerHeight;
                var elementTop = reveals[i].getBoundingClientRect().top;
                var elementVisible = 150;
                if (elementTop < windowHeight - elementVisible) {
                    reveals[i].classList.add("active");
                }
            }
        }

        window.addEventListener("scroll", reveal);
        reveal(); // Init
    </script>
</body>
</html>