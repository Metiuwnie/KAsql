<!DOCTYPE html>
<html lang="id">
<head>
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — KAsql Enterprise Financial Suite</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=5.1">
    <style>
        /* ====== MODERN MINIMALIST KAsql AUTH STYLES ====== */
        :root {
            --kasql-primary: #ff5e1a;
            --kasql-primary-hover: #e04d10;
            --kasql-primary-rgb: 255, 94, 26;
            --kasql-font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            
            /* Light Mode Default */
            --bg-page: #f8fafc;
            --bg-card: #ffffff;
            --bg-hero-side: #fafaf9;
            --bg-surface: #f4f4f5;
            --bg-surface-hover: #ebebeb;
            --text-main: #09090b;
            --text-heading: #18181b;
            --text-muted: #71717a;
            --border-color: rgba(0, 0, 0, 0.08);
            --border-focus: #ff5e1a;
            --card-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.06), 0 0 0 1px rgba(0, 0, 0, 0.05);
            --badge-bg: #18181b;
            --badge-text: #ffffff;
            --logo-badge-bg: #0f172a;
            --logo-badge-color: #ffffff;
            --logo-badge-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }

        [data-theme="dark"] {
            --bg-page: #080809;
            --bg-card: #111114;
            --bg-hero-side: #0c0c0e;
            --bg-surface: #17181c;
            --bg-surface-hover: #212227;
            --text-main: #f4f4f6;
            --text-heading: #ffffff;
            --text-muted: #8e8e93;
            --border-color: rgba(255, 255, 255, 0.08);
            --border-focus: #ff5e1a;
            --card-shadow: 0 30px 70px -15px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.08);
            --badge-bg: rgba(255, 94, 26, 0.15);
            --badge-text: #ff5e1a;
            --logo-badge-bg: #ff5e1a;
            --logo-badge-color: #ffffff;
            --logo-badge-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--kasql-font);
            background-color: var(--bg-page);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            padding: 1.5rem;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Clean Architectural Dot Matrix & Animated Tech Mesh */
        .bg-dots-container {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        /* Subtle Geometric Dot Grid */
        .bg-dot-pattern {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: radial-gradient(circle, rgba(15, 23, 42, 0.12) 1.5px, transparent 1.5px);
            background-size: 32px 32px;
            mask-image: radial-gradient(ellipse 90% 90% at 50% 50%, black 50%, transparent 95%);
            -webkit-mask-image: radial-gradient(ellipse 90% 90% at 50% 50%, black 50%, transparent 95%);
            opacity: 0.8;
            transition: background-image 0.3s ease;
        }

        [data-theme="dark"] .bg-dot-pattern {
            background-image: radial-gradient(circle, rgba(255, 255, 255, 0.12) 1.5px, transparent 1.5px);
        }

        /* Tech Mesh Dynamic Canvas */
        .tech-canvas {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        /* Floating Precision Accents & Orange Animated Nodes */
        .accent-node {
            position: absolute;
            pointer-events: none;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: center;
            will-change: transform;
            z-index: 3;
        }

        /* Orange Accent Nodes with Smooth Breathing Motion & Glow */
        .node-dot-orange {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ff5e1a;
            box-shadow: 0 0 12px rgba(255, 94, 26, 0.8), 0 0 24px rgba(255, 94, 26, 0.4);
            animation: pulseOrange 2.4s ease-in-out infinite alternate;
        }

        @keyframes pulseOrange {
            0% { transform: scale(0.75); opacity: 0.6; box-shadow: 0 0 6px rgba(255, 94, 26, 0.4); }
            100% { transform: scale(1.6); opacity: 1; box-shadow: 0 0 18px rgba(255, 94, 26, 0.95), 0 0 32px rgba(255, 94, 26, 0.5); }
        }

        /* Dynamic Floating Drift Animations */
        .motion-drift-1 { animation: driftMotion1 4.2s ease-in-out infinite alternate; }
        .motion-drift-2 { animation: driftMotion2 5.0s ease-in-out infinite alternate; }
        .motion-drift-3 { animation: driftMotion3 3.8s ease-in-out infinite alternate; }
        .motion-drift-4 { animation: driftMotion4 5.5s ease-in-out infinite alternate; }

        @keyframes driftMotion1 {
            0% { transform: translate(0px, 0px) rotate(0deg); }
            50% { transform: translate(12px, -18px) rotate(20deg); }
            100% { transform: translate(-8px, -34px) rotate(45deg); }
        }

        @keyframes driftMotion2 {
            0% { transform: translate(0px, 0px) rotate(0deg); }
            50% { transform: translate(-14px, 20px) rotate(-25deg); }
            100% { transform: translate(10px, 36px) rotate(-55deg); }
        }

        @keyframes driftMotion3 {
            0% { transform: translate(0px, 0px) rotate(0deg); }
            50% { transform: translate(16px, -15px) rotate(30deg); }
            100% { transform: translate(-10px, -36px) rotate(65deg); }
        }

        @keyframes driftMotion4 {
            0% { transform: translate(0px, 0px) rotate(0deg); }
            50% { transform: translate(-12px, 18px) rotate(-20deg); }
            100% { transform: translate(14px, 32px) rotate(-45deg); }
        }

        /* Precision Crosshairs '+' */
        .crosshair-marker {
            color: rgba(15, 23, 42, 0.35);
            transition: color 0.3s ease;
        }

        [data-theme="dark"] .crosshair-marker {
            color: rgba(255, 255, 255, 0.35);
        }

        .crosshair-marker.orange {
            color: #ff5e1a;
        }

        /* Strategic Positions framing the card */
        .pos-tl-a { top: 16%; left: 8%; }
        .pos-tl-b { top: 28%; left: 15%; }
        .pos-tl-c { top: 42%; left: 6%; }
        
        .pos-bl-a { bottom: 26%; left: 10%; }
        .pos-bl-b { bottom: 14%; left: 14%; }

        .pos-tr-a { top: 18%; right: 10%; }
        .pos-tr-b { top: 35%; right: 7%; }

        .pos-br-a { bottom: 32%; right: 14%; }
        .pos-br-b { bottom: 15%; right: 9%; }
        .pos-br-c { bottom: 22%; right: 6%; }

        @media (max-width: 900px) {
            .accent-node {
                display: none;
            }
        }

        /* Top Bar Floating Controls */
        .top-floating-bar {
            position: fixed;
            top: 1.5rem;
            right: 2rem;
            z-index: 30;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .theme-toggle-btn {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 9999px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-muted);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .theme-toggle-btn:hover {
            transform: translateY(-1.5px);
            color: var(--text-heading);
            border-color: rgba(255, 94, 26, 0.4);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
        }

        /* Master Auth Card Wrapper */
        .login-card-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1040px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            overflow: hidden;
            animation: cardFadeIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }



        @keyframes cardFadeIn {
            from { opacity: 0; transform: translateY(20px) scale(0.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ===== LEFT HERO SHOWCASE PANEL ===== */
        .login-hero-panel {
            padding: 3.25rem 2.75rem;
            background: var(--bg-hero-side);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .hero-top {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .hero-title {
            font-size: 2.05rem;
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -0.03em;
            color: var(--text-heading);
        }

        .hero-title .highlight {
            color: var(--kasql-primary);
        }

        .hero-desc {
            font-size: 0.92rem;
            line-height: 1.6;
            color: var(--text-muted);
        }

        /* Minimalist Value Prop Features */
        .hero-features {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            margin: 2rem 0;
        }

        .feature-card {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            padding: 0.9rem 1.05rem;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .feature-card:hover {
            border-color: rgba(255, 94, 26, 0.3);
            background: var(--bg-surface-hover);
            transform: translateY(-1px);
        }

        .feature-icon-box {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--kasql-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .feature-meta {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .feature-name {
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--text-heading);
            letter-spacing: -0.01em;
        }

        .feature-detail {
            font-size: 0.77rem;
            color: var(--text-muted);
            line-height: 1.45;
        }

        /* Hero Footer System Status */
        .hero-footer-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.76rem;
            color: var(--text-muted);
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 6px rgba(16, 185, 129, 0.5);
        }

        /* ===== RIGHT AUTH FORM PANEL ===== */
        .login-form-panel {
            padding: 3.25rem 2.75rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--bg-card);
        }

        /* Brand Identity Header */
        .brand-header {
            margin-bottom: 2rem;
        }

        .brand-row {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 1.5rem;
        }

        .brand-logo-badge {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: var(--logo-badge-bg);
            color: var(--logo-badge-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.15rem;
            flex-shrink: 0;
            box-shadow: var(--logo-badge-shadow);
            border: 1px solid rgba(255, 255, 255, 0.1);
            letter-spacing: -0.02em;
        }

        .brand-meta {
            display: flex;
            flex-direction: column;
        }

        .brand-name {
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--text-heading);
            line-height: 1.1;
        }

        .brand-dot {
            color: var(--kasql-primary);
        }

        .brand-sub {
            font-size: 0.74rem;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.01em;
            margin-top: 2px;
        }

        .form-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.02em;
            margin-bottom: 0.35rem;
        }

        .form-desc {
            font-size: 0.88rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* Error Alert Banner */
        .login-error-banner {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.25);
            border-radius: 10px;
            padding: 0.75rem 0.95rem;
            color: #ef4444;
            font-size: 0.84rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 1.25rem;
            animation: shakeAlert 0.35s ease;
        }

        [data-theme="dark"] .login-error-banner {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.35);
            color: #f87171;
        }

        @keyframes shakeAlert {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-5px); }
            40% { transform: translateX(5px); }
            60% { transform: translateX(-3px); }
            80% { transform: translateX(3px); }
        }

        /* Form Fields */
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .field-label {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-heading);
            letter-spacing: -0.01em;
        }

        .field-input-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .field-input-box svg.input-lead-icon {
            position: absolute;
            left: 14px;
            color: var(--text-muted);
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .field-input-box input {
            width: 100%;
            padding: 0.8rem 2.75rem 0.8rem 2.75rem;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: inherit;
            font-weight: 500;
            color: var(--text-main);
            transition: all 0.2s ease;
            outline: none;
        }

        [data-theme="light"] .field-input-box input {
            background: #ffffff;
            border-color: rgba(0, 0, 0, 0.12);
        }

        .field-input-box input:focus {
            border-color: var(--kasql-primary);
            background: var(--bg-card);
            box-shadow: 0 0 0 3px rgba(255, 94, 26, 0.15);
        }

        .field-input-box input:focus ~ svg.input-lead-icon {
            color: var(--kasql-primary);
        }

        .toggle-pw-btn {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: var(--text-muted);
            padding: 6px;
            cursor: pointer;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .toggle-pw-btn:hover {
            color: var(--kasql-primary);
            background: rgba(255, 94, 26, 0.08);
        }

        /* Submit Button */
        .btn-submit-login {
            margin-top: 0.35rem;
            width: 100%;
            padding: 0.85rem 1.25rem;
            background: var(--kasql-primary);
            border: none;
            border-radius: 10px;
            color: #ffffff;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            cursor: pointer;
            box-shadow: none;
            transition: background-color 0.2s ease, transform 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit-login:hover {
            background: var(--kasql-primary-hover);
            transform: translateY(-1px);
            box-shadow: none;
        }

        .btn-submit-login:active {
            transform: translateY(0);
            box-shadow: none;
        }

        .btn-submit-login svg {
            transition: transform 0.2s ease;
        }

        .btn-submit-login:hover svg {
            transform: translateX(3px);
        }

        /* Auth Footer */
        .auth-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.76rem;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-weight: 500;
        }

        /* Responsive Breakpoints */
        @media (max-width: 900px) {
            .login-card-wrapper {
                grid-template-columns: 1fr;
                max-width: 440px;
                border-radius: 18px;
            }

            .login-hero-panel {
                display: none;
            }

            .login-form-panel {
                padding: 2.25rem 1.75rem;
            }

            .top-floating-bar {
                top: 1rem;
                right: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <!-- Clean Architectural Dot Matrix & Interactive Tech Mesh -->
    <div class="bg-dots-container" aria-hidden="true">
        <!-- Subtle Non-Intrusive Dot Matrix Pattern -->
        <div class="bg-dot-pattern"></div>

        <!-- Dynamic High-Tech Cyber Mesh Canvas -->
        <canvas id="techCanvas" class="tech-canvas"></canvas>


        <!-- Animated Floating Orange Nodes & Crosshairs '+' -->
        <!-- Top Left Quadrant -->
        <div class="accent-node pos-tl-a motion-drift-1">
            <div class="node-dot-orange"></div>
        </div>
        <div class="accent-node pos-tl-b motion-drift-2">
            <div class="crosshair-marker orange">
                <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M7.5 1.5V13.5M1.5 7.5H13.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </div>
        </div>
        <div class="accent-node pos-tl-c motion-drift-3">
            <div class="crosshair-marker">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M6.5 1.5V11.5M1.5 6.5H11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
        </div>

        <!-- Bottom Left Quadrant -->
        <div class="accent-node pos-bl-a motion-drift-4">
            <div class="node-dot-orange"></div>
        </div>
        <div class="accent-node pos-bl-b motion-drift-1">
            <div class="crosshair-marker">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 1.5V12.5M1.5 7H12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
        </div>

        <!-- Top Right Quadrant -->
        <div class="accent-node pos-tr-a motion-drift-2">
            <div class="crosshair-marker orange">
                <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M7.5 1.5V13.5M1.5 7.5H13.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </div>
        </div>
        <div class="accent-node pos-tr-b motion-drift-3">
            <div class="node-dot-orange"></div>
        </div>

        <!-- Bottom Right Quadrant -->
        <div class="accent-node pos-br-a motion-drift-1">
            <div class="crosshair-marker orange">
                <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M7.5 1.5V13.5M1.5 7.5H13.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </div>
        </div>
        <div class="accent-node pos-br-b motion-drift-4">
            <div class="node-dot-orange"></div>
        </div>
        <div class="accent-node pos-br-c motion-drift-2">
            <div class="crosshair-marker">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M6.5 1.5V11.5M1.5 6.5H11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
        </div>
    </div>

    <!-- Top Floating Theme Toggle -->
    <div class="top-floating-bar">
        <button class="theme-toggle-btn" id="loginThemeToggle" title="Ganti Mode Gelap/Terang" aria-label="Toggle Theme">
            <svg class="theme-icon-dark" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
            <svg class="theme-icon-light" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                <circle cx="12" cy="12" r="5"></circle>
                <line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                <line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
        </button>
    </div>

    <!-- Master Card Wrapper -->
    <div class="login-card-wrapper">
        <!-- ===== LEFT HERO SHOWCASE PANEL ===== -->
        <div class="login-hero-panel">
            <div class="hero-top">
                <h1 class="hero-title">
                    Presisi Pembukuan & <span class="highlight">Integritas Neraca</span> Terpadu.
                </h1>
                <p class="hero-desc">
                    Platform akuntansi terintegrasi dengan validasi neraca berimbang, proteksi tutup periode, depresiasi aset, dan analisis audit real-time.
                </p>
            </div>


            <!-- Minimalist Capability Cards -->
            <div class="hero-features">
                <div class="feature-card">
                    <div class="feature-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                            <polyline points="17 6 23 6 23 12"></polyline>
                        </svg>
                    </div>
                    <div class="feature-meta">
                        <span class="feature-name">Validasi Neraca Real-time</span>
                        <span class="feature-detail">Keseimbangan debit & kredit terverifikasi secara presisi sebelum posting.</span>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <div class="feature-meta">
                        <span class="feature-name">Proteksi Tutup Periode</span>
                        <span class="feature-detail">Penguncian buku lampau mencegah manipulasi dan menjaga integritas audit.</span>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                    </div>
                    <div class="feature-meta">
                        <span class="feature-name">Analisis & Audit Keuangan</span>
                        <span class="feature-detail">Pemantauan rasio likuiditas, solvabilitas, dan tren laba rugi otomatis.</span>
                    </div>
                </div>
            </div>

            <!-- Footer Status -->
            <div class="hero-footer-status">
                <div class="status-indicator">
                    <span class="status-dot"></span>
                    <span>Sistem Akuntansi Aktif</span>
                </div>
                <span>Versi 5.1 Enterprise</span>
            </div>
        </div>

        <!-- ===== RIGHT AUTH FORM PANEL ===== -->
        <div class="login-form-panel">
            <div class="brand-header">
                <div class="brand-row">
                    <div class="brand-logo-badge">KA</div>
                    <div class="brand-meta">
                        <div class="brand-name">KAsql<span class="brand-dot">.</span></div>
                        <div class="brand-sub">Sistem Akuntansi Terpadu</div>
                    </div>
                </div>
                <h2 class="form-title">Selamat Datang</h2>
                <p class="form-desc">Masukkan akun terdaftar Anda untuk mengakses sistem.</p>
            </div>

            <!-- Error Alert Banner -->
            <?php if (!empty($error)): ?>
            <div class="login-error-banner">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="<?= BASE_URL ?>/auth/login" class="auth-form" id="loginForm">
                <?= csrf_field() ?>
                
                <div class="form-field">
                    <label class="field-label" for="username">Username</label>
                    <div class="field-input-box">
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Masukkan username" 
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                            autocomplete="username" 
                            required 
                            autofocus
                        >
                        <svg class="input-lead-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                </div>

                <div class="form-field">
                    <label class="field-label" for="password">Password</label>
                    <div class="field-input-box">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Masukkan password" 
                            autocomplete="current-password" 
                            required
                        >
                        <svg class="input-lead-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <button type="button" class="toggle-pw-btn" onclick="togglePassword()" title="Lihat Password" aria-label="Tampilkan Password">
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit-login">
                    <span>Login</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </form>

            <!-- Security Footnote -->
            <div class="auth-footer">
                <div class="security-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <span>Dilindungi Enkripsi Sesi & Proteksi CSRF</span>
                </div>
                <div>
                    &copy; <?= date('Y') ?> <strong style="color: var(--kasql-primary);">KAsql</strong>. All rights reserved.
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password Visibility Toggle
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }

        // Theme Toggle
        document.getElementById('loginThemeToggle').addEventListener('click', function() {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            
            const darkIcon = this.querySelector('.theme-icon-dark');
            const lightIcon = this.querySelector('.theme-icon-light');
            if (next === 'dark') {
                darkIcon.style.display = 'none';
                lightIcon.style.display = 'block';
            } else {
                darkIcon.style.display = 'block';
                lightIcon.style.display = 'none';
            }
        });

        // Set Initial Theme Icon
        (function() {
            const theme = document.documentElement.getAttribute('data-theme');
            const btn = document.getElementById('loginThemeToggle');
            if (!btn) return;
            const darkIcon = btn.querySelector('.theme-icon-dark');
            const lightIcon = btn.querySelector('.theme-icon-light');
            if (theme === 'dark') {
                darkIcon.style.display = 'none';
                lightIcon.style.display = 'block';
            }
        })();

        // ============================================================
        // DYNAMIC HIGH-TECH CYBERNETIC MESH CANVAS
        // ============================================================
        (function() {
            const canvas = document.getElementById('techCanvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            let width, height, dpr;
            let nodes = [];
            let packets = [];
            const mouse = { x: null, y: null, maxDist: 150 };

            function resize() {
                dpr = window.devicePixelRatio || 1;
                width = window.innerWidth;
                height = window.innerHeight;
                canvas.width = width * dpr;
                canvas.height = height * dpr;
                canvas.style.width = width + 'px';
                canvas.style.height = height + 'px';
                ctx.scale(dpr, dpr);
                initNodes();
            }

            function initNodes() {
                nodes = [];
                packets = [];
                const count = Math.min(Math.max(Math.floor((width * height) / 36000), 22), 38);
                for (let i = 0; i < count; i++) {
                    nodes.push({
                        x: Math.random() * width,
                        y: Math.random() * height,
                        vx: (Math.random() - 0.5) * 0.55,
                        vy: (Math.random() - 0.5) * 0.55,
                        radius: Math.random() < 0.25 ? 2.5 : (Math.random() < 0.6 ? 1.8 : 1.4),
                        isAccent: Math.random() < 0.35,
                        accentColor: Math.random() < 0.75 ? '#ff5e1a' : '#10b981',
                        pulsePhase: Math.random() * Math.PI * 2
                    });
                }
            }

            window.addEventListener('resize', resize);
            window.addEventListener('mousemove', (e) => {
                mouse.x = e.clientX;
                mouse.y = e.clientY;
            });
            window.addEventListener('mouseleave', () => {
                mouse.x = null;
                mouse.y = null;
            });

            resize();

            // Spawn travelling data packets between nodes (smooth & subtle)
            function spawnPacket() {
                if (packets.length > 4 || nodes.length < 2) return;
                const fromIdx = Math.floor(Math.random() * nodes.length);
                let closest = -1;
                let minDist = 140;
                for (let j = 0; j < nodes.length; j++) {
                    if (fromIdx === j) continue;
                    const dx = nodes[fromIdx].x - nodes[j].x;
                    const dy = nodes[fromIdx].y - nodes[j].y;
                    const d = Math.hypot(dx, dy);
                    if (d < minDist && d > 35) {
                        closest = j;
                        minDist = d;
                    }
                }
                if (closest !== -1) {
                    packets.push({
                        from: fromIdx,
                        to: closest,
                        progress: 0,
                        speed: 0.01 + Math.random() * 0.012,
                        color: Math.random() < 0.75 ? '#ff5e1a' : '#10b981'
                    });
                }
            }

            setInterval(spawnPacket, 1200);

            function animate() {
                requestAnimationFrame(animate);
                ctx.clearRect(0, 0, width, height);

                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                const baseLineAlpha = isDark ? 0.12 : 0.07;

                // 1. Update & Draw Node Network
                for (let i = 0; i < nodes.length; i++) {
                    const n = nodes[i];
                    n.x += n.vx;
                    n.y += n.vy;
                    n.pulsePhase += 0.035;

                    if (n.x < 0 || n.x > width) n.vx *= -1;
                    if (n.y < 0 || n.y > height) n.vy *= -1;

                    // Mouse interactive thread
                    if (mouse.x !== null) {
                        const mdx = n.x - mouse.x;
                        const mdy = n.y - mouse.y;
                        const mDist = Math.hypot(mdx, mdy);
                        if (mDist < mouse.maxDist) {
                            const alpha = (1 - mDist / mouse.maxDist) * (isDark ? 0.35 : 0.2);
                            ctx.strokeStyle = `rgba(255, 94, 26, ${alpha})`;
                            ctx.lineWidth = 1;
                            ctx.beginPath();
                            ctx.moveTo(n.x, n.y);
                            ctx.lineTo(mouse.x, mouse.y);
                            ctx.stroke();
                        }
                    }

                    // Node interconnection lines
                    for (let j = i + 1; j < nodes.length; j++) {
                        const n2 = nodes[j];
                        const dx = n.x - n2.x;
                        const dy = n.y - n2.y;
                        const dist = Math.hypot(dx, dy);

                        if (dist < 135) {
                            const alpha = (1 - dist / 135) * baseLineAlpha;
                            ctx.strokeStyle = isDark 
                                ? `rgba(255, 255, 255, ${alpha})` 
                                : `rgba(15, 23, 42, ${alpha})`;
                            ctx.lineWidth = 0.75;
                            ctx.beginPath();
                            ctx.moveTo(n.x, n.y);
                            ctx.lineTo(n2.x, n2.y);
                            ctx.stroke();
                        }
                    }

                    // Render node point
                    const pulse = 1 + Math.sin(n.pulsePhase) * 0.35;
                    ctx.beginPath();
                    ctx.arc(n.x, n.y, n.radius * (n.isAccent ? pulse : 1), 0, Math.PI * 2);

                    if (n.isAccent) {
                        ctx.fillStyle = n.accentColor;
                        if (isDark) {
                            ctx.shadowColor = n.accentColor;
                            ctx.shadowBlur = 10;
                        }
                        ctx.fill();
                        ctx.shadowBlur = 0;
                    } else {
                        ctx.fillStyle = isDark ? 'rgba(255, 255, 255, 0.45)' : 'rgba(15, 23, 42, 0.45)';
                        ctx.fill();
                    }
                }

                // 2. Data Packets
                for (let k = packets.length - 1; k >= 0; k--) {
                    const p = packets[k];
                    p.progress += p.speed;
                    if (p.progress >= 1) {
                        packets.splice(k, 1);
                        continue;
                    }
                    const nA = nodes[p.from];
                    const nB = nodes[p.to];
                    if (!nA || !nB) {
                        packets.splice(k, 1);
                        continue;
                    }
                    const px = nA.x + (nB.x - nA.x) * p.progress;
                    const py = nA.y + (nB.y - nA.y) * p.progress;

                    ctx.beginPath();
                    ctx.arc(px, py, 2.6, 0, Math.PI * 2);
                    ctx.fillStyle = p.color;
                    if (isDark) {
                        ctx.shadowColor = p.color;
                        ctx.shadowBlur = 12;
                    }
                    ctx.fill();
                    ctx.shadowBlur = 0;
                }
            }

            requestAnimationFrame(animate);
        })();

    </script>
</body>
</html>
