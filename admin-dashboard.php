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

// MongoDB එකෙන් Active Emergency Appeals ගණන ලබා ගැනීම
$activeAlertsCount = 0;
try {
    if (isset($mongoClient)) {
        $activeAlertsCount = $mongoClient->Blood_Bank->emergency_appeals->countDocuments();
    }
} catch (Exception $e) {
    $activeAlertsCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeLine Connect - Admin System</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f0f4f8; background-image: radial-gradient(at 0% 0%, hsla(359, 100%, 74%, 0.15) 0px, transparent 50%), radial-gradient(at 100% 0%, hsla(204, 100%, 74%, 0.15) 0px, transparent 50%); color: #2b3674; display: flex; min-height: 100vh; }
        
        .sidebar { width: 260px; background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%); padding: 24px; box-shadow: 4px 0 24px rgba(255, 75, 43, 0.2); display: flex; flex-direction: column; position: fixed; height: 100vh; color: white; z-index: 10; }
        .brand { display: flex; align-items: center; gap: 12px; font-size: 20px; font-weight: 800; margin-bottom: 40px; }
        .nav-links { list-style: none; display: flex; flex-direction: column; gap: 10px; }
        .nav-links li a { text-decoration: none; color: rgba(255, 255, 255, 0.85); font-weight: 600; padding: 12px 16px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; transition: all 0.3s ease; cursor: pointer; font-size: 14px; }
        .nav-links li a:hover, .nav-links li a.active { background: rgba(255, 255, 255, 0.25); color: white; transform: translateX(5px); }
        .logout-btn { background: rgba(255, 255, 255, 0.15) !important; color: white !important; margin-top: 15px; justify-content: flex-start !important; }
        .logout-btn:hover { background: rgba(255, 255, 255, 0.25) !important; transform: translateX(0) !important; }

        /* රතු පාටින් දිලිසෙන තිත් (Pulsing Red Dot) සඳහා ස්ටයිල් එක */
        .pulsing-dot {
            width: 10px;
            height: 10px;
            background-color: #ffffff;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 rgba(255, 255, 255, 0.8);
            animation: pulse-dot 1.5s infinite;
        }

        @keyframes pulse-dot {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.9); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(255, 255, 255, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); }
        }

        .main-content { flex: 1; margin-left: 260px; padding: 32px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .topbar h1 { font-size: 26px; background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; }
        
        .tab-content { display: none; animation: fadeIn 0.4s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .card { background: white; padding: 24px; border-radius: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.04); margin-bottom: 24px; }
        .card-header { font-size: 18px; font-weight: 800; color: #2b3674; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .text-gradient { background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .btn { background: linear-gradient(135deg, #36D1DC 0%, #5B86E5 100%); color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-weight: 700; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-red { background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); }
        .btn-green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .btn-blue { background: linear-gradient(135deg, #36D1DC 0%, #5B86E5 100%); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f8fafc; font-weight: 700; color: #64748b; }
        tr:hover { background: #f1f5f9; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
        .badge-safe { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef08a; color: #854d0e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }

        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 32px; }
        .kpi-card { padding: 24px; border-radius: 20px; display: flex; align-items: center; gap: 16px; color: white; box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .bg-red { background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); }
        .bg-blue { background: linear-gradient(135deg, #36D1DC 0%, #5B86E5 100%); }
        .bg-yellow { background: linear-gradient(135deg, #FDC830 0%, #F37335 100%); }
        .bg-purple { background: linear-gradient(135deg, #8E2DE2 0%, #4A00E0 100%); }
        .kpi-icon { width: 52px; height: 52px; border-radius: 50%; background: rgba(255, 255, 255, 0.25); display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px; }
        #map { height: 320px; width: 100%; border-radius: 16px; z-index: 1; border: 2px solid #f0f4f8; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; }
        .form-group select, .form-group input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px; outline: none; }

        #printableReport {
            max-width: 900px; margin: 20px auto; background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; position: relative;
        }

        /* පින්තූරයේ ඇති පරිදි ඊතල (Arrows) සහිත සම්පූර්ණ Scrollbar Design එක */
        ::-webkit-scrollbar { width: 14px; }
        ::-webkit-scrollbar-track { background: #f8fafc; border-left: 1px solid #e2e8f0; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 7px; border: 3px solid #f8fafc; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }
        ::-webkit-scrollbar-button:single-button { background-color: #f1f5f9; display: block; background-size: 8px; background-repeat: no-repeat; height: 14px; border: 1px solid #e2e8f0; }
        ::-webkit-scrollbar-button:single-button:vertical:decrement { background-position: center 4px; background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 10 6'><path fill='%2364748b' d='M5 0L0 5h10z'/></svg>"); }
        ::-webkit-scrollbar-button:single-button:vertical:increment { background-position: center 4px; background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 10 6'><path fill='%2364748b' d='M0 0l5 5 5-5z'/></svg>"); }

        @media print {
            body * { visibility: hidden; }
            #printableReport, #printableReport * { visibility: visible; }
            #printableReport { 
                position: absolute; left: 50%; transform: translateX(-50%); top: 0; width: 100%; max-width: 100%; border: none !important; background: white !important; padding: 10px !important; box-shadow: none !important; 
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg> Admin Dashboard
        </div>
        <ul class="nav-links">
            <li><a class="nav-btn active" onclick="showTab('tab-dashboard', this)"><span>📊 Dashboard Overview</span></a></li>
            <li><a class="nav-btn" onclick="showTab('tab-stock', this)"><span>🩸 Blood Inventory</span></a></li>
            <li><a class="nav-btn" onclick="showTab('tab-requests', this)"><span>🏥 Hospital Requests</span></a></li>
            <li><a class="nav-btn" onclick="showTab('tab-hospitals', this)"><span>🏢 Hospital Registrations</span></a></li>
            <li><a class="nav-btn" onclick="showTab('tab-camps', this)"><span>⛺ Camp Management</span></a></li>
            <li><a class="nav-btn" onclick="showTab('tab-donors', this)"><span>👥 Donors</span></a></li>
            
            <li>
                <a class="nav-btn" onclick="showTab('tab-emergency-alerts', this)">
                    <span style="display: flex; align-items: center; gap: 10px;">🚨 Emergency Alerts</span>
                    <?php if ($activeAlertsCount > 0): ?>
                        <span class="pulsing-dot" title="<?php echo $activeAlertsCount; ?> Active Alerts"></span>
                    <?php endif; ?>
                </a>
            </li>

            <li><a class="nav-btn" onclick="showTab('tab-feedback', this)"><span>💬 Feedback</span></a></li>
            <li><a class="nav-btn" onclick="showTab('tab-staff', this)"><span>👨‍💼 Staff & Volunteers</span></a></li>
            <li><a class="nav-btn" onclick="showTab('tab-reports', this)"><span>📄 Final Reports</span></a></li>
            <li><a href="logout.php" class="logout-btn">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h1>Central Portal Management (LifeLine Connect)</h1>
            <div style="display: flex; align-items: center; gap: 12px; background: white; padding: 8px 20px; border-radius: 30px; font-weight: 600;">
                <span>System Admin</span>
                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);"></div>
            </div>
        </header>

        <!-- 1. DASHBOARD OVERVIEW -->
        <div id="tab-dashboard" class="tab-content">
            <?php
            $totalUnits = 0; $totalDonors = 0; $activeCampsCount = 0; $urgentRequestsCount = 0;
            $campsMapData = [];
            try {
                $unitStmt = $conn->prepare("SELECT SUM(total_units) as total FROM blood_stock");
                $unitStmt->execute(); $totalUnits = $unitStmt->fetch(PDO::FETCH_ASSOC)['TOTAL'] ?? 0;

                $donorStmt = $conn->prepare("SELECT COUNT(*) as total FROM donors");
                $donorStmt->execute(); $totalDonors = $donorStmt->fetch(PDO::FETCH_ASSOC)['TOTAL'] ?? 0;

                $campStmt = $conn->prepare("SELECT COUNT(*) as total FROM camps");
                $campStmt->execute(); $activeCampsCount = $campStmt->fetch(PDO::FETCH_ASSOC)['TOTAL'] ?? 0;

                $reqStmt = $conn->prepare("SELECT COUNT(*) as total FROM hospital_requests WHERE status = 'Pending'");
                $reqStmt->execute(); $urgentRequestsCount = $reqStmt->fetch(PDO::FETCH_ASSOC)['TOTAL'] ?? 0;

                $mapCampsStmt = $conn->query("SELECT * FROM camps");
                $campsMapData = $mapCampsStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}
            ?>
            <div class="kpi-grid">
                <div class="kpi-card bg-red"><div class="kpi-icon">🩸</div><div><p style="font-size: 13px;">Total Units</p><h3><?php echo number_format($totalUnits); ?> Pints</h3></div></div>
                <div class="kpi-card bg-blue"><div class="kpi-icon">👥</div><div><p style="font-size: 13px;">Donors</p><h3><?php echo number_format($totalDonors); ?></h3></div></div>
                <div class="kpi-card bg-yellow"><div class="kpi-icon">⛺</div><div><p style="font-size: 13px;">Camps</p><h3><?php echo $activeCampsCount; ?> Active</h3></div></div>
                <div class="kpi-card bg-purple"><div class="kpi-icon">🚨</div><div><p style="font-size: 13px;">Requests</p><h3><?php echo $urgentRequestsCount; ?> Pending</h3></div></div>
            </div>
            <div class="dashboard-grid">
                <div class="card"><div class="card-header"><span class="text-gradient">📍</span> Live Camps Map</div><div id="map"></div></div>
                <div class="card"><div class="card-header"><span class="text-gradient">🚨</span> Emergency Appeals</div>
                    <?php
                    $emergencyList = [];
                    try {
                        if (isset($mongoClient)) {
                            $emergencyCol = $mongoClient->Blood_Bank->emergency_appeals;
                            $cursor = $emergencyCol->find([], ['sort' => ['_id' => -1], 'limit' => 2]);
                            $emergencyList = iterator_to_array($cursor);
                        }
                    } catch (Exception $e) {}

                    if (empty($emergencyList)) {
                        $emergencyList = [['hospital_name' => 'National Hospital', 'blood_group' => 'O- Negative', 'message' => 'Need 5 units urgently.']];
                    }

                    foreach ($emergencyList as $em):
                        $h_phone = $em['hotline'] ?? $em['phone'] ?? '';
                        if(empty($h_phone)) {
                            try {
                                $hpStmt = $conn->prepare("SELECT phone FROM hospitals WHERE hospital_name = :hname");
                                $hpStmt->execute([':hname' => $em['hospital_name']]);
                                $hpRow = $hpStmt->fetch(PDO::FETCH_ASSOC);
                                $h_phone = $hpRow['PHONE'] ?? $hpRow['phone'] ?? 'N/A';
                            } catch(Exception $e) { $h_phone = 'N/A'; }
                        }
                        if(empty(trim((string)$h_phone))) { $h_phone = 'N/A'; }
                    ?>
                        <div style="background: #fff0f0; border: 2px dashed #ff4b2b; padding: 18px; border-radius: 16px; color: #d32f2f; font-weight: 600; margin-bottom: 12px;">
                            <p style="font-size: 14px; margin-bottom: 4px;"><strong><?php echo htmlspecialchars($em['hospital_name'] ?? 'Hospital'); ?> (<?php echo htmlspecialchars($em['blood_group'] ?? 'General'); ?>):</strong></p>
                            <p style="font-size: 13px; color: #475569; font-weight: 500;"><?php echo htmlspecialchars($em['message'] ?? $em['description'] ?? 'Urgent blood requirement.'); ?></p>
                            <span style="font-size: 13px; font-weight: bold; color: #d32f2f;">📞 Hotline: <?php echo htmlspecialchars($h_phone); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 2. BLOOD STOCK (ORACLE) -->
        <div id="tab-stock" class="tab-content">
            <div class="card">
                <div class="card-header"><span class="text-gradient">🩸</span> Central Blood Inventory</div>
                <form action="insert-stock.php" method="POST" style="background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end;">
                    <div style="flex: 1;"><label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Blood Group</label><input type="text" name="blood_group" placeholder="e.g. AB+" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;" required></div>
                    <div style="flex: 1;"><label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Total Units (Pints)</label><input type="number" name="units" placeholder="e.g. 50" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;" required></div>
                    <div><button type="submit" class="btn btn-green" style="padding: 11px 20px;">➕ Add Stock</button></div>
                </form>
                
                <div style="max-height: 400px; overflow-y: auto; overflow-x: hidden; padding-right: 8px;">
                    <table>
                        <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 1;">
                            <tr><th>Blood Group</th><th>Total Units</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                        <?php
                        try {
                            $stockStmt = $conn->prepare("SELECT * FROM blood_stock ORDER BY blood_group ASC");
                            $stockStmt->execute();
                            $stockList = $stockStmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) { $stockList = []; }

                        if (empty($stockList)):
                        ?>
                            <tr><td colspan="4" style="text-align: center; color: #64748b;">No blood inventory records found.</td></tr>
                        <?php else: foreach ($stockList as $stock): 
                            $bGroup = $stock['BLOOD_GROUP'] ?? $stock['blood_group'] ?? 'N/A';
                            $units = (int) ($stock['TOTAL_UNITS'] ?? $stock['total_units'] ?? 0);
                            $statusBadge = $units < 10 ? '<span class="badge badge-danger">Critical Low</span>' : ($units < 30 ? '<span class="badge badge-warning">Moderate</span>' : '<span class="badge badge-safe">Healthy Stock</span>');
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($bGroup); ?></strong></td>
                                <td><?php echo $units; ?> Pints</td>
                                <td><?php echo $statusBadge; ?></td>
                                <td>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <form action="update-stock.php" method="POST" style="display: flex; gap: 6px; align-items: center; margin: 0;">
                                            <input type="hidden" name="blood_group" value="<?php echo htmlspecialchars($bGroup); ?>">
                                            <input type="number" name="units" value="<?php echo $units; ?>" style="width: 70px; padding: 6px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
                                            <button type="submit" class="btn" style="padding: 6px 12px; font-size: 12px;">Update</button>
                                        </form>
                                        <a href="delete-stock.php?group=<?php echo urlencode($bGroup); ?>" class="btn btn-red" style="padding: 6px 12px; font-size: 12px; text-decoration: none;" onclick="return confirm('Are you sure you want to delete this record?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. HOSPITAL REQUESTS -->
        <div id="tab-requests" class="tab-content">
            <div class="card">
                <div class="card-header"><span class="text-gradient">🏥</span> Pending Hospital Blood Requests</div>
                <div style="max-height: 480px; overflow-y: auto; overflow-x: hidden; padding-right: 8px;">
                    <table>
                        <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 1;">
                            <tr><th>Request ID</th><th>Hospital Name</th><th>Group</th><th>Units</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php
                        try {
                            $reqStmt = $conn->prepare("SELECT * FROM hospital_requests ORDER BY request_id DESC");
                            $reqStmt->execute();
                            $hospital_requests = $reqStmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) { $hospital_requests = []; }

                        $reqCount = 0;
                        foreach ($hospital_requests as $req):
                            $reqCount++;
                            $r_id = $req['REQUEST_ID'] ?? $req['request_id'];
                            $h_name = $req['HOSPITAL_NAME'] ?? $req['hospital_name'];
                            $b_group = $req['BLOOD_GROUP'] ?? $req['blood_group'];
                            $units = $req['UNITS'] ?? $req['units'];
                            $status = $req['STATUS'] ?? $req['status'];
                        ?>
                            <tr>
                                <td><?php echo "REQ-" . htmlspecialchars($r_id); ?></td>
                                <td><?php echo htmlspecialchars($h_name); ?></td>
                                <td><strong><?php echo htmlspecialchars($b_group); ?></strong></td>
                                <td><?php echo htmlspecialchars($units); ?> Pints</td>
                                <td>
                                    <?php if($status == 'Approved'): ?>
                                        <span class="badge badge-safe">Approved</span>
                                    <?php elseif($status == 'Rejected'): ?>
                                        <span class="badge badge-danger">Rejected</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="hospital_requests_action.php?id=<?php echo $r_id; ?>&action=approve" class="btn btn-green" style="padding: 6px 12px; font-size: 12px; text-decoration: none;">Approve</a> 
                                    <a href="hospital_requests_action.php?id=<?php echo $r_id; ?>&action=reject" class="btn btn-red" style="padding: 6px 12px; font-size: 12px; text-decoration: none;" onclick="return confirm('Are you sure you want to reject this request?');">Reject</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if ($reqCount == 0): ?>
                            <tr><td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">No pending hospital requests found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. HOSPITAL REGISTRATION REQUESTS -->
        <div id="tab-hospitals" class="tab-content">
            <div class="card">
                <div class="card-header"><span class="text-gradient">🏢</span> Pending Hospital Registration Requests</div>
                <div style="max-height: 480px; overflow-y: auto; overflow-x: hidden; padding-right: 8px;">
                    <table>
                        <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 1;">
                            <tr><th>Hospital ID</th><th>Hospital Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php
                        try {
                            $hospReqStmt = $conn->prepare("SELECT * FROM hospitals WHERE status = 'Pending' ORDER BY hospital_id DESC");
                            $hospReqStmt->execute();
                            $pendingHospitals = $hospReqStmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) { $pendingHospitals = []; }

                        $hCount = 0;
                        foreach ($pendingHospitals as $h):
                            $hCount++;
                            $h_id = $h['HOSPITAL_ID'] ?? $h['hospital_id'];
                            $h_name = $h['HOSPITAL_NAME'] ?? $h['hospital_name'];
                            $h_email = $h['EMAIL'] ?? $h['email'];
                            $h_phone = $h['PHONE'] ?? $h['phone'];
                        ?>
                            <tr>
                                <td>HOSP-<?php echo htmlspecialchars($h_id); ?></td>
                                <td><strong><?php echo htmlspecialchars($h_name); ?></strong></td>
                                <td><?php echo htmlspecialchars($h_email); ?></td>
                                <td><?php echo htmlspecialchars($h_phone); ?></td>
                                <td><span class="badge badge-warning">Pending Approval</span></td>
                                <td>
                                    <a href="hospital_approve_action.php?id=<?php echo $h_id; ?>&action=approve" class="btn btn-green" style="padding: 6px 12px; font-size: 12px; text-decoration: none;">Approve</a> 
                                    <a href="hospital_approve_action.php?id=<?php echo $h_id; ?>&action=reject" class="btn btn-red" style="padding: 6px 12px; font-size: 12px; text-decoration: none;" onclick="return confirm('Reject this hospital registration?');">Reject</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if ($hCount == 0): ?>
                            <tr><td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">No pending hospital registration requests found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 5. CAMPS & EVENTS -->
        <div id="tab-camps" class="tab-content">
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header"><span class="text-gradient">⛺</span> Organize New Camp</div>
                    <form action="add-camp.php" method="POST">
                        <div class="form-group"><label>Camp Name</label><input type="text" name="camp_name" placeholder="e.g. Kandy Youth Blood Drive" required></div>
                        <div class="form-group"><label>Location / Venue</label><input type="text" name="venue" placeholder="Venue Address" required></div>
                        
                        <div style="display: flex; gap: 15px;">
                            <div class="form-group" style="flex: 1;"><label>Start Date</label><input type="date" name="start_date" required></div>
                            <div class="form-group" style="flex: 1;"><label>End Date</label><input type="date" name="end_date" required></div>
                        </div>

                        <div style="display: flex; gap: 15px;">
                            <div class="form-group" style="flex: 1;"><label>Latitude</label><input type="text" name="latitude" placeholder="e.g. 6.9271" required></div>
                            <div class="form-group" style="flex: 1;"><label>Longitude</label><input type="text" name="longitude" placeholder="e.g. 79.8612" required></div>
                        </div>

                        <button type="submit" class="btn btn-red" style="width: 100%; margin-top: 10px;">Schedule Camp & Update Map</button>
                    </form>
                </div>
                <div class="card">
                    <div class="card-header"><span class="text-gradient">📅</span> Upcoming Camps</div>
                    
                    <div style="max-height: 440px; overflow-y: auto; overflow-x: hidden; padding-right: 8px;">
                        <?php
                        try {
                            $campsList = $conn->query("SELECT * FROM camps ORDER BY camp_id DESC")->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) { $campsList = []; }

                        if (empty($campsList)):
                        ?>
                            <div style="padding: 20px; text-align: center; color: #64748b; font-size: 14px;">No upcoming camps scheduled.</div>
                        <?php else: foreach ($campsList as $camp): 
                            $cName = $camp['CAMP_NAME'] ?? $camp['camp_name'] ?? '';
                            $cVenue = $camp['VENUE'] ?? $camp['venue'] ?? '';
                            $cStart = $camp['START_DATE'] ?? $camp['start_date'] ?? $camp['CAMP_DATE'] ?? $camp['camp_date'] ?? '';
                            $cId = $camp['CAMP_ID'] ?? $camp['camp_id'] ?? '';
                        ?>
                            <div style="background: #f8fafc; padding: 15px; border-left: 4px solid #36D1DC; border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="color: #2b3674; font-size: 15px; margin-bottom: 4px;"><?php echo htmlspecialchars($cName); ?></h4>
                                    <p style="font-size: 13px; color: #64748b; margin-bottom: 2px;">📍 <?php echo htmlspecialchars($cVenue); ?></p>
                                    <p style="font-size: 12px; font-weight: 700; color: #FF416C;">📅 <?php echo htmlspecialchars($cStart); ?></p>
                                </div>
                                <div>
                                    <a href="view-camp-donors.php?camp_id=<?php echo htmlspecialchars($cId); ?>" class="btn btn-blue" style="padding: 6px 12px; font-size: 12px; text-decoration: none;">Manage Bookings</a>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. DONORS (ORACLE) TAB -->
        <div id="tab-donors" class="tab-content">
            <div class="card">
                <div class="card-header"><span class="text-gradient">👥</span> Registered Donors</div>
                <div style="max-height: 500px; overflow-y: auto; overflow-x: hidden; padding-right: 8px;">
                    <table>
                        <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 1;">
                            <tr><th>Donor ID</th><th>Full Name</th><th>NIC</th><th>Blood Group</th><th>District</th><th>Mobile</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php
                        try {
                            $donorTabStmt = $conn->prepare("SELECT * FROM donors ORDER BY donor_id DESC");
                            $donorTabStmt->execute();
                            $donorsTabList = $donorTabStmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) { $donorsTabList = []; }

                        if (empty($donorsTabList)):
                        ?>
                            <tr><td colspan="7" style="text-align: center; color: #64748b; padding: 20px;">No registered donors found.</td></tr>
                        <?php else: foreach ($donorsTabList as $d): 
                            $dId = $d['DONOR_ID'] ?? $d['donor_id'] ?? '';
                            $dName = $d['FULL_NAME'] ?? $d['full_name'] ?? '';
                            $dNic = $d['NIC'] ?? $d['nic'] ?? '';
                            $dGroup = $d['BLOOD_GROUP'] ?? $d['blood_group'] ?? '';
                            $dDist = $d['DISTRICT'] ?? $d['district'] ?? '';
                            
                            $dMob = $d['MOBILE'] ?? $d['mobile'] ?? $d['PHONE'] ?? $d['phone'] ?? $d['CONTACT'] ?? $d['contact'] ?? $d['CONTACT_NO'] ?? 'N/A';
                            if(empty(trim((string)$dMob))) { $dMob = 'N/A'; }

                            $dEmail = $d['EMAIL'] ?? $d['email'] ?? 'N/A';
                            $dGender = $d['GENDER'] ?? $d['gender'] ?? 'N/A';
                            $dDob = $d['DOB'] ?? $d['dob'] ?? 'N/A';
                        ?>
                            <tr>
                                <td>D-<?php echo htmlspecialchars($dId); ?></td>
                                <td><strong><?php echo htmlspecialchars($dName); ?></strong></td>
                                <td><?php echo htmlspecialchars($dNic); ?></td>
                                <td><span class="badge badge-danger"><?php echo htmlspecialchars($dGroup); ?></span></td>
                                <td><?php echo htmlspecialchars($dDist); ?></td>
                                <td><?php echo htmlspecialchars($dMob); ?></td>
                                <td>
                                    <button onclick="viewDonorDetails('D-<?php echo htmlspecialchars($dId); ?>', '<?php echo htmlspecialchars($dName); ?>', '<?php echo htmlspecialchars($dNic); ?>', '<?php echo htmlspecialchars($dGroup); ?>', '<?php echo htmlspecialchars($dDist); ?>', '<?php echo htmlspecialchars($dMob); ?>', '<?php echo htmlspecialchars($dEmail); ?>', '<?php echo htmlspecialchars($dGender); ?>', '<?php echo htmlspecialchars($dDob); ?>')" class="btn btn-blue" style="padding: 6px 14px; font-size: 12px; text-decoration: none;">View</button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 7. EMERGENCY ALERTS (ADMIN MANAGEMENT) -->
        <div id="tab-emergency-alerts" class="tab-content">
            <div class="card">
                <div class="card-header"><span class="text-gradient">🚨</span> Active Emergency Appeals</div>
                <div style="max-height: 500px; overflow-y: auto; overflow-x: hidden; padding-right: 10px;">
                    <?php
                    $adminAlerts = [];
                    try {
                        if (isset($mongoClient)) {
                            $adminAlerts = iterator_to_array($mongoClient->Blood_Bank->emergency_appeals->find([], ['sort' => ['_id' => -1]]));
                        }
                    } catch (Exception $e) {}

                    if (!empty($adminAlerts)):
                        foreach ($adminAlerts as $alrt):
                            $aId = (string)($alrt['_id'] ?? '');
                            
                            $h_phone = $alrt['hotline'] ?? $alrt['phone'] ?? '';
                            if(empty($h_phone)) {
                                try {
                                    $hpStmt = $conn->prepare("SELECT phone FROM hospitals WHERE hospital_name = :hname");
                                    $hpStmt->execute([':hname' => $alrt['hospital_name']]);
                                    $hpRow = $hpStmt->fetch(PDO::FETCH_ASSOC);
                                    $h_phone = $hpRow['PHONE'] ?? $hpRow['phone'] ?? 'N/A';
                                } catch(Exception $e) { $h_phone = 'N/A'; }
                            }
                            if(empty(trim((string)$h_phone))) { $h_phone = 'N/A'; }
                    ?>
                        <div style="border: 1px solid #fee2e2; border-left: 5px solid #ef4444; padding: 20px; border-radius: 12px; margin-bottom: 16px; background: #fffcfc; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3 style="color: #991b1b; margin-bottom: 8px; display: flex; gap: 15px; align-items: center;">
                                    <span><?php echo htmlspecialchars($alrt['hospital_name'] ?? ''); ?></span>
                                    <span class="badge badge-danger">Needs <?php echo htmlspecialchars($alrt['blood_group'] ?? ''); ?></span>
                                </h3>
                                <p style="color: #475569; font-size: 14px; margin-bottom: 6px;"><?php echo htmlspecialchars($alrt['description'] ?? ''); ?></p>
                                <span style="font-size: 13px; font-weight: bold; color: #d32f2f;">📞 Hotline: <?php echo htmlspecialchars($h_phone); ?></span>
                            </div>
                            <div>
                                <a href="clear-alert.php?id=<?php echo $aId; ?>" class="btn btn-red" style="padding: 8px 16px; font-size: 12px; text-decoration: none;" onclick="return confirm('Mark this emergency appeal as completed and remove it?');">🗑️ Clear / Complete</a>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                        <p style="color: #64748b; text-align: center; padding: 20px;">No active emergency appeals found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 8. FEEDBACK (MONGODB) -->
        <div id="tab-feedback" class="tab-content">
            <div class="card">
                <div class="card-header"><span class="text-gradient">💬</span> Donor Feedback & Reviews</div>
                <div style="max-height: 500px; overflow-y: auto; overflow-x: hidden; padding-right: 10px;">
                    <?php
                    $feedbackList = [];
                    try {
                        if (isset($mongoClient)) {
                            $collection = $mongoClient->Blood_Bank->feedback;
                            $feedbackList = iterator_to_array($collection->find([], ['sort' => ['created_at' => -1]]));
                        }
                    } catch (Exception $e) {}

                    if (empty($feedbackList)) {
                        $feedbackList = [['donor_id' => 'D-8821', 'camp_name' => 'NIBM Campus Drive', 'rating' => 5, 'review' => 'Very well organized!']];
                    }

                    foreach ($feedbackList as $fb):
                    ?>
                        <div style="padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; margin-bottom: 15px;">
                            <span style="color: #FDC830; font-size: 16px;"><?php echo str_repeat('⭐', (int)($fb['rating'] ?? 5)); ?></span>
                            <h4 style="margin: 6px 0; color: #2b3674; font-size: 16px;">"<?php echo htmlspecialchars($fb['review'] ?? ''); ?>"</h4>
                            <span style="font-weight: 700; color: #FF416C; font-size: 13px;">- Donor ID: <?php echo htmlspecialchars($fb['donor_id'] ?? 'Anonymous'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 9. STAFF & VOLUNTEERS -->
        <div id="tab-staff" class="tab-content">
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div>
                        <h2 style="font-size: 18px; font-weight: 800; color: #ff4b2b; margin-bottom: 4px;">‍👨‍💼 Staff & Volunteer Assignment Tracking</h2>
                    </div>
                    <button onclick="openStaffModal()" class="btn btn-red" style="background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); padding: 10px 18px; font-size: 13px;">+ Assign New Member</button>
                </div>
                <div style="max-height: 480px; overflow-y: auto; overflow-x: hidden; padding-right: 8px;">
                    <table>
                        <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 1;">
                            <tr>
                                <th>Member Name</th>
                                <th>Role</th>
                                <th>Assigned Camp</th>
                                <th>Shift Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        try {
                            $staffList = $conn->query("SELECT * FROM staff_assignments ORDER BY staff_id DESC")->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) { $staffList = []; }

                        if (empty($staffList)):
                        ?>
                            <tr><td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">No staff assigned yet.</td></tr>
                        <?php else: foreach ($staffList as $st): 
                            $sId = $st['STAFF_ID'] ?? $st['staff_id'];
                            $sName = $st['STAFF_NAME'] ?? $st['staff_name'];
                            $sRole = $st['ROLE'] ?? $st['role'];
                            $sCamp = $st['ASSIGNED_CAMP'] ?? $st['assigned_camp'];
                            $sShift = $st['SHIFT_TIME'] ?? $st['shift_time'];
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($sName); ?></strong></td>
                                <td><?php echo htmlspecialchars($sRole); ?></td>
                                <td><?php echo htmlspecialchars($sCamp); ?></td>
                                <td><?php echo htmlspecialchars($sShift); ?></td>
                                <td>
                                    <a href="delete-staff.php?id=<?php echo $sId; ?>" class="btn btn-red" style="padding: 6px 14px; font-size: 12px; text-decoration: none;" onclick="return confirm('Reassign this member?');">Reassign</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 10. PL/SQL REPORTS -->
        <div id="tab-reports" class="tab-content">
            <div class="card">
                <div class="card-header"><span class="text-gradient">📄</span> System Reports Generator</div>
                
                <!-- Database Backup Button -->
                <div style="margin-bottom: 20px;">
                    <a href="backup-database.php" class="btn btn-green" style="padding: 12px 20px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        📥 Download Database Backup
                    </a>
                </div>

                <form method="POST" action="" style="display: flex; gap: 20px; margin-bottom: 25px; align-items: flex-end; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 250px; margin-bottom: 0;">
                        <label>Select Report Type</label>
                        <select name="report_type" id="reportType" onchange="checkReportSelection()" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px; outline: none;">
                            <option value="stock" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'stock') ? 'selected' : ''; ?>>Current Blood Stock Status Report</option>
                            <option value="requests" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'requests') ? 'selected' : ''; ?>>Hospital Requests Summary Report</option>
                            <option value="camps" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'camps') ? 'selected' : ''; ?>>Scheduled Camps Report</option>
                            <option value="emergencies" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'emergencies') ? 'selected' : ''; ?>>Emergency Appeals Report</option>
                            <option value="eligibility" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'eligibility') ? 'selected' : ''; ?>>Donor Eligibility Report</option>
                        </select>
                    </div>

                    <!-- NIC Input Field for PL/SQL Function -->
                    <div class="form-group" id="nicInputGroup" style="flex: 1; min-width: 250px; margin-bottom: 0; display: <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'eligibility') ? 'block' : 'none'; ?>;">
                        <label>Enter Donor NIC Number</label>
                        <input type="text" name="donor_nic" value="<?php echo htmlspecialchars($_POST['donor_nic'] ?? ''); ?>" placeholder="e.g. 200012345678" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px; outline: none;">
                    </div>

                    <div style="flex: 1; min-width: 200px;"><button type="submit" name="generate_report" class="btn btn-blue" style="width: 100%; padding: 12px;">Generate Report</button></div>
                </form>

                <?php
                if (isset($_POST['generate_report'])) {
                    $reportType = $_POST['report_type'];
                    
                    echo '<div id="printableReport">';
                    echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">';
                    echo '<h3 style="color: #2b3674; font-size: 18px; margin: 0;">📊 LifeLine Connect - System Report</h3>';
                    echo '<button onclick="printReport()" class="no-print btn btn-green" style="padding: 8px 16px; font-size: 13px; display: flex; align-items: center; gap: 6px;">📥 Download as PDF</button>';
                    echo '</div>';
                    echo '<hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 15px;">';

                    try {
                        if ($reportType == 'stock') {
                            $rows = $conn->query("SELECT * FROM blood_stock ORDER BY blood_group ASC")->fetchAll(PDO::FETCH_ASSOC);
                            echo '<table><tr><th>Blood Group</th><th>Total Units</th></tr>';
                            foreach ($rows as $r) { echo '<tr><td><strong>' . ($r['BLOOD_GROUP'] ?? $r['blood_group']) . '</strong></td><td>' . ($r['TOTAL_UNITS'] ?? $r['total_units']) . ' Pints</td></tr>'; }
                            echo '</table>';
                        } elseif ($reportType == 'requests') {
                            $rows = $conn->query("SELECT * FROM hospital_requests ORDER BY request_id DESC")->fetchAll(PDO::FETCH_ASSOC);
                            echo '<table><tr><th>Hospital</th><th>Group</th><th>Units</th><th>Status</th></tr>';
                            foreach ($rows as $r) { echo '<tr><td>' . ($r['HOSPITAL_NAME'] ?? $r['hospital_name']) . '</td><td>' . ($r['BLOOD_GROUP'] ?? $r['blood_group']) . '</td><td>' . ($r['UNITS'] ?? $r['units']) . '</td><td>' . ($r['STATUS'] ?? $r['status']) . '</td></tr>'; }
                            echo '</table>';
                        } elseif ($reportType == 'camps') {
                            $rows = $conn->query("SELECT * FROM camps ORDER BY camp_id DESC")->fetchAll(PDO::FETCH_ASSOC);
                            echo '<table><tr><th>Camp Name</th><th>Venue</th><th>Start Date</th><th>End Date</th><th>Total Appointments</th></tr>';
                            
                            $appCountStmt = $conn->prepare("SELECT COUNT(*) as total_apps FROM camp_appointments WHERE camp_id = :cid");
                            
                            foreach ($rows as $r) { 
                                $cId = $r['CAMP_ID'] ?? $r['camp_id'];
                                $sDate = $r['START_DATE'] ?? $r['start_date'] ?? $r['CAMP_DATE'] ?? $r['camp_date'] ?? 'N/A';
                                $eDate = $r['END_DATE'] ?? $r['end_date'] ?? 'N/A';
                                
                                $appCountStmt->execute([':cid' => $cId]);
                                $appResult = $appCountStmt->fetch(PDO::FETCH_ASSOC);
                                $totalApps = $appResult['TOTAL_APPS'] ?? $appResult['total_apps'] ?? 0;

                                echo '<tr><td><strong>' . htmlspecialchars($r['CAMP_NAME'] ?? $r['camp_name']) . '</strong></td><td>' . htmlspecialchars($r['VENUE'] ?? $r['venue']) . '</td><td>' . $sDate . '</td><td>' . $eDate . '</td><td><span class="badge badge-safe">' . $totalApps . ' Donors</span></td></tr>'; 
                            }
                            echo '</table>';
                        } elseif ($reportType == 'emergencies') {
                            $emergencyRows = [];
                            if (isset($mongoClient)) {
                                $collection = $mongoClient->Blood_Bank->emergency_appeals;
                                $emergencyRows = iterator_to_array($collection->find([], ['sort' => ['_id' => -1]]));
                            }
                            
                            echo '<table><tr><th>Hospital Name</th><th>Blood Group Required</th><th>Message / Details</th></tr>';
                            if (!empty($emergencyRows)) {
                                foreach ($emergencyRows as $em) {
                                    $hName = $em['hospital_name'] ?? 'N/A';
                                    $bGroup = $em['blood_group'] ?? 'N/A';
                                    $msg = $em['message'] ?? 'N/A';
                                    echo '<tr><td><strong>' . htmlspecialchars($hName) . '</strong></td><td><span class="badge badge-danger">' . htmlspecialchars($bGroup) . '</span></td><td>' . htmlspecialchars($msg) . '</td></tr>';
                                }
                            } else {
                                echo '<tr><td colspan="3" style="text-align: center; color: #64748b;">No emergency appeals found in MongoDB.</td></tr>';
                            }
                            echo '</table>';
                        } elseif ($reportType == 'eligibility') {
                            $nic = trim($_POST['donor_nic'] ?? '');
                            if (!empty($nic)) {
                                $outputResult = "";
                                $sql = "BEGIN :result := get_donor_eligibility_func(:nic); END;";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindParam(':nic', $nic, PDO::PARAM_STR);
                                $stmt->bindParam(':result', $outputResult, PDO::PARAM_STR, 400);
                                $stmt->execute();

                                echo '<div style="background: #e2fbe8; border: 1px solid #22c55e; color: #15803d; padding: 16px; border-radius: 10px; font-weight: 700; font-size: 15px;">';
                                echo '✅ <strong>PL/SQL Function Result:</strong> ' . htmlspecialchars($outputResult);
                                echo '</div>';
                            } else {
                                echo '<div style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 16px; border-radius: 10px; font-weight: 600;">⚠️ Please enter a valid Donor NIC number to check eligibility.</div>';
                            }
                        }
                    } catch (Exception $e) { echo '<p style="color: red;">Error generating report: ' . $e->getMessage() . '</p>'; }
                    
                    echo '<div style="margin-top: 20px; font-size: 12px; color: #64748b; text-align: right;">Generated on: ' . date('Y-m-d H:i:s') . ' | LifeLine Connect System</div>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

    </main>

    <!-- Assign New Member Modal Popup -->
    <div id="staffModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 20px; width: 450px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="color: #2b3674; font-weight: 800;">Assign Staff / Volunteer</h3>
                <button onclick="closeStaffModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
            </div>
            <form action="add-staff.php" method="POST">
                <div class="form-group">
                    <label>Member Name</label>
                    <input type="text" name="staff_name" placeholder="e.g. Dr. Kamal Perera" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                </div>
                <div class="form-group">
                    <label>Role / Position</label>
                    <input type="text" name="role" placeholder="e.g. Medical Officer (MO) / Phlebotomist" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                </div>
                <div class="form-group">
                    <label>Assigned Camp / Venue</label>
                    <input type="text" name="assigned_camp" placeholder="e.g. NIBM City Blood Drive" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                </div>
                <div class="form-group">
                    <label>Shift Time</label>
                    <input type="text" name="shift_time" placeholder="e.g. 09:00 AM - 02:00 PM" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-red" style="flex: 1; padding: 12px; border-radius: 8px; border: none; font-weight: 700; cursor: pointer;">Save Assignment</button>
                    <button type="button" onclick="closeStaffModal()" style="flex: 1; background: #e2e8f0; color: #475569; padding: 12px; border-radius: 8px; border: none; font-weight: 700; cursor: pointer;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Donor Details View Modal Popup -->
    <div id="donorModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 20px; width: 480px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="color: #2b3674; font-weight: 800;">👤 Donor Profile Details</h3>
                <button onclick="closeDonorModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
            </div>
            <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px; color: #475569;">
                <p><strong>Donor ID:</strong> <span id="mDonorId" style="color: #2b3674;"></span></p>
                <p><strong>Full Name:</strong> <span id="mDonorName" style="color: #2b3674;"></span></p>
                <p><strong>NIC Number:</strong> <span id="mDonorNic" style="color: #2b3674;"></span></p>
                <p><strong>Blood Group:</strong> <span id="mDonorGroup" style="color: #ff4b2b; font-weight: 700;"></span></p>
                <p><strong>District:</strong> <span id="mDonorDist" style="color: #2b3674;"></span></p>
                <p><strong>Mobile Number:</strong> <span id="mDonorMob" style="color: #2b3674;"></span></p>
                <p><strong>Email Address:</strong> <span id="mDonorEmail" style="color: #2b3674;"></span></p>
                <p><strong>Gender:</strong> <span id="mDonorGender" style="color: #2b3674;"></span></p>
                <p><strong>Date of Birth:</strong> <span id="mDonorDob" style="color: #2b3674;"></span></p>
            </div>
            <div style="margin-top: 25px; text-align: right;">
                <button onclick="closeDonorModal()" class="btn btn-blue" style="padding: 8px 20px;">Close</button>
            </div>
        </div>
    </div>

    <script>
        var map = L.map('map').setView([7.8731, 80.7718], 7); 
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        const dbCamps = <?php echo json_encode($campsMapData); ?>;

        dbCamps.forEach(camp => {
            let lat = camp.LATITUDE || camp.latitude || camp.LAT || camp.lat;
            let lng = camp.LONGITUDE || camp.longitude || camp.LNG || camp.lng || camp.LON || camp.lon;
            let name = camp.CAMP_NAME || camp.camp_name || camp.NAME || camp.name || 'Blood Donation Camp';
            let venue = camp.VENUE || camp.venue || camp.LOCATION || camp.location || '';

            if (lat && lng && lat !== 'null' && lng !== 'null') {
                L.marker([parseFloat(lat), parseFloat(lng)]).addTo(map)
                    .bindPopup(`<b>${name}</b><br>${venue}`);
            }
        });

        if(dbCamps.length === 0) {
            L.marker([6.9147, 79.8656]).addTo(map).bindPopup("<b>Colombo Central Camp</b>");
            L.marker([7.2906, 80.6337]).addTo(map).bindPopup("<b>Kandy Youth Drive</b>");
        }

        function openStaffModal() {
            document.getElementById('staffModal').style.display = 'flex';
        }
        function closeStaffModal() {
            document.getElementById('staffModal').style.display = 'none';
        }

        function viewDonorDetails(id, name, nic, group, dist, mob, email, gender, dob) {
            document.getElementById('mDonorId').innerText = id;
            document.getElementById('mDonorName').innerText = name;
            document.getElementById('mDonorNic').innerText = nic;
            document.getElementById('mDonorGroup').innerText = group;
            document.getElementById('mDonorDist').innerText = dist;
            document.getElementById('mDonorMob').innerText = mob;
            document.getElementById('mDonorEmail').innerText = email;
            document.getElementById('mDonorGender').innerText = gender;
            document.getElementById('mDonorDob').innerText = dob;
            document.getElementById('donorModal').style.display = 'flex';
        }

        function closeDonorModal() {
            document.getElementById('donorModal').style.display = 'none';
        }

        function checkReportSelection() {
            const reportType = document.getElementById('reportType').value;
            const nicGroup = document.getElementById('nicInputGroup');
            if (reportType === 'eligibility') {
                nicGroup.style.display = 'block';
            } else {
                nicGroup.style.display = 'none';
            }
        }

        function printReport() {
            window.print();
        }

        function showTab(tabId, clickedElement) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(l => l.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            
            if (clickedElement) {
                clickedElement.classList.add('active');
            } else {
                const targetLink = document.querySelector(`a[onclick*='${tabId}']`);
                if (targetLink) targetLink.classList.add('active');
            }
            if(tabId === 'tab-dashboard') setTimeout(() => { map.invalidateSize(); }, 100);
        }

        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');

            if (tabParam === 'emergency') {
                const alertBtn = document.querySelector("a[onclick*='tab-emergency-alerts']");
                showTab('tab-emergency-alerts', alertBtn);
            } else if (tabParam === 'camps') {
                const campBtn = document.querySelector("a[onclick*='tab-camps']");
                showTab('tab-camps', campBtn);
            } else {
                <?php if (isset($_POST['generate_report'])): ?>
                    const reportBtn = document.querySelector("a[onclick*='tab-reports']");
                    showTab('tab-reports', reportBtn);
                <?php else: ?>
                    const defaultTab = 'tab-dashboard';
                    const defaultLink = document.querySelector(`a[onclick*='${defaultTab}']`);
                    showTab(defaultTab, defaultLink);
                <?php endif; ?>
            }
        });
    </script>
</body>
</html>