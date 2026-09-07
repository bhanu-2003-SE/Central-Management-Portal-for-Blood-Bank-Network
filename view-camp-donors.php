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

$camp_id = $_GET['camp_id'] ?? 1;

// කැම්ප් එකේ විස්තර ලබා ගැනීම
$campStmt = $conn->prepare("SELECT * FROM camps WHERE camp_id = :camp_id");
$campStmt->execute([':camp_id' => $camp_id]);
$camp = $campStmt->fetch(PDO::FETCH_ASSOC);

$camp_name = $camp['CAMP_NAME'] ?? $camp['camp_name'] ?? 'Blood Drive';
$camp_venue = $camp['VENUE'] ?? $camp['venue'] ?? '';
$camp_date = $camp['START_DATE'] ?? $camp['start_date'] ?? $camp['CAMP_DATE'] ?? '';

// අදාළ කැම්ප් එකට බුක් කරපු ඩොනර්ස්ලාගේ ලැයිස්තුව ආරක්ෂිතව ලබා ගැනීම
$appointments = [];
try {
    $appStmt = $conn->prepare("SELECT * FROM camp_appointments WHERE camp_id = :camp_id ORDER BY appointment_id DESC");
    $appStmt->execute([':camp_id' => $camp_id]);
    $rawApps = $appStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rawApps as $app) {
        $dId = $app['DONOR_ID'] ?? $app['donor_id'] ?? '';
        
        $donorName = 'Unknown Donor';
        $donorNic = 'N/A';
        $donorGroup = 'N/A';
        $donorMob = 'N/A';

        if (!empty($dId)) {
            $donorStmt = $conn->prepare("SELECT * FROM donors WHERE donor_id = :did");
            $donorStmt->execute([':did' => $dId]);
            $donor = $donorStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($donor) {
                $donorName = $donor['FULL_NAME'] ?? $donor['full_name'] ?? 'Unknown Donor';
                $donorNic = $donor['NIC'] ?? $donor['nic'] ?? 'N/A';
                $donorGroup = $donor['BLOOD_GROUP'] ?? $donor['blood_group'] ?? 'N/A';
                
                // Mobile Number එක නිවැරදිව ලබා ගැනීමට යාවත්කාලීන කළ කොටස
                $donorMob = $donor['MOBILE'] ?? $donor['mobile'] ?? $donor['PHONE'] ?? $donor['phone'] ?? $donor['CONTACT'] ?? $donor['contact'] ?? $donor['CONTACT_NO'] ?? $donor['contact_no'] ?? 'N/A';
                if(empty(trim((string)$donorMob))) { 
                    $donorMob = 'N/A'; 
                }
            }
        }

        $appointments[] = [
            'appointment_id' => $app['APPOINTMENT_ID'] ?? $app['appointment_id'] ?? '',
            'donor_id' => $dId,
            'time_slot' => $app['TIME_SLOT'] ?? $app['time_slot'] ?? '',
            'pass_code' => $app['PASS_CODE'] ?? $app['pass_code'] ?? 'N/A',
            'status' => $app['STATUS'] ?? $app['status'] ?? 'Pending',
            'full_name' => $donorName,
            'nic' => $donorNic,
            'blood_group' => $donorGroup,
            'mobile' => $donorMob
        ];
    }
} catch (Exception $e) {
    $appointments = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Camp Bookings & Management - LifeLine Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f0f4f8; padding: 40px; color: #2b3674; }
        .container { max-width: 1100px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; }
        h2 { font-size: 22px; color: #2b3674; font-weight: 800; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f8fafc; color: #64748b; font-weight: 700; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
        .badge-safe { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef08a; color: #854d0e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .btn { padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 12px; cursor: pointer; text-decoration: none; display: inline-block; border: none; }
        .btn-green { background: #22c55e; color: white; }
        .back-link { color: #64748b; text-decoration: none; font-weight: 600; font-size: 14px; }
        .back-link:hover { color: #2b3674; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h2>⛺ Camp: <?php echo htmlspecialchars($camp_name); ?></h2>
            <p style="font-size: 13px; color: #64748b; margin-top: 4px;">📍 <?php echo htmlspecialchars($camp_venue); ?> | 📅 <?php echo htmlspecialchars($camp_date); ?></p>
        </div>
        <!-- මෙතන තමයි වෙනස් කළේ: ?tab=camps එකතු කර ඇත -->
        <a href="admin-dashboard.php?tab=camps" class="back-link">← Back to Camp Management</a>
    </div>



    <table>
        <tr>
            <th>Pass Code</th>
            <th>Donor Name</th>
            <th>NIC</th>
            <th>Blood Group</th>
            <th>Mobile</th>
            <th>Time Slot</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php if (!empty($appointments)): ?>
            <?php foreach ($appointments as $app): 
                $appId = $app['appointment_id'];
                $dId = $app['donor_id'];
                $passCode = $app['pass_code'];
                $dName = $app['full_name'];
                $dNic = $app['nic'];
                $dGroup = $app['blood_group'];
                $dMob = $app['mobile'];
                $slot = $app['time_slot'];
                $status = $app['status'];
            ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($passCode); ?></strong></td>
                    <td><?php echo htmlspecialchars($dName); ?></td>
                    <td><?php echo htmlspecialchars($dNic); ?></td>
                    <td><span class="badge badge-danger"><?php echo htmlspecialchars($dGroup); ?></span></td>
                    <td><?php echo htmlspecialchars($dMob); ?></td>
                    <td><?php echo htmlspecialchars($slot); ?></td>
                    <td>
                        <?php if ($status === 'Completed' || $status === 'Donated'): ?>
                            <span class="badge badge-safe">Donated Successfully</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><?php echo htmlspecialchars($status); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($status !== 'Completed' && $status !== 'Donated'): ?>
                            <a href="complete-donation.php?appointment_id=<?php echo $appId; ?>&donor_id=<?php echo $dId; ?>&camp_id=<?php echo $camp_id; ?>" class="btn btn-green" onclick="return confirm('Mark this donor as successfully donated? This will update the central blood stock.');">✔ Mark as Donated</a>
                        <?php else: ?>
                            <span style="font-size: 12px; color: #166534; font-weight: 700;">Completed</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" style="text-align: center; color: #64748b; padding: 30px;">No appointments booked for this camp yet. (Camp ID: <?php echo htmlspecialchars($camp_id); ?>)</td></tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>