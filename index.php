<?php
declare(strict_types=1);

/*
 * No-Sleep | Keep Awake
 */

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$assetDir = __DIR__ . '/assets/';
$assetUrl = '/assets/';
$imageExt = ['jpg', 'jpeg', 'png', 'webp'];
$videoExt = ['mp4'];
$media = [];

if (is_dir($assetDir)) {
    $files = scandir($assetDir);

    if ($files !== false) {
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            // Only serve direct files in assets; do not recurse into folders.
            $fullPath = $assetDir . $file;
            if (!is_file($fullPath)) {
                continue;
            }

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if (in_array($ext, $imageExt, true)) {
                $media[] = [
                    'type' => 'image',
                    'src' => $assetUrl . rawurlencode($file),
                ];
            } elseif (in_array($ext, $videoExt, true)) {
                $media[] = [
                    'type' => 'video',
                    'src' => $assetUrl . rawurlencode($file),
                ];
            }
        }
    }
}

if ($media === []) {
    $media[] = [
        'type' => 'image',
        'src' => 'https://via.placeholder.com/1920x1080?text=No+Media',
    ];
}

// Stable daily starting item. Replaces crc32 with a predictable integer hash.
$dayKey = date('Ymd');
$startIndex = (int) (sprintf('%u', crc32($dayKey)) % count($media));

$mediaJson = json_encode(
    $media,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG |
    JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);

if ($mediaJson === false) {
    $mediaJson = '[]';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-adsense-account" content="ca-pub-2527528185076488">
    <title>No Sleep | Keep Awake</title>

    <!-- AdSense publisher/client ID is public, not a private secret. -->
    <script async
        src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2527528185076488"
        crossorigin="anonymous"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body { width: 100%; min-height: 100%; }

        body {
            background: #000;
            color: #fff;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI",
                Roboto, Arial, sans-serif;
            overflow: hidden;
        }

        .bg-wrap { position: fixed; inset: 0; z-index: 0; }

        .bg-media {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity .6s ease;
        }

        .bg-media.active { opacity: 1; }

        .overlay {
            position: fixed;
            inset: 0;
            pointer-events: none;
        }

        .auto-toggle {
            position: fixed;
            top: 14px;
            right: 14px;
            z-index: 20;
            padding: 8px 14px;
            border-radius: 20px;
            background: #111;
            border: 1px solid #333;
            color: #00eaff;
            font-size: 80%;
            cursor: pointer;
            width: auto;
        }

        .auto-toggle.active { background: #00eaff; color: #000; }

        .container {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: min(420px, calc(100vw - 32px));
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease;
        }

        .container.show { opacity: 1; pointer-events: auto; }

        .card {
            width: 100%;
            background: rgba(0, 0, 0, .62);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 18px;
            padding: 22px;
            text-align: center;
            box-shadow: 0 12px 32px rgba(0, 0, 0, .3);
        }

        h1 { font-size: 26px; color: #00eaff; margin-bottom: 12px; }

        button, select {
            width: 100%;
            margin-top: 10px;
            padding: 11px 12px;
            border-radius: 10px;
            border: 1px solid #333;
            background: #111;
            color: #fff;
            cursor: pointer;
            font: inherit;
        }

        button:hover, select:hover { border-color: #00eaff; }

        .controls { display: flex; gap: 10px; }
        .controls button { width: 50%; }
        .status { font-size: 13px; margin-top: 8px; opacity: .8; }

        @media (max-width: 480px) {
            h1 { font-size: 22px; }
            .card { padding: 18px 16px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .bg-media, .container { transition: none; }
        }
    </style>
</head>
<body>
    <div class="bg-wrap" id="bgWrap" aria-hidden="true"></div>
    <div class="overlay"></div>

    <button class="auto-toggle" id="autoToggle" type="button">
        Auto Cycle OFF
    </button>

    <main class="container" id="controlContainer">
        <section class="card" aria-label="No Sleep controls">
            <h1>No Sleep Mode</h1>

            <button id="wakeBtn" type="button">Keep Screen Awake</button>
            <div class="status" id="wakeStatus" role="status" aria-live="polite">Inactive</div>

            <div class="controls">
                <button id="prev" type="button">Back</button>
                <button id="next" type="button">Next</button>
            </div>

            <label for="mediaSelect" class="sr-only">Choose background media</label>
            <select id="mediaSelect"></select>
        </section>
    </main>

    <script>
    'use strict';

    const mediaList = <?= $mediaJson ?>;
    let index = <?= $startIndex ?>;
    const AUTO_SECONDS = 60;

    let autoTimer = null;
    let wakeLock = null;
    let hideToolTimer = null;

    const bgWrap = document.getElementById('bgWrap');
    const select = document.getElementById('mediaSelect');
    const autoBtn = document.getElementById('autoToggle');
    const controlContainer = document.getElementById('controlContainer');
    const status = document.getElementById('wakeStatus');
    const elements = [];

    mediaList.forEach((item, i) => {
        let el;

        if (item.type === 'video') {
            el = document.createElement('video');
            el.src = item.src;
            el.muted = true;
            el.loop = true;
            el.autoplay = true;
            el.playsInline = true;
            el.preload = 'metadata';
        } else {
            el = document.createElement('img');
            el.src = item.src;
            el.alt = '';
            el.decoding = 'async';
        }

        el.className = 'bg-media';
        bgWrap.appendChild(el);
        elements.push(el);

        let fileName = item.src.split('/').pop() || `Media ${i + 1}`;
        try { fileName = decodeURIComponent(fileName); } catch (_) {}
        const option = document.createElement('option');
        option.value = String(i);
        option.textContent = fileName
            .replace(/\.[^/.]+$/, '')
            .replace(/[_-]/g, ' ')
            .replace(/\b\w/g, c => c.toUpperCase());
        select.appendChild(option);
    });

    function showMedia(nextIndex) {
        if (elements.length === 0) return;

        index = ((nextIndex % elements.length) + elements.length) % elements.length;

        try { localStorage.setItem('nosleep-bg', String(index)); } catch (_) {}

        elements.forEach((el, i) => {
            const active = i === index;
            el.classList.toggle('active', active);

            if (el.tagName === 'VIDEO') {
                if (active) {
                    try { el.currentTime = 0; } catch (_) {}
                    el.play().catch(() => {});
                } else {
                    el.pause();
                }
            }
        });

        select.value = String(index);
    }

    document.getElementById('next').addEventListener('click', () => showMedia(index + 1));
    document.getElementById('prev').addEventListener('click', () => showMedia(index - 1));
    select.addEventListener('change', event => showMedia(Number.parseInt(event.target.value, 10)));

    try {
        const saved = localStorage.getItem('nosleep-bg');
        if (saved !== null) {
            const savedIndex = Number.parseInt(saved, 10);
            if (Number.isInteger(savedIndex) && savedIndex >= 0 && savedIndex < elements.length) {
                index = savedIndex;
            }
        }
    } catch (_) {}

    autoBtn.addEventListener('click', () => {
        if (autoTimer !== null) {
            clearInterval(autoTimer);
            autoTimer = null;
            autoBtn.textContent = 'Auto Cycle OFF';
            autoBtn.classList.remove('active');
        } else {
            autoTimer = setInterval(() => showMedia(index + 1), AUTO_SECONDS * 1000);
            autoBtn.textContent = 'Auto Cycle ON';
            autoBtn.classList.add('active');
        }
    });

    document.getElementById('wakeBtn').addEventListener('click', async () => {
        if (!('wakeLock' in navigator)) {
            status.textContent = 'Not supported by this browser';
            return;
        }

        try {
            if (wakeLock && !wakeLock.released) {
                await wakeLock.release();
                wakeLock = null;
                status.textContent = 'Inactive';
                return;
            }

            wakeLock = await navigator.wakeLock.request('screen');
            status.textContent = 'Active';
            wakeLock.addEventListener('release', () => {
                status.textContent = 'Inactive';
                wakeLock = null;
            }, { once: true });
        } catch (error) {
            status.textContent = 'Permission denied';
            console.error('Wake Lock error:', error);
        }
    });

    document.addEventListener('visibilitychange', async () => {
        if (document.visibilityState === 'visible' && wakeLock === null &&
            status.textContent === 'Active' && 'wakeLock' in navigator) {
            try {
                wakeLock = await navigator.wakeLock.request('screen');
                wakeLock.addEventListener('release', () => {
                    status.textContent = 'Inactive';
                    wakeLock = null;
                }, { once: true });
            } catch (_) {
                status.textContent = 'Inactive';
            }
        }
    });

    function showToolPanel() {
        controlContainer.classList.add('show');
        clearTimeout(hideToolTimer);
        hideToolTimer = setTimeout(() => controlContainer.classList.remove('show'), 4000);
    }

    function hideToolPanel() {
        clearTimeout(hideToolTimer);
        controlContainer.classList.remove('show');
    }

    document.addEventListener('mousemove', event => {
        const rect = controlContainer.getBoundingClientRect();
        const inside = event.clientX >= rect.left && event.clientX <= rect.right &&
            event.clientY >= rect.top && event.clientY <= rect.bottom;
        if (inside) showToolPanel();
        else hideToolPanel();
    });

    document.addEventListener('mouseenter', showToolPanel);
    document.addEventListener('mouseleave', hideToolPanel);
    document.addEventListener('touchstart', showToolPanel, { passive: true });

    showMedia(index);
    </script>
</body>
</html>
