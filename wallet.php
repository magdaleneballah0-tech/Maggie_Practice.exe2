<?php
include 'db_config.php';
include 'header.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all tickets for this user, joining with events to get the title and date
$stmt = $pdo->prepare("
    SELECT t.*, e.title, e.event_date, e.location 
    FROM tickets t 
    JOIN events e ON t.event_id = e.event_id 
    WHERE t.user_id = ? 
    ORDER BY t.purchase_date DESC
");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll();
?>

<div class="min-h-screen p-6 bg-[#0a0a0a]">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-4xl font-black text-white uppercase italic mb-8">My <span class="text-purple-500">Ticket Wallet</span></h2>

        <?php if (empty($tickets)): ?>
            <div class="glass p-10 rounded-[30px] text-center border border-white/5">
                <p class="text-gray-400">You don't have any tickets yet.</p>
                <a href="browse_events.php" class="mt-4 inline-block text-purple-500 font-bold uppercase tracking-widest">Browse Events -></a>
            </div>
        <?php else: ?>
            <div class="grid gap-6">
                <?php foreach ($tickets as $ticket): ?>
                    <div class="glass p-6 rounded-[30px] border border-white/10 flex flex-col md:flex-row justify-between items-center gap-6">
                        
                        <div>
                            <span class="text-[10px] font-black text-purple-500 uppercase tracking-widest">Event Ticket</span>
                            <h3 class="text-2xl font-black text-white uppercase italic"><?php echo htmlspecialchars($ticket['title']); ?></h3>
                            <div class="flex gap-4 mt-2 text-xs text-gray-400 font-bold uppercase">
                                <span>📅 <?php echo date('M d, Y', strtotime($ticket['event_date'])); ?></span>
                                <span>📍 <?php echo htmlspecialchars($ticket['location']); ?></span>
                            </div>
                        </div>

                        <div class="w-full md:w-auto">
                            <?php if ($ticket['status'] === 'active'): ?>
                                <a href="view_ticket.php?id=<?php echo $ticket['ticket_id']; ?>" 
                                   class="block text-center bg-purple-600 hover:bg-purple-500 text-white font-black py-3 px-8 rounded-2xl uppercase tracking-tighter transition-all shadow-lg shadow-purple-600/20">
                                   View QR Code
                                </a>
                            <?php else: ?>
                                <div class="bg-white/5 border border-white/10 text-gray-500 font-black py-3 px-8 rounded-2xl uppercase tracking-tighter italic flex items-center gap-2 justify-center">
                                    <span class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></span>
                                    Verifying Payment
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>