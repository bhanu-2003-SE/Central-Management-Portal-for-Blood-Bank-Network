<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $donor_id = $_SESSION['donor_id'] ?? 'D-8821'; 
    $camp_name = $_POST['camp_name'] ?? '';
    $rating = (int) ($_POST['rating'] ?? 5);
    $review = $_POST['review'] ?? '';

    try {
        // `$mongoCollection` එක කෙළින්ම භාවිතා කිරීම (db.php එකේ සාදා ඇත)
        if (isset($mongoCollection)) {
            $mongoCollection->insertOne([
                'donor_id' => $donor_id,
                'camp_name' => $camp_name,
                'rating' => $rating,
                'review' => $review,
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ]);
            
            header("Location: user-dashboard.php?feedback=success");
            exit();
        } else {
            die("Error: MongoDB Connection object ($ mongoCollection) is not initialized. Check your vendor/autoload.php or MongoDB service.");
        }

    } catch (Exception $e) {
        die("MongoDB Insert Failed: " . $e->getMessage());
    }
}
?>