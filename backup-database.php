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

$backupData = [
    'system_name' => 'LifeLine Connect',
    'backup_date' => date('Y-m-d H:i:s'),
    'oracle_tables' => [],
    'mongodb_collections' => []
];

try {
    // 1. Oracle / MySQL Database දත්ත ලබා ගැනීම
    $tables = ['blood_stock', 'hospital_requests', 'hospitals', 'camps', 'donors', 'camp_appointments', 'staff_assignments'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $conn->prepare("SELECT * FROM {$table}");
            $stmt->execute();
            $backupData['oracle_tables'][$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $backupData['oracle_tables'][$table] = "Error fetching table: " . $e->getMessage();
        }
    }

    // 2. MongoDB දත්ත ලබා ගැනීම
    if (isset($mongoClient)) {
        $db = $mongoClient->Blood_Bank;
        $collections = ['emergency_appeals', 'feedback'];
        
        foreach ($collections as $col) {
            try {
                $cursor = $db->{$col}->find([], ['sort' => ['_id' => -1]]);
                $documents = [];
                foreach ($cursor as $doc) {
                    // MongoDB BSON ObjectId සහ Dates JSON වලට හැරවීම සඳහා සකස් කිරීම
                    $docArray = (array) $doc;
                    if (isset($docArray['_id'])) {
                        $docArray['_id'] = (string) $docArray['_id'];
                    }
                    $documents[] = $docArray;
                }
                $backupData['mongodb_collections'][$col] = $documents;
            } catch (Exception $e) {
                $backupData['mongodb_collections'][$col] = "Error fetching collection: " . $e->getMessage();
            }
        }
    }

    // JSON ෆයිල් එකක් ලෙස ඩවුන්ලෝඩ් කිරීමට යැවීම
    $filename = "lifeline_connect_backup_" . date('Y-m-d_H-i-s') . ".json";
    
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();

} catch (Exception $e) {
    die("Backup Generation Failed: " . $e->getMessage());
}
?>