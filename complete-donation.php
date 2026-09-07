<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db.php';

// ඇමින් කෙනෙක් දැයි පරීක්ෂා කිරීම
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['appointment_id']) && isset($_GET['donor_id']) && isset($_GET['camp_id'])) {
    $appointment_id = $_GET['appointment_id'];
    $donor_id = $_GET['donor_id'];
    $camp_id = $_GET['camp_id'];

    try {
        $conn->beginTransaction();

        // 1. අපොයින්ට්මන්ට් එකේ ස්ටේටස් එක 'Completed' ලෙස වෙනස් කිරීම
        $stmtUpdate = $conn->prepare("UPDATE camp_appointments SET status = 'Completed' WHERE appointment_id = :app_id");
        $stmtUpdate->execute([':app_id' => $appointment_id]);

        // 2. ඩොනර්ගේ රුධිර කාණ්ඩය (Blood Group) ලබා ගැනීම
        $stmtDonor = $conn->prepare("SELECT blood_group FROM donors WHERE donor_id = :donor_id");
        $stmtDonor->execute([':donor_id' => $donor_id]);
        $donor = $stmtDonor->fetch(PDO::FETCH_ASSOC);

        if ($donor) {
            // අකුරු uppercase කර whitespace ඉවත් කර ගැනීම (උදා: a+ -> A+)
            $blood_group = strtoupper(trim($donor['BLOOD_GROUP'] ?? $donor['blood_group'] ?? ''));

            if (!empty($blood_group)) {
                // 3. Central Blood Stock එකේ අදාළ රුධිර කාණ්ඩය පවතියිදැයි පරීක්ෂා කිරීම
                $stmtStockCheck = $conn->prepare("SELECT total_units FROM blood_stock WHERE UPPER(blood_group) = :bg");
                $stmtStockCheck->execute([':bg' => $blood_group]);
                $stockExists = $stmtStockCheck->fetch(PDO::FETCH_ASSOC);

                if ($stockExists) {
                    // ස්ටොක් එක තිබේ නම් පින්ට් 1කින් වැඩි කිරීම
                    $stmtStockUpdate = $conn->prepare("UPDATE blood_stock SET total_units = total_units + 1 WHERE UPPER(blood_group) = :bg");
                    $stmtStockUpdate->execute([':bg' => $blood_group]);
                } else {
                    // ස්ටොක් එකේ නොමැති නම් අලුතින් රුධිර කාණ්ඩය ඇතුළත් කර පින්ට් 1ක් දැමීම
                    $stmtStockInsert = $conn->prepare("INSERT INTO blood_stock (blood_group, total_units) VALUES (:bg, 1)");
                    $stmtStockInsert->execute([':bg' => $blood_group]);
                }
            }
        }

        $conn->commit();
        
        // සාර්ථකව අවසන් වී නැවත අදාළ කැම්ප් එකේ බුකින්ස් පේජ් එකටම පැමිණීම
        header("Location: view-camp-donors.php?camp_id=" . $camp_id . "&success=donated");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        die("Error completing donation: " . $e->getMessage());
    }
} else {
    header("Location: admin-dashboard.php");
    exit();
}
?>