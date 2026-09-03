<?php 
include 'db_config.php'; 
include 'header.php'; 

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Access control: Only Organizers (Role 3)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: login.php");
    exit();
}

$event_id = $_GET['event_id'] ?? null;
$user_id = $_SESSION['user_id'];

// Verify organizer owns this event
$check = $pdo->prepare("SELECT title FROM events WHERE event_id = ? AND organizer_id = ?");
$check->execute([$event_id, $user_id]);
$event = $check->fetch();

if (!$event) {
    die("<div class='p-20 text-center text-white font-black'>ACCESS DENIED OR EVENT NOT FOUND</div>");
}

// Fetch attendees - This was the line causing your error
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE event_id = ? ORDER BY created_at DESC");
$stmt->execute([$event_id]);
$attendees = $stmt->fetchAll();
?>

<div class="p-8 max-w-6xl mx-auto min-h-screen">
    <div class="mb-8">
        <a href="manage_events.php" class="inline-flex items-center gap-2 px-5 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-white text-xs font-bold uppercase transition group">
            <span class="group-hover:-translate-x-1 transition-transform">←</span> 
            Back to Command
        </a>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-end gap-4 mb-10">
        <div>
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase">Guest <span class="text-blue-500">List</span></h1>
            <p class="text-gray-400 text-sm mt-1 font-medium italic"><?php echo htmlspecialchars($event['title']); ?></p>
        </div>
        <div class="bg-blue-500/10 border border-blue-500/20 px-6 py-3 rounded-2xl">
            <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Total Guests</p>
            <p class="text-2xl font-black text-white"><?php echo count($attendees); ?></p>
        </div>
    </div>

    <div class="glass rounded-[40px] border border-white/10 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/5 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">
                    <th class="p-8">Customer Detail</th>
                    <th class="p-8">Ticket ID</th>
                    <th class="p-8">Status</th>
                    <th class="p-8 text-right">Purchase Date</th>
                </tr>
            </thead>
            <tbody class="text-white text-sm">
                <?php if ($attendees): foreach ($attendees as $row): ?>
                <tr class="border-t border-white/5 hover:bg-white/5 transition-colors group">
                    <td class="p-8">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center font-black text-xs">
                                <?php echo strtoupper(substr($row['customer_name'] ?? 'G', 0, 1)); ?>
                            </div>
                            <span class="font-bold"><?php echo htmlspecialchars($row['customer_name'] ?? 'Guest User'); ?></span>
                        </div>
                    </td>
                    <td class="p-8 font-mono text-gray-400 group-hover:text-blue-400 transition-colors">
                        <?php echo $row['ticket_code']; ?>
                    </td>
                    <td class="p-8">
                        <span class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest <?php echo $row['status'] == 'used' ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-blue-500/20 text-blue-400 border border-blue-500/30'; ?>">
                            <?php echo $row['status'] == 'used' ? 'Scanned' : 'Active'; ?>
                        </span>
                    </td>
                    <td class="p-8 text-right text-gray-500 font-medium">
                        <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="4" class="p-20 text-center text-gray-600 italic uppercase tracking-widest text-xs font-black">
                        No tickets have been sold for this event yet.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>