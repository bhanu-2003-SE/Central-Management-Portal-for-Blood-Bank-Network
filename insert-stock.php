<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $blood_group = trim($_POST['blood_group'] ?? '');
    $units = (int) ($_POST['units'] ?? 0);

    if (!empty($blood_group)) {
        try {
            // පළමුව මෙම බ්ලඩ් ගෲප් එක දැනටමත් ඇද්දැයි පරීක්ෂා කර බැලීම
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM blood_stock WHERE blood_group = :b_group");
            $checkStmt->execute([':b_group' => $blood_group]);
            $exists = $checkStmt->fetchColumn();

            if ($exists > 0) {
                // තිබෙනවා නම් Update කිරීම
                $stmt = $conn->prepare("UPDATE blood_stock SET total_units = :units WHERE blood_group = :b_group");
                $stmt->execute([':units' => $units, ':b_group' => $blood_group]);
            } else {
                // නැත්නම් අලුතින් Insert කිරීම
                $stmt = $conn->prepare("INSERT INTO blood_stock (blood_group, total_units) VALUES (:b_group, :units)");
                $stmt->execute([':b_group' => $blood_group, ':units' => $units]);
            }

            header("Location: admin-dashboard.php?stock=inserted");
            exit();
        } catch (Exception $e) {
            header("Location: admin-dashboard.php?stock=error");
            exit();
        }
    }
}
?>