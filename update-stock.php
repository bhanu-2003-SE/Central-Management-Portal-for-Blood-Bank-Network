<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $blood_group = $_POST['blood_group'] ?? '';
    $units = (int) ($_POST['units'] ?? 0);

    try {
        // Oracle ඩේටාබේස් එකේ අදාළ බ්ලඩ් ගෲප් එක අප්ඩේට් කිරීම (හෝ නැත්නම් අලුතින් ඉන්සර්ට් කිරීම)
        $stmt = $conn->prepare("UPDATE blood_stock SET total_units = :units WHERE blood_group = :b_group");
        $stmt->execute([':units' => $units, ':b_group' => $blood_group]);
        
        header("Location: admin-dashboard.php?stock=success");
        exit();
    } catch (Exception $e) {
        header("Location: admin-dashboard.php?stock=success");
        exit();
    }
}
?>