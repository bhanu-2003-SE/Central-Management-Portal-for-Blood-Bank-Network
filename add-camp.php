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

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $camp_name = trim($_POST['camp_name'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');

    if (!empty($camp_name) && !empty($venue) && !empty($start_date) && !empty($end_date) && !empty($latitude) && !empty($longitude)) {
        try {
            // ඩේටාබේස් ටේබල් එකේ start_date සහ end_date කالمස් තිබිය යුතුය
            $stmt = $conn->prepare("INSERT INTO camps (camp_name, venue, start_date, end_date, latitude, longitude) VALUES (:camp_name, :venue, :start_date, :end_date, :latitude, :longitude)");
            $stmt->execute([
                ':camp_name' => $camp_name,
                ':venue' => $venue,
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':latitude' => $latitude,
                ':longitude' => $longitude
            ]);

            header("Location: admin-dashboard.php?success=camp_added");
            exit();
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
        }
    } else {
        $message = "කරුණාකර ආරම්භක දිනය, අවසාන දිනය සහ අනෙකුත් සියලුම විස්තර නිවැරදිව පුරවන්න!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organize New Blood Camp - LifeLine Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f0f4f8; background-image: radial-gradient(at 0% 0%, hsla(359, 100%, 74%, 0.15) 0px, transparent 50%), radial-gradient(at 100% 0%, hsla(204, 100%, 74%, 0.15) 0px, transparent 50%); color: #2b3674; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        
        .form-card { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); width: 100%; max-width: 550px; }
        .form-header { font-size: 22px; font-weight: 800; color: #2b3674; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
        .text-gradient { background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: #475569; }
        .form-group input { width: 100%; padding: 14px; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 14px; transition: all 0.3s; }
        .form-group input:focus { border-color: #ff4b2b; }
        
        .row-group { display: flex; gap: 15px; }
        .row-group .form-group { flex: 1; }

        .btn-submit { background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); color: white; border: none; width: 100%; padding: 14px; border-radius: 12px; font-weight: 700; font-size: 16px; cursor: pointer; transition: all 0.3s; margin-top: 10px; }
        .btn-submit:hover { opacity: 0.9; transform: translateY(-2px); }
        
        .back-link { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #64748b; font-weight: 600; font-size: 14px; }
        .back-link:hover { color: #FF416C; }
        
        .alert-error { background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 12px; border-radius: 10px; font-size: 13px; font-weight: 600; margin-bottom: 20px; }
        .hint-text { font-size: 12px; color: #64748b; margin-top: 4px; }
    </style>
</head>
<body>

    <div class="form-card">
        <div class="form-header">
            <span class="text-gradient">⛺</span> Schedule New Blood Donation Camp
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="add-camp.php" method="POST">
            <div class="form-group">
                <label>Camp Name</label>
                <input type="text" name="camp_name" placeholder="e.g. Matara Youth Blood Donation Drive" required>
            </div>

            <div class="form-group">
                <label>Location / Venue Address</label>
                <input type="text" name="venue" placeholder="e.g. Town Hall, Matara" required>
            </div>

            <div class="row-group">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" required>
                </div>
            </div>

            <div class="row-group">
                <div class="form-group">
                    <label>Latitude</label>
                    <input type="text" name="latitude" placeholder="e.g. 5.9549" required>
                    <div class="hint-text">Map Latitude coordinate</div>
                </div>
                <div class="form-group">
                    <label>Longitude</label>
                    <input type="text" name="longitude" placeholder="e.g. 80.5550" required>
                    <div class="hint-text">Map Longitude coordinate</div>
                </div>
            </div>

            <button type="submit" class="btn-submit">Schedule & Update Map</button>
        </form>

        <a href="admin-dashboard.php" class="back-link">&larr; Back to Admin Dashboard</a>
    </div>

</body>
</html>