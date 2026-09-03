<?php
include 'db_config.php';

// Fix: Prevent "Ignoring session_start()" notice
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ticket_code'])) {
    $ticket_code = trim($_POST['ticket_code']);
    
    if (!isset($_SESSION['user_id'])) {
        echo "<div class='p-6 bg-red-600 rounded-3xl text-center text-white'>SESSION EXPIRED</div>";
        exit();
    }

    $organizer_id = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("
            SELECT t.*, e.title, e.organizer_id 
            FROM tickets t 
            JOIN events e ON t.event_id = e.event_id 
            WHERE t.ticket_code = ?
        ");
        $stmt->execute([$ticket_code]);
        $ticket = $stmt->fetch();

        if ($ticket) {
            if ($ticket['organizer_id'] != $organizer_id) {
                echo "<div class='p-6 bg-yellow-600 rounded-3xl text-center text-white'>WRONG EVENT</div>";
            } 
            elseif (strtolower(trim($ticket['status'])) === 'used') {
                // Uses the scanned_at column confirmed in your DB
                $scan_time = $ticket['scanned_at'] ? date('H:i', strtotime($ticket['scanned_at'])) : "Earlier";
                echo "<div class='p-6 bg-red-600 border-2 border-red-400 rounded-3xl text-center text-white'>
                        <h2 class='text-2xl font-black italic'>ALREADY USED</h2>
                        <p class='text-xs font-bold uppercase tracking-widest'>Previously scanned at $scan_time</p>
                      </div>";
            } 
            else {
                // Update status to 'used' and set scanned_at timestamp
                $update = $pdo->prepare("UPDATE tickets SET status = 'used', scanned_at = NOW() WHERE ticket_id = ?");
                $update->execute([$ticket['ticket_id']]);

                echo "<div class='p-6 bg-green-600 border-2 border-green-400 rounded-3xl text-center text-white'>
                        <h2 class='text-2xl font-black italic'>ACCESS GRANTED</h2>
                        <p class='text-xs font-bold uppercase tracking-widest'>" . htmlspecialchars($ticket['title']) . "</p>
                      </div>";
            }
        } else {
            echo "<div class='p-6 bg-gray-800 rounded-3xl text-center text-white font-black'>INVALID TICKET</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='text-white p-4'>Error: " . $e->getMessage() . "</div>";
    }
}
?>