<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db.php';

// ඇමින් කෙනෙක් නොවේ නම් ලොගින් පිටුවට යැවීම
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// URL එකෙන් staff_id (id) එක ලැබී ඇත්දැයි පරීක්ෂා කිරීම
if (isset($_GET['id'])) {
    $staff_id = $_GET['id'];

    try {
        // ඩේටාබේස් එකෙන් අදාළ රෙකෝඩ් එක ඉවත් කිරීම
        $stmt = $conn->prepare("DELETE FROM staff_assignments WHERE staff_id = :id");
        $stmt->execute([':id' => $staff_id]);

        // සාර්ථකව ডിലීට් වූ පසු නැවත ඇමින් ඩෑෂ්බෝඩ් එකට යැවීම
        header("Location: admin-dashboard.php?success=deleted");
        exit();
    } catch (PDOException $e) {
        die("Error deleting record: " . $e->getMessage());
    }
} else {
    header("Location: admin-dashboard.php");
    exit();
}
?>