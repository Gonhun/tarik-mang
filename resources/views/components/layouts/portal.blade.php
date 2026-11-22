<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Portal IT Supports</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* ---------- Reset / Base ---------- */
    * { box-sizing: border-box; }
    body {
        font-family: "Libre Baskerville", serif;
        font-weight: 400;
        font-size: 16px;
        line-height: 1.6;
        background-color: #0c0f15;
        color: #ababab;
        margin: 0;
        padding: 0;
        overflow: hidden;
        -webkit-font-smoothing:antialiased;
        -moz-osx-font-smoothing:grayscale;
    }

    /* ---------- Video Background ---------- */
    .video-background {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        overflow: hidden;
        z-index: 0;
    }

    .video-background iframe {
        position: absolute;
        top: 50%; left: 50%;
        width: 120%;
        height: 120%;
        transform: translate(-50%, -50%);
        pointer-events: none;
    }

    /* ---------- Overlay gelap di atas video ---------- */
    .overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.6);
        z-index: 1;
    }

    /* ---------- Page Content ---------- */
    .site-wrapper { min-height: 100vh; position: relative; z-index: 2; }
    .pt-tablecell {
        min-height: calc(100vh - 4px);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 2;
    }

    .container { position: relative; z-index: 2; width: 100%; max-width: 1200px; margin: 0 auto; padding: 40px; }

    .page-title { text-align: center; color: #fff; margin-bottom: 30px; }
    .heading-page { font-size: 36px; display:block; font-weight:700; letter-spacing:2px; }

    /* ---------- Hexagon Menu ---------- */
    .hexagon-menu {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        align-items: flex-start;
        gap: 0;
        margin-top: 20px;
        user-select: none;
    }

    .hexagon-item {
        cursor: pointer;
        width: 200px;
        height: 173.20508px;
        margin-left: -29px;
        position: relative;
        transform: rotate(30deg);
        transition: transform .25s ease, z-index .15s ease;
    }
    .hexagon-item:first-child { margin-left: 0; }
    .hexagon-item:nth-child(even) { margin-top: 86px; }
    .hexagon-item:hover { z-index: 5; transform: rotate(30deg) scale(1.03); }

    .hex-item {
        position: absolute;
        top: 0; left: 50px;
        width: 100px;
        height: 173.20508px;
        pointer-events: none;
    }
    .hex-item:first-child { z-index: 0; transform: scale(0.9); transition: all 0.3s cubic-bezier(.165,.84,.44,1); }
    .hex-item:last-child { z-index: 1; transition: all 0.3s cubic-bezier(.19,1,.22,1); }

    .hex-item div { position: absolute; top: 0; width: 100%; height: 173.20508px; transform-origin: center; }
    .hex-item div::before,
    .hex-item div::after {
        content: "";
        position: absolute;
        width: 100%; height: 3px;
        background-color: rgba(30,37,48,1);
        transition: all .25s ease;
    }
    .hex-item div::before { top: 0; }
    .hex-item div::after  { bottom: 0; }
    .hex-item div:nth-child(1) { transform: rotate(0deg); }
    .hex-item div:nth-child(2) { transform: rotate(60deg); }
    .hex-item div:nth-child(3) { transform: rotate(120deg); }

    .hexagon-item:hover .hex-item:last-child div::before,
    .hexagon-item:hover .hex-item:last-child div::after {
        background-color: #ff0037;
    }

    .hex-content {
        color: #fff;
        display: block;
        height: 180px;
        margin: 0 auto;
        position: relative;
        text-align: center;
        transform: rotate(-30deg);
        width: 156px;
        pointer-events: auto;
        text-decoration: none;
    }
    .hex-content .hex-content-inner {
        left: 50%;
        position: absolute;
        top: 50%;
        transform: translate(-50%, -50%);
    }
    .hex-content .icon { display:block; font-size: 28px; margin-bottom: 10px; color:#fff; }
    .hex-content .title {
        display: block;
        font-family: "Open Sans", sans-serif;
        font-size: 14px;
        letter-spacing: 1px;
        line-height: 20px;
        text-transform: uppercase;
        color: #e6e9ee;
    }

    .hex-content.work .title { color: #ffffff; }
    .hexagon-item.work .hex-item:last-child div::before,
    .hexagon-item.work .hex-item:last-child div::after {
        background-color: #ff4b4b;
    }

    .hex-content svg {
        left: -7px;
        position: absolute;
        top: -13px;
        transform: scale(0.87);
        z-index: -1;
        transition: all 0.3s cubic-bezier(.165,.84,.44,1);
        pointer-events: none;
    }

    @media (max-width: 991px) {
        .container { padding: 20px; }
        .hexagon-item { width: 160px; height: 138.564px; margin-left: -24px; }
        .hex-content { width: 125px; }
        .hex-item { left: 40px; }
        .hexagon-item:nth-child(even) { margin-top: 68px; }
    }
    @media (max-width: 767px) {
        .hexagon-menu { flex-direction: column; align-items: center; gap: 22px; }
        .hexagon-item { margin-left: 0; transform: rotate(30deg) translateY(0); }
        .hexagon-item:nth-child(even) { margin-top: 0; }
    }
</style>
</head>
<body>

    <!-- 🔥 Video YouTube sebagai background -->
    <div class="video-background">
        <iframe 
            src="https://www.youtube.com/embed/ouVCenTXG0o?autoplay=1&mute=1&controls=0&showinfo=0&loop=1&playlist=ouVCenTXG0o&modestbranding=1"
            frameborder="0"
            allow="autoplay; fullscreen"
            allowfullscreen>
        </iframe>
    </div>

    <!-- Overlay gelap -->
    <div class="overlay"></div>

<main class="site-wrapper">
    <div class="pt-tablecell">
        <div class="container">
            <div class="page-title">
                <span class="heading-page">Welcome to Portal IT</span>
                <p style="color: #ddd; max-width:700px; margin: 8px auto 0;">
                    Selamat datang di Portal IT Support! Silakan gunakan menu di bawah ini untuk mengakses layanan, informasi, dan fitur IT yang tersedia untuk Anda.
                </p>
            </div>

            <div class="hexagon-menu">
                @php
                    $hexItems = [
                        ['icon' => 'fa-regular fa-envelope', 'title' => 'Ticket','link' => 'helpdesk.binasaranasukses.co.id'],
                        ['icon' => 'fa-solid fa-laptop', 'title' => 'AMS','link' => 'ams.binasaranasukses.co.id'],
                        ['icon' => 'fa-braille', 'title' => 'Perjadin','link' => 'ams.binasaranasukses.com'],
                        ['icon' => 'fa-id-badge', 'title' => 'MeReport','link' => 'ams.binasaranasukses.com'],
                        ['icon' => 'fa-life-ring', 'title' => 'Dashboard Kinerja','link' => 'ams.binasaranasukses.com'],
                        ['icon' => 'fa-regular fa-newspaper', 'title' => 'SmartForm','link' => 'ams.binasaranasukses.com'],
                        ['icon' => 'fa-regular fa-pen-to-square', 'title' => 'BAJS','link' => 'ams.binasaranasukses.com'],
                    ];
                @endphp

                @foreach ($hexItems as $i => $item)
                    <div class="hexagon-item {{ strtolower($item['title']) === 'ams' ? 'work' : '' }}">
                        <div class="hex-item"><div></div><div></div><div></div></div>
                        <div class="hex-item"><div></div><div></div><div></div></div>
                        <a class="hex-content" title="{{ $item['title'] }}"  href="https://{{ $item['link'] }}"
                        target="_blank"
                        rel="noopener noreferrer">
                            <span class="hex-content-inner">
                                <span class="icon"><i class="fa {{ $item['icon'] }}"></i></span>
                                <span class="title">{{ strtoupper($item['title']) }}</span>
                            </span>
                            <svg viewBox="0 0 173.20508075688772 200" height="200" width="174">
                                <path d="M86.60254037844386 0L173.20508075688772 50L173.20508075688772 150L86.60254037844386 200L0 150L0 50Z" fill="#1e2530"></path>
                            </svg>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</main>
</body>
</html>
