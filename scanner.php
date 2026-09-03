<?php 
include 'db_config.php'; 
include 'header.php'; 

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Access control: Ensure only organizers (Role 3) can access
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    echo "
    <div class='min-h-screen flex items-center justify-center bg-gray-900 p-10'>
        <div class='text-center glass p-10 rounded-3xl border border-red-500/30'>
            <div class='text-6xl mb-6'>🚫</div>
            <h1 class='text-white text-3xl font-black mb-2 italic'>ACCESS DENIED</h1>
            <p class='text-gray-400 text-sm mb-8 uppercase tracking-widest font-bold'>Only verified Organizers can scan tickets</p>
            <a href='dashboard.php' class='bg-purple-600 hover:bg-purple-700 text-white px-8 py-4 rounded-2xl font-black uppercase text-xs tracking-tighter transition shadow-lg shadow-purple-500/20'>
                Return to Dashboard
            </a>
        </div>
    </div>";
    exit();
}
?>

<style>
    #reader { border: none !important; width: 100% !important; }
    #reader__dashboard_section_csr button {
        background: #9333ea !important;
        color: white !important;
        border-radius: 12px !important;
        padding: 10px 20px !important;
        text-transform: uppercase !important;
        font-weight: 900 !important;
        font-size: 10px !important;
        border: none !important;
        margin-top: 10px !important;
    }
    #reader__status_span { color: #a855f7 !important; font-weight: bold !important; }
    #reader video { border-radius: 2rem !important; object-fit: cover; width: 100% !important; }
</style>

<script src="https://unpkg.com/html5-qrcode"></script>

<div class="p-8 max-w-xl mx-auto min-h-screen">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-black tracking-tighter text-white">Ticket <span class="text-purple-500">Scanner</span></h1>
            <p class="text-gray-500 text-[10px] uppercase tracking-[0.2em] font-black">PartyPass SL Official</p>
        </div>
        <a href="dashboard.php" class="bg-white/5 border border-white/10 text-white text-[10px] font-bold px-4 py-2 rounded-lg uppercase tracking-widest hover:bg-white/10">Exit</a>
    </div>

    <div id="reader" class="overflow-hidden rounded-[2rem] border-4 border-purple-500/20 bg-black shadow-2xl mb-8 min-h-[300px]"></div>
    
    <div id="scan-result" class="mt-4">
        <div id="setup-message" class="p-8 border-2 border-dashed border-white/10 rounded-[2rem] text-center">
            <div class="text-3xl mb-2 opacity-20">📷</div>
            <p class="text-gray-500 text-xs font-bold uppercase tracking-widest">Waiting for camera permission...</p>
        </div>
    </div>

    <div class="mt-8 text-center">
        <label for="qr-file" class="inline-block bg-purple-500/10 border border-purple-500/20 px-6 py-3 rounded-2xl text-purple-400 text-[10px] font-black uppercase tracking-widest cursor-pointer hover:bg-purple-500 hover:text-white transition">
            Trouble scanning? Upload QR Image
        </label>
        <input type="file" id="qr-file" accept="image/*" class="hidden">
    </div>
</div>

<script>
const resultContainer = document.getElementById('scan-result');
const qrFileInput = document.getElementById('qr-file');

function onScanSuccess(decodedText) {
    if (navigator.vibrate) navigator.vibrate(100);
    
    // UI Update: Show "Verifying"
    resultContainer.innerHTML = `
        <div class="p-8 bg-purple-500/10 rounded-[2rem] text-center animate-pulse border border-purple-500/30">
            <p class="text-purple-400 text-xs font-black uppercase tracking-widest">Verifying Ticket...</p>
            <p class="text-white text-[10px] mt-1 opacity-50 font-mono italic">${decodedText}</p>
        </div>`;
    
    // AJAX to Verify
    const params = new URLSearchParams();
    params.append('ticket_code', decodedText);

    fetch('verify_ticket.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params
    })
    .then(response => response.text())
    .then(data => {
        resultContainer.innerHTML = data;
        // Wait 5 seconds so the organizer can see if it's "Valid" or "Invalid", then refresh
        setTimeout(() => { location.reload(); }, 5000);
    })
    .catch(err => {
        resultContainer.innerHTML = `<div class="p-6 bg-red-500/20 text-red-500 rounded-2xl text-center font-bold">Network Error - Check verify_ticket.php</div>`;
    });
}

// 1. Initialize Camera Scanner
const html5QrcodeScanner = new Html5QrcodeScanner("reader", { 
    fps: 15, 
    qrbox: {width: 250, height: 250},
    aspectRatio: 1.0
});

html5QrcodeScanner.render(onScanSuccess, (error) => {
    // We ignore constant scanning errors to keep the console clean
});

// 2. Fix: Manual File Upload Handler
qrFileInput.addEventListener('change', e => {
    if (e.target.files.length === 0) return;
    
    const file = e.target.files[0];
    const html5QrCode = new Html5Qrcode("reader");

    resultContainer.innerHTML = `<p class="text-white text-center text-xs">Processing file...</p>`;

    html5QrCode.scanFile(file, true)
        .then(decodedText => {
            onScanSuccess(decodedText);
        })
        .catch(err => {
            alert("No QR Code found in this image. Please ensure the code is clear.");
            resultContainer.innerHTML = "";
        });
});
</script>