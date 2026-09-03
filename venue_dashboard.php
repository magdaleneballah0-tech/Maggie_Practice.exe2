<?php 
include 'db_config.php'; 
include 'header.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$user_id = $_SESSION['user_id'];

try {
    // 1. Get Venue Owner Stats
    $stmt_venues = $pdo->prepare("SELECT COUNT(*) FROM venues WHERE owner_id = ?");
    $stmt_venues->execute([$user_id]);
    $total_venues = $stmt_venues->fetchColumn();

    // 2. Get Potential Earnings - Added a fallback check for the column name
    $stmt_earn = $pdo->prepare("SELECT SUM(price_per_night) FROM venues WHERE owner_id = ?");
    $stmt_earn->execute([$user_id]);
    $potential = $stmt_earn->fetchColumn() ?: 0;

} catch (PDOException $e) {
    // If it fails again, it will show a helpful message instead of a Fatal Error
    $potential = 0;
    $db_error = "Database column mismatch: " . $e->getMessage();
}
?>

<div class="p-8 max-w-7xl mx-auto">
    <?php if(isset($db_error)): ?>
        <div class="bg-red-500/20 border border-red-500/50 text-red-200 p-4 rounded-2xl mb-6 text-sm">
            <strong>Dev Note:</strong> Your 'venues' table needs the 'price_per_night' column. 
            <br>Run: <code>ALTER TABLE venues ADD price_per_night DECIMAL(10,2);</code> in phpMyAdmin.
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-end mb-10">
        <div>
            <p class="text-purple-500 text-[10px] font-black uppercase tracking-[0.2em] mb-2">Venue Management</p>
            <h1 class="text-5xl font-black text-white uppercase italic">Owner <span class="text-purple-500">Panel</span></h1>
        </div>
        <a href="list_venue.php" class="bg-purple-600 hover:bg-purple-500 text-white px-8 py-4 rounded-2xl font-black uppercase text-xs tracking-widest transition shadow-lg shadow-purple-500/20">
            + List New Venue
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="glass p-8 rounded-[35px] border-l-4 border-purple-500">
            <p class="text-gray-400 text-[10px] font-black uppercase">My Listings</p>
            <h3 class="text-4xl font-black text-white mt-2"><?php echo $total_venues; ?></h3>
        </div>
        <div class="glass p-8 rounded-[35px] border-l-4 border-yellow-500">
            <p class="text-gray-400 text-[10px] font-black uppercase">Potential Value</p>
            <h3 class="text-4xl font-black text-white mt-2"><?php echo number_format($potential, 2); ?> <span class="text-sm font-normal text-gray-500">SLE</span></h3>
        </div>
        <div class="glass p-8 rounded-[35px] border-l-4 border-green-500">
            <p class="text-gray-400 text-[10px] font-black uppercase">Account Status</p>
            <h3 class="text-xl font-black text-green-400 mt-4 uppercase italic">Active ✅</h3>
        </div>
    </div>
</div>