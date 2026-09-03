<?php
include 'db_config.php'; // This file creates the $pdo connection
include 'header.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. FIXED: Use $pdo instead of $conn
$stmt = $pdo->prepare("SELECT available_balance, is_verified FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Security Check: Only verified organizers
if (!$user['is_verified']) {
    echo "<div class='p-8 text-white'>Access Denied. You must be verified to withdraw funds.</div>";
    exit();
}

$balance = $user['available_balance'] ?? 0;
?>

<div class="p-8 max-w-4xl mx-auto">
    <div class="glass p-8 rounded-[40px] border border-white/10">
        <h1 class="text-3xl font-black text-white uppercase italic mb-2">Withdraw <span class="text-green-500">Funds</span></h1>
        <p class="text-gray-400 mb-8">Securely transfer your event earnings to your account.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-white/5 p-6 rounded-3xl border border-white/5">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Available Balance</p>
                <h2 class="text-4xl font-black text-white italic"><?php echo number_format($balance, 2); ?> <span class="text-sm text-gray-500">SLE</span></h2>
            </div>
            
            <form action="process_withdrawal.php" method="POST" class="space-y-4">
                <div>
                    <label class="text-[10px] font-black text-purple-400 uppercase tracking-widest ml-2">Amount to Withdraw</label>
                    <input type="number" name="amount" step="0.01" max="<?php echo $balance; ?>" required 
                           class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-green-500 transition-all"
                           placeholder="0.00">
                </div>
                
                <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-black font-black uppercase py-4 rounded-2xl transition-all duration-300 transform hover:scale-[1.02]">
                    Request Payout
                </button>
            </form>
        </div>
        
        <div class="bg-yellow-500/5 border border-yellow-500/20 p-6 rounded-3xl">
            <p class="text-xs text-yellow-500 leading-relaxed">
                <strong>Note:</strong> Withdrawals are processed within 24-48 hours. Please ensure your bank/mobile money details in your <a href="profile.php" class="underline">profile</a> are up to date.
            </p>
        </div>
    </div>
</div>