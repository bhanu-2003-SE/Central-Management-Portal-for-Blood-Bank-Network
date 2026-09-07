<?php
session_start();
require_once 'db.php';

// Security Check: ඇමින් කෙනෙක් ලෙස ලොග් වී ඇත්දැයි බැලීම
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";
$statusType = "";

if (isset($_GET['id']) && isset($_GET['action'])) {
    $hospitalId = $_GET['id'];
    $action = $_GET['action'];

    // ඇෂන් එක අනුව ස්ටේටස් එක තීරණය කිරීම ('Approved' හෝ 'Rejected')
    $newStatus = ($action === 'approve') ? 'Approved' : 'Rejected';

    try {
        // hospitals ටේබල් එකේ අදාළ රෝහලේ ස්ටේටස් එක අප්ඩේට් කිරීම
        $stmt = $conn->prepare("UPDATE hospitals SET status = :status WHERE hospital_id = :id");
        $stmt->execute([
            ':status' => $newStatus,
            ':id' => $hospitalId
        ]);

        $message = "Hospital registration has been successfully " . strtolower($newStatus) . "!";
        $statusType = "success";

    } catch (Exception $e) {
        $message = "Error updating hospital status: " . $e->getMessage();
        $statusType = "error";
    }
} else {
    header("Location: admin-dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Approval Action - LifeLine Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f0f4f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); text-align: center; max-width: 450px; width: 100%; }
        .icon { font-size: 50px; margin-bottom: 20px; }
        h2 { color: #2b3674; margin-bottom: 12px; font-weight: 800; }
        p { color: #64748b; font-size: 15px; margin-bottom: 25px; line-height: 1.5; }
        .btn { background: linear-gradient(135deg, #36D1DC 0%, #5B86E5 100%); color: white; border: none; padding: 12px 24px; border-radius: 10px; cursor: pointer; font-weight: 700; text-decoration: none; display: inline-block; transition: 0.3s; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><?php echo ($statusType === 'success') ? '✅' : '❌'; ?></div>
        <h2><?php echo ($statusType === 'success') ? 'Action Successful!' : 'Action Failed'; ?></h2>
        <p><?php echo htmlspecialchars($message); ?></p>
        <a href="admin-dashboard.php" class="btn">Return to Dashboard</a>
    </div>
</body>
</html>