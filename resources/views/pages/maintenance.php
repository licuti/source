<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống đang bảo trì</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg-color: #0a0a12;
            --primary: #3b82f6;
            --primary-light: #60a5fa;
            --accent: #8b5cf6;
            --cyan: #22d3ee;
            --primary-glow: rgba(59, 130, 246, 0.5);
            --accent-glow: rgba(139, 92, 246, 0.4);
            --text-main: #ffffff;
            --text-muted: #94a3b8;
            --glass-bg: rgba(255, 255, 255, 0.035);
            --glass-border: rgba(255, 255, 255, 0.09);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body { height: 100%; }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* ====== Background layers ====== */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: radial-gradient(ellipse 75% 65% at 50% 45%, black 30%, transparent 75%);
            -webkit-mask-image: radial-gradient(ellipse 75% 65% at 50% 45%, black 30%, transparent 75%);
            z-index: 0;
        }

        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
        }
        .orb-1 {
            width: 560px; height: 560px;
            background: radial-gradient(circle, var(--primary-glow) 0%, transparent 65%);
            top: -12%; left: -8%;
            animation: drift1 14s ease-in-out infinite alternate;
        }
        .orb-2 {
            width: 520px; height: 520px;
            background: radial-gradient(circle, var(--accent-glow) 0%, transparent 65%);
            bottom: -15%; right: -10%;
            animation: drift2 18s ease-in-out infinite alternate;
        }
        .orb-3 {
            width: 380px; height: 380px;
            background: radial-gradient(circle, rgba(34, 211, 238, 0.28) 0%, transparent 65%);
            top: 55%; left: 55%;
            animation: drift3 12s ease-in-out infinite alternate;
        }

        @keyframes drift1 { to { transform: translate(70px, 60px) scale(1.15); } }
        @keyframes drift2 { to { transform: translate(-60px, -70px) scale(1.1); } }
        @keyframes drift3 { to { transform: translate(-90px, 40px) scale(0.9); } }

        /* Particles */
        #particles { position: fixed; inset: 0; z-index: 1; pointer-events: none; }

        /* ====== Card ====== */
        .maintenance-card {
            position: relative;
            z-index: 10;
            max-width: 680px;
            width: 92%;
            background: var(--glass-bg);
            backdrop-filter: blur(24px) saturate(140%);
            -webkit-backdrop-filter: blur(24px) saturate(140%);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            padding: 52px 46px 46px;
            text-align: center;
            box-shadow:
                0 30px 60px -15px rgba(0, 0, 0, 0.6),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
            animation: slideUp 0.9s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(36px);
            overflow: hidden;
        }

        /* Animated border shimmer on top edge */
        .maintenance-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary-light), var(--accent), transparent);
            background-size: 200% 100%;
            animation: shimmer 4s linear infinite;
        }

        @keyframes shimmer { to { background-position: -200% 0; } }
        @keyframes slideUp { to { opacity: 1; transform: translateY(0); } }

        /* ====== Status pill ====== */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 8px 18px;
            border-radius: 999px;
            background: rgba(251, 191, 36, 0.08);
            border: 1px solid rgba(251, 191, 36, 0.25);
            color: #fbbf24;
            font-size: 12.5px;
            font-weight: 600;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            margin-bottom: 32px;
        }
        .status-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #fbbf24;
            box-shadow: 0 0 0 0 rgba(251, 191, 36, 0.6);
            animation: ping 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
        @keyframes ping {
            0% { box-shadow: 0 0 0 0 rgba(251, 191, 36, 0.6); }
            70% { box-shadow: 0 0 0 9px rgba(251, 191, 36, 0); }
            100% { box-shadow: 0 0 0 0 rgba(251, 191, 36, 0); }
        }

        /* ====== Icon ====== */
        .icon-container {
            width: 96px; height: 96px;
            margin: 0 auto 30px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%;
            background: linear-gradient(145deg, rgba(59, 130, 246, 0.12), rgba(139, 92, 246, 0.08));
            border: 1px solid rgba(59, 130, 246, 0.28);
            position: relative;
            box-shadow: 0 0 45px -8px var(--primary-glow);
        }
        .icon-container i {
            font-size: 38px;
            background: linear-gradient(135deg, var(--primary-light), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: wiggle 3.5s ease-in-out infinite;
        }
        @keyframes wiggle {
            0%, 100% { transform: rotate(0deg) scale(1); }
            15% { transform: rotate(-12deg) scale(1.06); }
            30% { transform: rotate(10deg) scale(1.06); }
            45% { transform: rotate(-6deg) scale(1.02); }
            60% { transform: rotate(0deg) scale(1); }
        }
        .icon-container::before, .icon-container::after {
            content: '';
            position: absolute;
            inset: -7px;
            border-radius: 50%;
            border: 2px solid transparent;
            animation: spin 3.2s linear infinite;
        }
        .icon-container::before {
            border-top-color: var(--primary);
            border-right-color: rgba(59, 130, 246, 0.35);
        }
        .icon-container::after {
            inset: -14px;
            border-bottom-color: var(--accent);
            border-left-color: rgba(139, 92, 246, 0.25);
            animation-duration: 5s;
            animation-direction: reverse;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* ====== Text ====== */
        h1 {
            font-size: 34px;
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: -0.6px;
            background: linear-gradient(135deg, #ffffff 0%, #bfd4f5 55%, #93a8ce 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .description {
            font-size: 16.5px;
            line-height: 1.75;
            color: var(--text-muted);
            margin-bottom: 34px;
            font-weight: 300;
            max-width: 480px;
            margin-left: auto; margin-right: auto;
        }
        .description a {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .description a:hover { text-shadow: 0 0 10px rgba(96, 165, 250, 0.6); }

        /* ====== Progress bar ====== */
        .progress-wrap {
            max-width: 400px;
            margin: 0 auto 36px;
        }
        .progress-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 10px;
        }
        .progress-meta span:last-child { color: var(--primary-light); }
        .progress-track {
            height: 8px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.05);
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            width: 0%;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--primary), var(--accent), var(--cyan), var(--primary));
            background-size: 300% 100%;
            animation: flow 3s linear infinite;
            transition: width 1.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 0 14px var(--primary-glow);
        }
        @keyframes flow { to { background-position: -300% 0; } }

        /* ====== Countdown ====== */
        .eta-wrapper {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.06);
            padding: 22px 38px;
            border-radius: 20px;
            box-shadow: inset 0 2px 12px rgba(0,0,0,0.25);
            transition: transform 0.35s ease, border-color 0.35s ease, box-shadow 0.35s ease;
            margin-bottom: 36px;
        }
        .eta-wrapper:hover {
            transform: translateY(-4px);
            border-color: rgba(59, 130, 246, 0.35);
            box-shadow: inset 0 2px 12px rgba(0,0,0,0.25), 0 15px 35px -12px var(--primary-glow);
        }
        .eta-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: var(--text-muted);
            margin-bottom: 14px;
            font-weight: 600;
        }
        .eta-time { display: flex; gap: 14px; align-items: flex-start; }

        .countdown-item {
            display: flex; flex-direction: column; align-items: center;
            min-width: 66px;
        }
        .countdown-value {
            position: relative;
            font-size: 30px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
            font-variant-numeric: tabular-nums;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            height: 52px;
            width: 66px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden; /* Khung cố định, giấu số cuộn ra ngoài */
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.05);
        }
        .countdown-num {
            display: inline-block;
            line-height: 1;
            text-shadow: 0 0 18px rgba(147, 197, 253, 0.45);
            will-change: transform, opacity;
        }
        .countdown-num.roll-up {
            animation: clockRollUp 0.38s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes clockRollUp {
            0% {
                transform: translateY(80%);
                opacity: 0.15;
                filter: blur(2px);
            }
            100% {
                transform: translateY(0);
                opacity: 1;
                filter: blur(0);
            }
        }
        .countdown-label {
            font-size: 10.5px;
            text-transform: uppercase;
            color: var(--primary-light);
            margin-top: 8px;
            font-weight: 600;
            letter-spacing: 2px;
        }
        .countdown-separator {
            font-size: 26px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.25);
            margin-top: 10px;
            animation: blink 1s step-end infinite;
        }
        @keyframes blink { 50% { opacity: 0.15; } }

        /* ====== Footer / socials ====== */
        .card-footer {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
            padding-top: 28px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }
        .socials { display: flex; gap: 14px; }
        .socials a {
            width: 44px; height: 44px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: var(--text-muted);
            font-size: 17px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .socials a:hover {
            color: #fff;
            border-color: rgba(59, 130, 246, 0.5);
            background: rgba(59, 130, 246, 0.14);
            transform: translateY(-4px);
            box-shadow: 0 10px 22px -8px var(--primary-glow);
        }
        .footer-note {
            font-size: 13px;
            color: rgba(148, 163, 184, 0.65);
            font-weight: 300;
        }
        .footer-note i { color: var(--primary-light); margin-right: 5px; }

        /* Responsive */
        @media (max-width: 620px) {
            .maintenance-card { padding: 40px 22px 36px; }
            h1 { font-size: 26px; }
            .description { font-size: 15px; }
            .eta-wrapper { padding: 18px 18px; width: 100%; }
            .eta-time { gap: 8px; }
            .countdown-item { min-width: 54px; }
            .countdown-value { font-size: 23px; height: 44px; width: 54px; }
            .countdown-separator { font-size: 20px; }
            .icon-container { width: 84px; height: 84px; }
            .icon-container i { font-size: 32px; }
        }
    </style>
</head>
<body>

    <!-- Background layers -->
    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <canvas id="particles"></canvas>

    <div class="maintenance-card">

        <div class="status-pill">
            <span class="status-dot"></span>
            Đang bảo trì hệ thống
        </div>

        <div class="icon-container">
            <i class="fa-solid fa-screwdriver-wrench"></i>
        </div>

        <h1>Hệ thống đang bảo trì</h1>

        <div class="description">
            <p>Chúng tôi đang tiến hành nâng cấp hệ thống để mang lại trải nghiệm tốt hơn.
            Vui lòng quay lại sau ít phút. Cảm ơn bạn đã kiên nhẫn chờ đợi! 💙</p>
        </div>

        <!-- Progress bar -->
        <div class="progress-wrap">
            <div class="progress-meta">
                <span>Tiến độ nâng cấp</span>
                <span id="progress-percent">0%</span>
            </div>
            <div class="progress-track">
                <div class="progress-fill" id="progress-fill"></div>
            </div>
        </div>

        <!-- Countdown -->
        <div class="eta-wrapper">
            <span class="eta-label">Dự kiến hoàn thành trong</span>
            <div class="eta-time" id="countdown-timer">
                <div class="countdown-item">
                    <div class="countdown-value"><span class="countdown-num" id="cd-days">00</span></div>
                    <span class="countdown-label">Ngày</span>
                </div>
                <span class="countdown-separator">:</span>
                <div class="countdown-item">
                    <div class="countdown-value"><span class="countdown-num" id="cd-hours">00</span></div>
                    <span class="countdown-label">Giờ</span>
                </div>
                <span class="countdown-separator">:</span>
                <div class="countdown-item">
                    <div class="countdown-value"><span class="countdown-num" id="cd-minutes">00</span></div>
                    <span class="countdown-label">Phút</span>
                </div>
                <span class="countdown-separator">:</span>
                <div class="countdown-item">
                    <div class="countdown-value"><span class="countdown-num" id="cd-seconds">00</span></div>
                    <span class="countdown-label">Giây</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="card-footer">
            <div class="socials">
                <a href="#" title="Facebook" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" title="Telegram" aria-label="Telegram"><i class="fa-brands fa-telegram"></i></a>
                <a href="#" title="Discord" aria-label="Discord"><i class="fa-brands fa-discord"></i></a>
                <a href="mailto:support@example.com" title="Email" aria-label="Email"><i class="fa-solid fa-envelope"></i></a>
            </div>
            <p class="footer-note"><i class="fa-solid fa-shield-halved"></i>Dữ liệu của bạn vẫn được bảo vệ an toàn trong thời gian bảo trì</p>
        </div>

    </div>

    <script>
        /* ============ CONFIG ============ */
        // Thời gian dự kiến hoàn thành bảo trì (đổi giá trị này theo nhu cầu)
        // Ví dụ: '2026-01-01T08:00:00+07:00' — mặc định demo: +6 giờ kể từ lúc mở trang
        const ETA_TARGET = new Date(Date.now() + 6 * 60 * 60 * 1000);
        // Tổng thời lượng bảo trì (để tính % tiến độ) — mặc định 8 giờ
        const TOTAL_DURATION_MS = 8 * 60 * 60 * 1000;

        /* ============ COUNTDOWN ============ */
        const targetTime = ETA_TARGET.getTime();
        const startTime = targetTime - TOTAL_DURATION_MS;
        const els = {
            days: document.getElementById('cd-days'),
            hours: document.getElementById('cd-hours'),
            minutes: document.getElementById('cd-minutes'),
            seconds: document.getElementById('cd-seconds')
        };

        function setValue(el, val) {
            const str = String(val).padStart(2, '0');
            if (el.innerText !== str) {
                el.innerText = str;
                el.classList.remove('roll-up');
                void el.offsetWidth; // restart animation
                el.classList.add('roll-up');
            }
        }

        function updateCountdown() {
            const now = Date.now();
            const distance = targetTime - now;

            if (distance < 0) {
                document.getElementById('countdown-timer').innerHTML =
                    '<span style="font-size:19px;font-weight:500;color:#fff;padding:6px 4px;">✨ Đang hoàn tất những bước cuối... Xin chờ trong giây lát.</span>';
                document.getElementById('progress-fill').style.width = '100%';
                document.getElementById('progress-percent').innerText = '100%';
                clearInterval(timerId);
                return;
            }

            setValue(els.days,    Math.floor(distance / 86400000));
            setValue(els.hours,   Math.floor((distance % 86400000) / 3600000));
            setValue(els.minutes, Math.floor((distance % 3600000) / 60000));
            setValue(els.seconds, Math.floor((distance % 60000) / 1000));

            // Progress
            const pct = Math.min(99, Math.max(2, ((now - startTime) / TOTAL_DURATION_MS) * 100));
            document.getElementById('progress-fill').style.width = pct.toFixed(1) + '%';
            document.getElementById('progress-percent').innerText = Math.round(pct) + '%';
        }

        updateCountdown();
        const timerId = setInterval(updateCountdown, 1000);

        /* ============ PARTICLES ============ */
        const canvas = document.getElementById('particles');
        const ctx = canvas.getContext('2d');
        let W, H, particles = [];

        function resize() {
            W = canvas.width = window.innerWidth;
            H = canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resize);
        resize();

        const COLORS = ['rgba(96,165,250,', 'rgba(139,92,246,', 'rgba(34,211,238,', 'rgba(255,255,255,'];
        const COUNT = Math.min(70, Math.floor(W * H / 22000));

        for (let i = 0; i < COUNT; i++) {
            particles.push({
                x: Math.random() * W,
                y: Math.random() * H,
                r: Math.random() * 2 + 0.6,
                vx: (Math.random() - 0.5) * 0.35,
                vy: (Math.random() - 0.5) * 0.35 - 0.1,
                a: Math.random() * 0.5 + 0.15,
                c: COLORS[Math.floor(Math.random() * COLORS.length)],
                tw: Math.random() * Math.PI * 2,
                tws: Math.random() * 0.02 + 0.005
            });
        }

        function animate() {
            ctx.clearRect(0, 0, W, H);
            for (const p of particles) {
                p.x += p.vx;
                p.y += p.vy;
                p.tw += p.tws;
                if (p.x < -10) p.x = W + 10;
                if (p.x > W + 10) p.x = -10;
                if (p.y < -10) p.y = H + 10;
                if (p.y > H + 10) p.y = -10;

                const alpha = p.a * (0.55 + 0.45 * Math.sin(p.tw));
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = p.c + alpha.toFixed(3) + ')';
                ctx.fill();
            }
            requestAnimationFrame(animate);
        }
        animate();

        /* ============ CARD TILT (subtle) ============ */
        const card = document.querySelector('.maintenance-card');
        if (window.matchMedia('(pointer: fine)').matches) {
            document.addEventListener('mousemove', (e) => {
                const rx = ((e.clientY / window.innerHeight) - 0.5) * -4;
                const ry = ((e.clientX / window.innerWidth) - 0.5) * 4;
                card.style.transform = `perspective(1000px) rotateX(${rx}deg) rotateY(${ry}deg)`;
            });
            document.addEventListener('mouseleave', () => {
                card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg)';
            });
        }
    </script>
</body>
</html>