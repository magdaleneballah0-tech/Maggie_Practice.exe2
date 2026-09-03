<?php
include 'db_config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['proof'])) {
    $ref = $_POST['ref'];
    $target_dir = "uploads/proofs/";
    
    // Create unique filename to avoid overwriting
    $file_extension = pathinfo($_FILES["proof"]["name"], PATHINFO_EXTENSION);
    $new_filename = "PROOF_" . $ref . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Check if file is an actual image
    $check = getimagesize($_FILES["proof"]["tmp_name"]);
    if($check === false) {
        die("Error: File is not an image.");
    }

    // Move the file to the folder
    if (move_uploaded_file($_FILES["proof"]["tmp_name"], $target_file)) {
        try {
            // Update the payment record with the image path
            $stmt = $pdo->prepare("UPDATE payments SET proof_image = ? WHERE reference = ?");
            $stmt->execute([$new_filename, $ref]);

            // Success! Send them back to the dashboard with a success message
            header("Location: dashboard.php?msg=upload_success");
            exit();
            
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    } else {
        die("Error: There was an issue uploading your file. Check folder permissions.");
    }
} else {
    header("Location: dashboard.php");
    exit();
}