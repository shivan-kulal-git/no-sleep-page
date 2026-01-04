<?php
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

// reCAPTCHA v3 keys 
define('RECAPTCHA_SITE_KEY', 'YOUR_SITE_KEY');
define('RECAPTCHA_SECRET_KEY', 'YOUR_SECRET_KEY');

// Handle non-blocking bot score submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['page_token'])) {

    $data = http_build_query([
        'secret'   => RECAPTCHA_SECRET_KEY,
        'response' => $_POST['page_token'],
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => $data,
            'timeout' => 5
        ]
    ]);

    $res = @file_get_contents(
        'https://www.google.com/recaptcha/api/siteverify',
        false,
        $context
    );

    $r = json_decode($res ?: '{}', true);

    $_SESSION['bot_score'] = $r['score'] ?? 0;
    $_SESSION['bot_ok']    = $r['success'] ?? false;

    exit;
}

// Asset discovery
$assetDir = __DIR__ . '/assets';
$assetUrl = '/assets/';

$imageExt = ['jpg','jpeg','png','webp'];
$videoExt = ['mp4'];
$media = [];

if (is_dir($assetDir)) {
    foreach (scandir($assetDir) as $file) {
        if ($file === '.' || $file === '..') continue;

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (in_array($ext, $imageExt)) {
            $media[] = ['type' => 'image', 'src' => $assetUrl . $file];
        } elseif (in_array($ext, $videoExt)) {
            $media[] = ['type' => 'video', 'src' => $assetUrl . $file];
        }
    }
}

// Fallback background
if (!$media) {
    $media[] = ['type'=>'image','src'=>'https://via.placeholder.com/1920x1080'];
}

// Deterministic daily start index
$startIndex = crc32(date('Ymd')) % count($media);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>No Sleep | Keep Awake</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<script src="https://www.google.com/recaptcha/api.js?render=YOUR_SITE_KEY"></script>

<style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{height:100%}
body{
    background:#000;
    color:#fff;
    font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;
    overflow:hidden;
}

/* Background */
.bg-wrap{position:fixed;inset:0}
.bg-media{
    position:absolute;
    inset:0;
    width:100%;
    height:100%;
    object-fit:cover;
    opacity:0;
    transition:opacity .6s ease;
}
.bg-media.active{opacity:1}

/* Overlay */
.overlay{
    position:fixed;
    inset:0;
/*  background:rgba(0,0,0,.45); */    
    pointer-events:none;
}

/* Auto-cycle toggle */
.auto-toggle{
    position:fixed;
    top:14px;
    right:14px;
    z-index:5;
    padding:8px 14px;
    border-radius:20px;
    background:#111;
    border:1px solid #333;
    color:#00eaff;
    font-size:80%;
    cursor:pointer;
    width:10%;
}
.auto-toggle.active{
    background:#00eaff;
    color:#000;
}

/* UI */
.container{
    position:relative;
    z-index:2;
    height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
    transition:opacity .5s ease;
}
.hide-ui .container{
    opacity:0;
    pointer-events:none;
}

.card{
    width:100%;
    max-width:420px;
    background:rgba(0,0,0,.6);
    border-radius:16px;
    padding:22px;
    text-align:center;
}

h1{font-size:26px;color:#00eaff;margin-bottom:12px}

button,select{
    width:100%;
    margin-top:10px;
    padding:10px;
    border-radius:10px;
    border:1px solid #333;
    background:#111;
    color:#fff;
    cursor:pointer;
}

.controls{display:flex;gap:10px}
.controls button{width:50%}

.status{font-size:13px;margin-top:8px;opacity:.8}

@media(max-width:480px){
    h1{font-size:22px}
}
</style>
</head>

<body>

<div class="bg-wrap" id="bgWrap"></div>
<div class="overlay"></div>

<button class="auto-toggle" id="autoToggle">Auto Cycle OFF</button>

<div class="container">
    <div class="card">
        <h1>No Sleep Mode</h1>

        <button id="wakeBtn">Keep Screen Awake</button>
        <div class="status" id="wakeStatus">Inactive</div>

        <div class="controls">
            <button id="prev">Back</button>
            <button id="next">Next</button>
        </div>

        <select id="mediaSelect"></select>
    </div>
</div>

<script>
// Send reCAPTCHA signal without blocking UI
grecaptcha.ready(() => {
    grecaptcha.execute('YOUR_SITE_KEY', { action: 'page_view' })
        .then(token => {
            navigator.sendBeacon(
                location.pathname,
                new URLSearchParams({ page_token: token })
            );
        });
});

const mediaList = <?php echo json_encode($media); ?>;
let index = <?php echo (int)$startIndex; ?>;

const AUTO_SECONDS = 60;
let autoTimer = null;
let hideTimer = null;

const bgWrap = document.getElementById('bgWrap');
const select = document.getElementById('mediaSelect');
const autoBtn = document.getElementById('autoToggle');
const elements = [];

// Build media elements once
mediaList.forEach((item, i) => {
    let el = item.type === 'video' ? document.createElement('video') : document.createElement('img');

    el.src = item.src;
    el.className = 'bg-media';

    if (item.type === 'video') {
        el.muted = el.loop = el.autoplay = el.playsInline = true;
        el.preload = 'auto';
    }

    bgWrap.appendChild(el);
    elements.push(el);

    const name = item.src.split('/').pop().replace(/\.[^/.]+$/, '').replace(/[_-]/g,' ');
    select.add(new Option(name, i));
});

// Display selected media
function showMedia(i){
    index = (i + elements.length) % elements.length;
    localStorage.setItem('nosleep-bg', index);

    elements.forEach((el, idx) => {
        el.classList.toggle('active', idx === index);
        if (el.tagName === 'VIDEO') idx === index ? el.play().catch(()=>{}) : el.pause();
    });

    select.value = index;
}

document.getElementById('next').onclick = () => showMedia(index + 1);
document.getElementById('prev').onclick = () => showMedia(index - 1);
select.onchange = e => showMedia(+e.target.value);

// Restore last background
const saved = localStorage.getItem('nosleep-bg');
if (saved !== null) index = +saved;

// Toggle auto cycling
autoBtn.onclick = () => {
    autoTimer ? clearInterval(autoTimer) : autoTimer = setInterval(() => showMedia(index + 1), AUTO_SECONDS * 1000);
    autoBtn.classList.toggle('active', !!autoTimer);
    autoBtn.textContent = autoTimer ? 'Auto Cycle ON' : 'Auto Cycle OFF';
};

// Hide UI after inactivity
function resetHide(){
    document.body.classList.remove('hide-ui');
    clearTimeout(hideTimer);
    hideTimer = setTimeout(() => document.body.classList.add('hide-ui'), 8000);
}
['mousemove','keydown','touchstart'].forEach(e => document.addEventListener(e, resetHide));
resetHide();

// Wake Lock support
let wakeLock = null;
const status = document.getElementById('wakeStatus');

document.getElementById('wakeBtn').onclick = async () => {
    try {
        if ('wakeLock' in navigator) {
            wakeLock = await navigator.wakeLock.request('screen');
            status.textContent = 'Active';
        } else status.textContent = 'Not supported';
    } catch {
        status.textContent = 'Denied';
    }
};

showMedia(index);
</script>

</body>
</html>
