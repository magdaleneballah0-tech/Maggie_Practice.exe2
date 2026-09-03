<?php
include 'db_config.php';
session_start();

// SECURITY: Only YOU (Admin Role 1, User ID 1) can run this logic
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1 || $_SESSION['user_id'] != 1) {
    die("Unauthorized Access");
}

$user_id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($user_id && $action == 'approve') {
    // 1. Update the user to be verified
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // 2. Optional: Create a notification for the organizer
    $msg = "System Alert: Your Organizer account has been verified. You can now post events!";
    $notif = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $notif->execute([$user_id, $msg]);

    // 3. Send back to the dashboard with a success message
    header("Location: admin_dashboard.php?verified=success");
    exit();
}

// If something went wrong, just go back
header("Location: admin_dashboard.php");
exit();