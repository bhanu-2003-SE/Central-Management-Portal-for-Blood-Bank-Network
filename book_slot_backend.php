<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db.php';

// Login වෙලා නැත්නම් login පිටුවට යවනවා
if (!isset($_SESSION['donor_id']) || empty($_SESSION['donor_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donor_id = $_SESSION['donor_id'];
    $camp_id = $_POST['camp_id'] ?? 1;
    $time_slot = $_POST['timeSlot'] ?? '08:30 AM - 09:30 AM';
    
    // අද්විතීය පාස් කෝඩ් එකක් ජනනය කිරීම (Unique Pass Code)
    $pass_code = 'LLC-' . date('Y') . '-' . rand(1000, 9999);
    $status = 'Confirmed'; // බුක් කළ වගෙන්ම කන්ෆර්ම් වීම

    try {
        // Oracle ඩේටාබේස් එකේ camp_appointments ටේබල් එකට දත්ත ඇතුළත් කිරීම
        $stmt = $conn->prepare("INSERT INTO camp_appointments (donor_id, camp_id, time_slot, pass_code, status) 
                                VALUES (:donor_id, :camp_id, :time_slot, :pass_code, :status)");
        
        $stmt->execute([
            ':donor_id' => $donor_id,
            ':camp_id' => $camp_id,
            ':time_slot' => $time_slot,
            ':pass_code' => $pass_code,
            ':status' => $status
        ]);

        // සාර්ථකව බුක් වූ පසු පාස් කෝඩ් එක සමඟ නැවත camp-register.php වෙතම යැවීම
        header("Location: camp-register.php?camp_id=" . $camp_id . "&booking=success&code=" . $pass_code);
        exit();

    } catch (PDOException $e) {
        die("Booking Error: " . $e->getMessage());
    }
} else {
    header("Location: user-dashboard.php");
    exit();
}
?>