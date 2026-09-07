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

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $alert_id = trim($_GET['id']);
    
    try {
        if (isset($mongoClient)) {
            $collection = $mongoClient->Blood_Bank->emergency_appeals;
            
            // MongoDB ID එක මඟින් අදාළ රෙකෝඩ් එක ඉවත් කිරීම
            $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($alert_id)]);
        }
    } catch (Exception $e) {
        // දෝෂයක් මතු වුවහොත් ලොග් කිරීම හෝ නොසලකා හැරීම
    }
}

// නැවත ඇමින් ඩෑෂ්බෝඩ් එකේ Emergency Alerts ටැබ් එක වෙතම හරවා යැවීම
header("Location: admin-dashboard.php?tab=emergency");
exit();
?>