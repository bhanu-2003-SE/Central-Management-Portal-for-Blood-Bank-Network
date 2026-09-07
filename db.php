<?php
// Oracle Database Connection using PDO
$db_username = "system";
$db_password = "admin";
$db_connection = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=XE)))";

try {
    $conn = new PDO("oci:dbname=" . $db_connection, $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Oracle Connection Failed: " . $e->getMessage());
}

// MongoDB Connection with proper initialization
$mongoClient = null;
$mongoCollection = null;

try {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
        // MongoDB Client එක නිර්මාණය කිරීම
        $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
        // Blood_Bank ඩේටාබේස් එක සහ feedback කલેක්ෂන් එක නිවැරදිව ලබා දීම
        $mongoCollection = $mongoClient->Blood_Bank->feedback;
    }
} catch (Exception $e) {
    $mongoClient = null;
    $mongoCollection = null;
}
?>