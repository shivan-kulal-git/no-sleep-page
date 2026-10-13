
<?php
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

define('RECAPTCHA_SITE_KEY', '6LfVWjIsAAAAAIxBmKgzcCbgLN2lEjtjacqMOmDG');
define('RECAPTCHA_SECRET_KEY', '6LfVWjIsAAAAAPpLFdnNQUMcJR8VQa1C0nalZ7eg');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['page_token'])) {

    $data = http_build_query([
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $_POST['page_token'],
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => $data,
            'timeout' => 5
        ]
    ]);

    $res = file_get_contents(
        'https://www.google.com/recaptcha/api/siteverify',
        false,
        $context
    );

    $r = json_decode($res, true);

    $_SESSION['bot_score'] = $r['score'] ?? 0;
    $_SESSION['bot_ok'] = $r['success'] ?? false;

    exit;
}

$assetDir = __DIR__ . '/assets/';
$assetUrl = '/assets/';

$imageExt = ['jpg', 'jpeg', 'png', 'webp'];
$videoExt = ['mp4'];

$media = [];

if (is_dir($assetDir)) {

    foreach (scandir($assetDir) as $file) {

        if ($file === '.' || $file === '..') {
            continue;
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (in_array($ext, $imageExt, true)) {

            $media[] = [
                'type' => 'image',
                'src' => $assetUrl . rawurlencode($file)
            ];

        } elseif (in_array($ext, $videoExt, true)) {

            $media[] = [
                'type' => 'video',
                'src' => $assetUrl . rawurlencode($file)
            ];
        }
    }
}

if (count($media) === 0) {

    $media[] = [
        'type' => 'image',
        'src' => 'https://via.placeholder.com/1920x1080?text=No+Media'
    ];
}

$dayKey = date('Ymd');
$startIndex = crc32($dayKey) % count($media);

?>
<!DOCTYPE html>
<html lang="en">

<head>

<script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES); ?>"></script>

<script async
    src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2527528185076488"
    crossorigin="anonymous"></script>
    
<script async custom-element="amp-auto-ads"
        src="https://cdn.ampproject.org/v0/amp-auto-ads-0.1.js">
</script>

<meta charset="UTF-8">

<title>No Sleep | Keep Awake</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<meta name="google-adsense-account" content="ca-pub-2527528185076488">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html,
body{
    width:100%;
    min-height:100%;
}

body{
    background:#000;
    color:#fff;
    font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
    overflow-x:hidden;
}

.bg-wrap{
    position:fixed;
    inset:0;
    z-index:0;
}

.bg-media{
    position:absolute;
    inset:0;
    width:100%;
    height:100%;
    object-fit:cover;
    opacity:0;
    transition:opacity .6s ease;
}

.bg-media.active{
    opacity:1;
}

.overlay{
    position:fixed;
    inset:0;
    pointer-events:none;
    /*background:linear-gradient(rgba(0,0,0,.32), rgba(0,0,0,.48));*/
}

.auto-toggle{
    position:fixed;
    top:14px;
    right:14px;
    z-index:20;
    padding:8px 14px;
    border-radius:20px;
    background:#111;
    border:1px solid #333;
    color:#00eaff;
    font-size:80%;
    cursor:pointer;
    width:auto;
}

.auto-toggle.active{
    background:#00eaff;
    color:#000;
}

.container{
    position:fixed;
    top:40%;
    left:50%;
    transform:translate(-50%,-50%);
    width:min(420px,calc(100vw - 32px));
    z-index:10;
    display:flex;
    align-items:center;
    justify-content:center;
    opacity:0;
    pointer-events:none;
    transition:opacity .2s ease;
}

.container.show{
    opacity:1;
    pointer-events:auto;
}

.card{
    width:100%;
    background:rgba(0,0,0,.62);
    border:1px solid rgba(255,255,255,.08);
    border-radius:18px;
    padding:22px;
    text-align:center;
    box-shadow:0 12px 32px rgba(0,0,0,.3);
}

h1{
    font-size:26px;
    color:#00eaff;
    margin-bottom:12px;
}

button,
select{
    width:100%;
    margin-top:10px;
    padding:11px 12px;
    border-radius:10px;
    border:1px solid #333;
    background:#111;
    color:#fff;
    cursor:pointer;
    font:inherit;
}

button:hover,
select:hover{
    border-color:#00eaff;
}

.controls{
    display:flex;
    gap:10px;
}

.controls button{
    width:50%;
}

.status{
    font-size:13px;
    margin-top:8px;
    opacity:.8;
}

.ad-dock{
    position:fixed;
    left:50%;
    bottom:12px;
    transform:translate(-50%, 18px);
    width:min(780px,calc(100vw - 28px));
    background:rgba(0,0,0,.72);
    border:1px solid rgba(255,255,255,.08);
    border-radius:16px;
    box-shadow:0 12px 32px rgba(0,0,0,.25);
    padding:12px 14px 14px;
    opacity:0;
    pointer-events:none;
    transition:opacity .25s ease, transform .25s ease;
    z-index:12;
}

.ad-dock.show{
    opacity:1;
    pointer-events:auto;
    transform:translate(-50%, 0);
}

.ad-label{
    font-size:11px;
    letter-spacing:.12em;
    text-transform:uppercase;
    color:#a7b3c7;
    margin-bottom:10px;
    text-align:center;
}

.ad-track{
    position:relative;
    min-height:120px;
    display:flex;
    align-items:center;
    justify-content:center;
}

.ad-item{
    position:absolute;
    inset:0;
    display:flex;
    align-items:center;
    justify-content:center;
    text-align:center;
    border:1px dashed rgba(255,255,255,.2);
    border-radius:12px;
    background:rgba(255,255,255,.02);
    color:#dfe6ef;
    font-size:14px;
    line-height:1.5;
    padding:12px;
    opacity:0;
    transition:opacity .35s ease;
}

.ad-item.active{
    opacity:1;
}

@media(max-width:480px){

    h1{
        font-size:22px;
    }

    .card{
        padding:18px 16px;
    }
}

</style>

</head>

<body>
    <amp-auto-ads type="adsense"
        data-ad-client="ca-pub-2527528185076488">
</amp-auto-ads>

<div class="bg-wrap" id="bgWrap"></div>

<div class="overlay"></div>

<button
    class="auto-toggle"
    id="autoToggle"
>
    Auto Cycle OFF
</button>

<div
    class="container"
    id="controlContainer"
>

    <div class="card">

        <h1>No Sleep Mode</h1>

        <button id="wakeBtn">
            Keep Screen Awake
        </button>

        <div
            class="status"
            id="wakeStatus"
        >
            Inactive
        </div>

        <div class="controls">

            <button id="prev">
                Back
            </button>

            <button id="next">
                Next
            </button>

        </div>

        <select id="mediaSelect"></select>

    </div>

</div>



<!--<div class="ad-dock" id="adDock" aria-label="Advertisement">-->
<!--    <div class="ad-label">Advertisement</div>-->
<!--    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2527528185076488"-->
<!--     crossorigin="anonymous"></script>-->
<!-- NoSleep Bottom Responsive -->
<!--<ins class="adsbygoogle"-->
<!--     style="display:block"-->
<!--     data-ad-client="ca-pub-2527528185076488"-->
<!--     data-ad-slot="5460787141"-->
<!--     data-ad-format="auto"-->
<!--     data-full-width-responsive="true"></ins>-->
<!--<script>-->
<!--     (adsbygoogle = window.adsbygoogle || []).push({});-->
<!--</script>-->
<!--</div>-->

<script>

grecaptcha.ready(() => {

    grecaptcha.execute(
        '<?php echo htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES); ?>',
        {
            action:'page_view'
        }
    )
    .then(token => {

        navigator.sendBeacon(
            location.pathname,
            new URLSearchParams({
                page_token:token
            })
        );

    })
    .catch(() => {});

});

const mediaList = <?php echo json_encode(
    $media,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
); ?>;

let index = <?php echo (int)$startIndex; ?>;

const AUTO_SECONDS = 60;

let autoTimer = null;

const bgWrap = document.getElementById('bgWrap');
const select = document.getElementById('mediaSelect');
const autoBtn = document.getElementById('autoToggle');
const controlContainer = document.getElementById('controlContainer');

const elements = [];

mediaList.forEach((item, i) => {

    let el;

    if(item.type === 'video'){

        el = document.createElement('video');

        el.src = item.src;
        el.muted = true;
        el.loop = true;
        el.autoplay = true;
        el.playsInline = true;
        el.preload = 'auto';

    }else{

        el = document.createElement('img');

        el.src = item.src;
        el.loading = 'eager';

    }

    el.className = 'bg-media';

    bgWrap.appendChild(el);

    elements.push(el);

    const fileName = decodeURIComponent(
        item.src.split('/').pop()
    );

    const opt = document.createElement('option');

    opt.value = i;

    opt.textContent = fileName
        .replace(/\.[^/.]+$/, '')
        .replace(/[_-]/g, ' ')
        .replace(/\b\w/g, c => c.toUpperCase());

    select.appendChild(opt);

});

function showMedia(i){

    if(elements.length === 0){
        return;
    }

    index = (i + elements.length) % elements.length;

    localStorage.setItem(
        'nosleep-bg',
        index
    );

    elements.forEach((el, idx) => {

        if(idx === index){

            el.classList.add('active');

            if(el.tagName === 'VIDEO'){

                el.currentTime = 0;

                el.play().catch(() => {});

            }

        }else{

            el.classList.remove('active');

            if(el.tagName === 'VIDEO'){
                el.pause();
            }

        }

    });

    select.value = index;
}

document.getElementById('next').onclick = () => {
    showMedia(index + 1);
};

document.getElementById('prev').onclick = () => {
    showMedia(index - 1);
};

select.onchange = event => {

    showMedia(
        parseInt(
            event.target.value,
            10
        )
    );

};

const saved = localStorage.getItem('nosleep-bg');

if(saved !== null){

    const savedIndex = parseInt(saved, 10);

    if(
        !Number.isNaN(savedIndex) &&
        savedIndex >= 0 &&
        savedIndex < elements.length
    ){

        index = savedIndex;

    }
}

autoBtn.onclick = () => {

    if(autoTimer){

        clearInterval(autoTimer);

        autoTimer = null;

        autoBtn.textContent = 'Auto Cycle OFF';

        autoBtn.classList.remove('active');

    }else{

        autoTimer = setInterval(
            () => {
                showMedia(index + 1);
            },
            AUTO_SECONDS * 1000
        );

        autoBtn.textContent = 'Auto Cycle ON';

        autoBtn.classList.add('active');

    }

};

let wakeLock = null;

const status = document.getElementById('wakeStatus');

document.getElementById('wakeBtn').onclick = async () => {

    try{

        if('wakeLock' in navigator){

            wakeLock =
                await navigator.wakeLock.request('screen');

            status.textContent = 'Active';

            wakeLock.addEventListener(
                'release',
                () => {
                    status.textContent = 'Inactive';
                }
            );

        }else{

            status.textContent = 'Not supported';

        }

    }catch(error){

        status.textContent = 'Denied';

        console.error(
            'Wake Lock error:',
            error
        );

    }

};

document.addEventListener(
    'visibilitychange',
    async () => {

        if(
            document.visibilityState === 'visible' &&
            wakeLock !== null
        ){

            try{

                wakeLock =
                    await navigator.wakeLock.request('screen');

                status.textContent = 'Active';

            }catch(error){

                status.textContent = 'Inactive';

            }

        }

    }
);

const adDock = document.getElementById('adDock');
const adItems = Array.from(document.querySelectorAll('.ad-item'));
const toolHideDelay = 4000;
const adHideDelay = 300000;

let adIndex = 0;
let adCycleTimer = null;
let hideToolTimer = null;
let hideAdTimer = null;

function showToolPanel(){
    controlContainer.classList.add('show');
    clearTimeout(hideToolTimer);
    hideToolTimer = setTimeout(() => {
        controlContainer.classList.remove('show');
    }, toolHideDelay);
}

function hideToolPanel(){
    clearTimeout(hideToolTimer);
    controlContainer.classList.remove('show');
}

function showAdDock(){
    adDock.classList.add('show');
    clearTimeout(hideAdTimer);
    hideAdTimer = setTimeout(() => {
        adDock.classList.remove('show');
    }, adHideDelay);
}

function hideAdDock(){
    clearTimeout(hideAdTimer);
    adDock.classList.remove('show');
}

function cycleAds(){
    if(adItems.length <= 1){
        return;
    }

    adItems.forEach((item, index) => {
        item.classList.toggle('active', index === adIndex);
    });

    adIndex = (adIndex + 1) % adItems.length;
}

if(adItems.length > 1){
    adCycleTimer = setInterval(cycleAds, 3000);
}

function handleActivity(event){
    const rect = controlContainer.getBoundingClientRect();
    const mouseX = event.clientX;
    const mouseY = event.clientY;
    const insideTool =
        mouseX >= rect.left &&
        mouseX <= rect.right &&
        mouseY >= rect.top &&
        mouseY <= rect.bottom;

    if(insideTool){
        showToolPanel();
    } else {
        hideToolPanel();
    }

    showAdDock();
}

document.addEventListener('mousemove', handleActivity);
document.addEventListener('mouseenter', showAdDock);
document.addEventListener('mouseleave', () => {
    hideToolPanel();
    hideAdDock();
});

document.addEventListener('touchstart', () => {
    showToolPanel();
    showAdDock();
}, { passive: true });

showMedia(index);

</script>

</body>

</html>
