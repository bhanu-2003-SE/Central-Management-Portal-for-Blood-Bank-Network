<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_name = trim($_POST['staff_name']);
    $role = trim($_POST['role']);
    $assigned_camp = trim($_POST['assigned_camp']);
    $shift_time = trim($_POST['shift_time']);
    $contact_number = trim($_POST['contact_number'] ?? '');

    try {
        $stmt = $conn->prepare("INSERT INTO staff_assignments (staff_name, role, assigned_camp, shift_time, contact_number, status) VALUES (:s_name, :role, :camp, :shift, :contact, 'Active')");
        $stmt->execute([
            ':s_name' => $staff_name,
            ':role' => $role,
            ':camp' => $assigned_camp,
            ':shift' => $shift_time,
            ':contact' => $contact_number
        ]);
        header("Location: admin-dashboard.php");
        exit();
    } catch (PDOException $e) {
        die("Error adding staff: " . $e->getMessage());
    }
}
?>