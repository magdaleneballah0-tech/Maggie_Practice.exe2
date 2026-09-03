<?php
include 'db_config.php';
include 'header.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ticket_code = $_GET['code'] ?? null; 

try {
    $stmt = $pdo->prepare("
        SELECT t.*, e.title, e.event_date, e.location 
        FROM tickets t 
        JOIN events e ON t.event_id = e.event_id 
        WHERE t.ticket_code = ? AND t.user_id = ?
    ");
    $stmt->execute([$ticket_code, $user_id]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        die("<div class='min-h-screen flex items-center justify-center text-white font-black uppercase italic'>Ticket Not Found</div>");
    }

    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($ticket['ticket_code']);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $qr_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $raw_image = curl_exec($ch);
    curl_close($ch);
    $qr_base64 = 'data:image/png;base64,' . base64_encode($raw_image);

} catch (Exception $e) {
    die("System Error: " . $e->getMessage());
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<div class="min-h-screen p-8 relative flex items-center justify-center" style="background: url('bg.jpg') no-repeat center center fixed; background-size: cover;">
    
    <div class="absolute inset-0 bg-black/60 z-0"></div>

    <div class="relative z-10 w-full max-w-md flex flex-col items-center">
        <div id="ticket-card" class="bg-white rounded-[50px] w-full max-w-[360px] overflow-hidden text-black shadow-2xl shadow-black/50">
            <div class="bg-purple-600 p-10 text-center text-white">
                <h1 class="text-2xl font-black italic uppercase tracking-tighter m-0">PartyPass SL</h1>
                <p class="text-[8px] font-black tracking-[5px] mt-2 opacity-90 uppercase">Official Entry Pass</p>
            </div>

            <div class="p-10 text-center">
                <h2 class="text-2xl font-black uppercase m-0 text-gray-900 tracking-tighter leading-none"><?php echo htmlspecialchars($ticket['title']); ?></h2>
                <p class="text-gray-500 text-xs font-bold mt-2 uppercase">📍 <?php echo htmlspecialchars($ticket['location']); ?></p>

                <div class="mt-4">
                    <span class="bg-purple-100 text-purple-600 px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest border border-purple-200">
                        <?php echo htmlspecialchars($ticket['ticket_type']); ?>
                    </span>
                </div>

                <div class="bg-white p-4 rounded-3xl inline-block my-6 border-2 border-gray-50">
                    <img src="<?php echo $qr_base64; ?>" class="w-44 h-44 block" alt="QR Code">
                </div>

                <div class="bg-gray-50 rounded-2xl p-4 mb-6 flex justify-between items-center border border-gray-100">
                    <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Price Paid</span>
                    <span class="text-lg font-black text-gray-900 italic">SLE <?php echo number_format($ticket['price_paid'], 2); ?></span>
                </div>

                <div class="flex justify-between text-left border-t-2 border-dashed border-gray-200 pt-6">
                    <div>
                        <p class="text-[8px] text-gray-400 font-black uppercase tracking-widest m-0">Date</p>
                        <p class="font-black text-sm text-gray-900 mt-1"><?php echo date('M d, Y', strtotime($ticket['event_date'])); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[8px] text-gray-400 font-black uppercase tracking-widest m-0">Code</p>
                        <p class="font-black text-sm text-purple-600 mt-1"><?php echo $ticket['ticket_code']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <button onclick="downloadTicket()" id="dl-btn" class="w-full max-w-[360px] mt-6 bg-purple-600 text-white font-black py-5 rounded-[25px] uppercase text-[11px] tracking-[3px] shadow-2xl hover:bg-purple-500 transition-all">
            Download PNG Pass
        </button>
        
        <a href="dashboard.php" class="mt-6 text-white text-[10px] font-black uppercase tracking-[2px] opacity-70 hover:opacity-100 transition">
            Back to Dashboard
        </a>
    </div>
</div>

<script>
function downloadTicket() {
    const card = document.getElementById('ticket-card');
    const btn = document.getElementById('dl-btn');
    btn.innerText = "Processing...";
    
    html2canvas(card, {
        scale: 4,
        useCORS: true,
        backgroundColor: "#ffffff",
        borderRadius: 50
    }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'PartyPass-<?php echo $ticket['ticket_code']; ?>.png';
        link.href = canvas.toDataURL("image/png");
        link.click();
        btn.innerText = "Download PNG Pass";
    });
}
</script>