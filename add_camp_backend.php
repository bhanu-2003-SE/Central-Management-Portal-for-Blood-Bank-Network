<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db.php';

// ඇමින් කෙනෙක් පමණක්දැයි පරීක්ෂා කිරීම
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $camp_name = $_POST['camp_name'];
    $venue = $_POST['venue'];
    $camp_date = $_POST['camp_date'];

    try {
        $sql = "INSERT INTO camps (camp_name, venue, camp_date) VALUES (:c_name, :venue, TO_DATE(:c_date, 'YYYY-MM-DD'))";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':c_name'  => $camp_name,
            ':venue'   => $venue,
            ':c_date'  => $camp_date
        ]);

        header("Location: admin-dashboard.php?success=camp_added");
        exit();
    } catch (PDOException $e) {
        die("Error adding camp: " . $e->getMessage());
    }
} else {
    header("Location: admin-dashboard.php");
    exit();
}
?>