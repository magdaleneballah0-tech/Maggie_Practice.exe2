<?php
include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role_id = $_POST['role_id']; // 2 for Attendee, 3 for Organizer
    
    // 1. Hash the password correctly
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // 2. Set verification status (Admins/Attendees = 1, Organizers = 0)
    $is_verified = ($role_id == 3) ? 0 : 1;

    try {
        // We include a dummy phone value or null to avoid the "Duplicate Entry" error you saw
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role_id, is_verified, phone) VALUES (?, ?, ?, ?, ?, NULL)");
        
        if ($stmt->execute([$full_name, $email, $hashed_password, $role_id, $is_verified])) {
            header("Location: login.php?success=Account created! Please login.");
            exit();
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            header("Location: register.php?error=Email already in use.");
        } else {
            header("Location: register.php?error=Database Error: " . $e->getMessage());
        }
        exit();
    }
}