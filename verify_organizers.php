<?php
include 'db_config.php';
include 'header.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Security: Admin Only
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}

/**
 * HELPER: Send Email & SMS Notifications
 */
function sendVendorNotification($email, $phone, $name, $status) {
    if ($status === 'approved') {
        // --- 1. EMAIL NOTIFICATION ---
        $subject = "ACCESS GRANTED: PartyPass SL Organizer";
        $msg = "Hello $name,\n\nYour organizer account has been VERIFIED. You can now log in to the Nexus and start creating events.\n\nWelcome to the team!";
        $headers = "From: noreply@partypasssl.com";
        mail($email, $subject, $msg, $headers);

        // --- 2. SMS NOTIFICATION (Sierra Leone Gateway Config) ---
        if (!empty($phone)) {
            // Clean the phone number (removes spaces/dashes)
            $clean_phone = preg_replace('/[^0-9]/', '', $phone);
            
            // If number starts with 0, replace with 232 for SL international format
            if (substr($clean_phone, 0, 1) === '0') {
                $clean_phone = '232' . substr($clean_phone, 1);
            }

            $sms_text = "Congrats $name! Your PartyPass SL vendor account is now ACTIVE. Log in now to start hosting your events.";
            
            // API Configuration - Replace these with your provider's details
            $api_key = "YOUR_API_KEY_HERE"; 
            $sender_id = "PartyPass"; 
            
            // Example URL structure for most SL gateways
            $sms_url = "https://api.yourprovider.com/send?apikey=" . urlencode($api_key) . 
                       "&to=" . $clean_phone . 
                       "&msg=" . urlencode($sms_text) . 
                       "&sender=" . urlencode($sender_id);

            // Execute the SMS send request
            @file_get_contents($sms_url);
        }
    }
}

// Handle Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $target_id = $_GET['id'];
    
    $stmt_vendor = $pdo->prepare("SELECT full_name, email, phone FROM users WHERE user_id = ?");
    $stmt_vendor->execute([$target_id]);
    $vendor = $stmt_vendor->fetch();

    if ($_GET['action'] === 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ? AND role_id = 3");
        if ($stmt->execute([$target_id]) && $vendor) {
            sendVendorNotification($vendor['email'], $vendor['phone'], $vendor['full_name'], 'approved');
        }
    } elseif ($_GET['action'] === 'reject') {
        $stmt = $pdo->prepare("UPDATE users SET role_id = 2, is_verified = 0 WHERE user_id = ?");
        $stmt->execute([$target_id]);
    }
    
    header("Location: verify_organizers.php?success=1");
    exit();
}

$stmt = $pdo->query("SELECT user_id, full_name, email, phone, created_at FROM users WHERE role_id = 3 AND is_verified = 0 ORDER BY created_at DESC");
$pending = $stmt->fetchAll();
?>

<div class="min-h-screen relative" style="background: url('bg.jpg') no-repeat center center fixed; background-size: cover;">
    <div class="absolute inset-0 bg-black/80 z-0"></div>

    <div class="relative z-10 p-8 max-w-5xl mx-auto">
        <div class="mb-10 flex justify-between items-end">
            <div>
                <a href="admin_dashboard.php" class="text-gray-500 hover:text-white text-[10px] font-black uppercase tracking-widest transition">← Back to Nexus</a>
                <h1 class="text-4xl font-black text-white uppercase italic tracking-tighter mt-4">Vendor <span class="text-purple-500">Approvals</span></h1>
                <p class="text-gray-500 text-[10px] font-black uppercase tracking-[0.3em]">Manual Identity & Permission Control</p>
            </div>
            <?php if(isset($_GET['success'])): ?>
                <div class="bg-green-500/20 border border-green-500/50 text-green-500 px-6 py-3 rounded-2xl text-[10px] font-black uppercase animate-bounce shadow-lg shadow-green-500/20">
                    ✨ Notifications Dispatched!
                </div>
            <?php endif; ?>
        </div>

        <?php if (count($pending) > 0): ?>
            <div class="grid gap-4">
                <?php foreach ($pending as $org): ?>
                    <div class="glass p-6 rounded-[2rem] border border-white/5 flex flex-col md:flex-row justify-between items-center gap-6 group hover:border-purple-500/30 transition-all duration-500">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-purple-500/10 rounded-2xl flex items-center justify-center text-2xl border border-purple-500/20 shadow-inner">
                                👤
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-white uppercase italic"><?php echo htmlspecialchars($org['full_name']); ?></h3>
                                <p class="text-purple-400 text-[10px] font-black uppercase tracking-widest">ID: #<?php echo $org['user_id']; ?></p>
                                <div class="flex gap-4 mt-2">
                                    <p class="text-gray-400 text-[10px] font-bold">📧 <?php echo htmlspecialchars($org['email']); ?></p>
                                    <p class="text-gray-400 text-[10px] font-bold">📱 <span class="text-white"><?php echo htmlspecialchars($org['phone'] ?? 'No Phone'); ?></span></p>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-3 w-full md:w-auto">
                            <a href="?action=reject&id=<?php echo $org['user_id']; ?>" 
                               onclick="return confirm('Rejecting will demote this user to Attendee status. Continue?')"
                               class="flex-1 md:flex-none text-center px-8 py-4 bg-red-500/10 border border-red-500/20 text-red-500 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all">
                                Reject
                            </a>
                            
                            <a href="?action=approve&id=<?php echo $org['user_id']; ?>" 
                               class="flex-1 md:flex-none text-center px-8 py-4 bg-green-500 text-black rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-green-400 transition-all shadow-lg shadow-green-500/20">
                                Verify & Notify
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="glass p-20 rounded-[3rem] text-center border border-white/5">
                <div class="text-5xl mb-6 opacity-20">✅</div>
                <h2 class="text-2xl font-black text-white uppercase italic">Queue Clear</h2>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mt-2">All organizers are verified.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
</style>