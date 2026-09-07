<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['group'])) {
    $blood_group = $_GET['group'];

    try {
        $stmt = $conn->prepare("DELETE FROM blood_stock WHERE blood_group = :b_group");
        $stmt->execute([':b_group' => $blood_group]);
        
        header("Location: admin-dashboard.php?stock=deleted");
        exit();
    } catch (Exception $e) {
        header("Location: admin-dashboard.php?stock=deleted");
        exit();
    }
}
?>