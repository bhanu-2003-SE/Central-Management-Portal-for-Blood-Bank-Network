<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db.php';

// රෝහලක් ලෙස ලොග් වී ඇත්දැයි පරීක්ෂා කිරීම
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hospital') {
    header("Location: hospital-login.php");
    exit();
}

$hospitalName = $_SESSION['hospital_name'] ?? 'Hospital Portal';
$successMsg = "";
$errorMsg = "";

// ඉල්ලීමක් සබ්මිට් කළ විට
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_request'])) {
    $bloodGroup = trim($_POST['blood_group']);
    $units = (int)($_POST['units']);
    $requestType = trim($_POST['request_type']);

    if (!empty($bloodGroup) && $units > 0) {
        try {
            // 1. Oracle ඩේටාබේස් එකේ 'hospital_requests' ටේබල් එකට ඇතුළත් කිරීම
            $stmt = $conn->prepare("INSERT INTO hospital_requests (hospital_name, blood_group, units, status) VALUES (:h_name, :b_group, :units, 'Pending')");
            $stmt->execute([
                ':h_name' => $hospitalName,
                ':b_group' => $bloodGroup,
                ':units' => $units
            ]);

            // 2. Request Type එක Emergency නම් MongoDB එකේ 'emergency_appeals' වෙත ඇතුළත් කිරීම
            if ($requestType === 'Emergency' && isset($mongoClient)) {
                $mongoClient->Blood_Bank->emergency_appeals->insertOne([
                    'hospital_name' => $hospitalName,
                    'blood_group' => $bloodGroup,
                    'units' => $units,
                    'message' => "URGENT! Need {$units} units of {$bloodGroup} blood group immediately for emergency cases.",
                    'created_at' => new MongoDB\BSON\UTCDateTime()
                ]);
            }

            $successMsg = "Blood request submitted successfully!";
        } catch (Exception $e) {
            $errorMsg = "Error: " . $e->getMessage();
        }
    } else {
        $errorMsg = "Please fill in all valid details.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Dashboard - LifeLine Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f0f4f8; background-image: radial-gradient(at 0% 0%, hsla(359, 100%, 74%, 0.15) 0px, transparent 50%), radial-gradient(at 100% 0%, hsla(204, 100%, 74%, 0.15) 0px, transparent 50%); color: #2b3674; display: flex; min-height: 100vh; }
        
        .sidebar { width: 260px; background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%); padding: 24px; box-shadow: 4px 0 24px rgba(255, 75, 43, 0.2); display: flex; flex-direction: column; position: fixed; height: 100vh; color: white; z-index: 10; }
        .brand { display: flex; align-items: center; gap: 12px; font-size: 22px; font-weight: 800; margin-bottom: 40px; }
        .nav-links { list-style: none; display: flex; flex-direction: column; gap: 12px; }
        .nav-links li a { text-decoration: none; color: rgba(255, 255, 255, 0.85); font-weight: 600; padding: 14px 16px; border-radius: 12px; display: flex; align-items: center; gap: 12px; transition: all 0.3s ease; cursor: pointer; }
        .nav-links li a:hover, .nav-links li a.active { background: rgba(255, 255, 255, 0.25); color: white; transform: translateX(5px); }
        .logout-btn { background: rgba(255, 255, 255, 0.15) !important; color: white !important; margin-top: auto; }
        .logout-btn:hover { background: rgba(255, 255, 255, 0.25) !important; transform: translateX(0) !important; }

        .main-content { flex: 1; margin-left: 260px; padding: 32px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .topbar h1 { font-size: 26px; background: linear-gradient(135deg, #1A2980 0%, #26D0CE 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; }
        
        .tab-content { display: none; animation: fadeIn 0.4s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .card { background: white; padding: 24px; border-radius: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.04); margin-bottom: 24px; }
        .card-header { font-size: 18px; font-weight: 800; color: #2b3674; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .text-gradient { background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .btn { background: linear-gradient(135deg, #36D1DC 0%, #5B86E5 100%); color: white; border: none; padding: 12px 20px; border-radius: 10px; cursor: pointer; font-weight: 700; width: 100%; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn:hover { opacity: 0.9; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f8fafc; font-weight: 700; color: #64748b; }
        tr:hover { background: #f1f5f9; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
        .badge-safe { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef08a; color: #854d0e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; }
        .form-group select, .form-group input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px; outline: none; }
        .alert-success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-weight: 600; font-size: 14px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg> LifeLine Connect
        </div>
        <ul class="nav-links">
            <li><a class="nav-btn active" onclick="showTab('tab-stock', this)">🩸 Blood Stock</a></li>
            <li><a class="nav-btn" onclick="showTab('tab-request', this)">🏥 Hospital Requests</a></li>
            <li><a class="nav-btn" onclick="showTab('tab-camps', this)">⛺ Camps & Events</a></li>
            <li><a href="logout.php" class="nav-btn logout-btn">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h1>Hospital Portal - <?php echo htmlspecialchars($hospitalName); ?></h1>
            <div style="display: flex; align-items: center; gap: 12px; background: white; padding: 8px 20px; border-radius: 30px; font-weight: 600;">
                <span>Hospital Admin</span>
                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);"></div>
            </div>
        </header>

 <!-- 1. BLOOD STOCK (INVENTORY) TAB -->
        <div id="tab-stock" class="tab-content active">
            <div class="card">
                <div class="card-header"><span class="text-gradient">🩸</span> Central Blood Inventory Status</div>
                
                <!-- Scrollbar එක සඳහා එකතු කළ div එක -->
                <div style="max-height: 500px; overflow-y: auto; overflow-x: hidden; padding-right: 8px;">
                    <table>
                        <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 1;">
                            <tr>
                                <th>Blood Group</th>
                                <th>Available Units</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        try {
                            $stockStmt = $conn->prepare("SELECT * FROM blood_stock ORDER BY blood_group ASC");
                            $stockStmt->execute();
                            $stockList = $stockStmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) { 
                            // යම් දෝෂයක් ආවොත් එය රතු පාටින් පෙන්වයි
                            echo "<tr><td colspan='3' style='color:red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            $stockList = []; 
                        }

                        if (empty($stockList)):
                        ?>
                            <tr><td colspan="3" style="text-align: center; color: #64748b;">No stock inventory found.</td></tr>
                        <?php else: foreach ($stockList as $stock): 
                            $bGroup = $stock['BLOOD_GROUP'] ?? $stock['blood_group'] ?? 'N/A';
                            $units = (int) ($stock['TOTAL_UNITS'] ?? $stock['total_units'] ?? 0);
                            $statusBadge = $units < 10 ? '<span class="badge badge-danger">Critical Low</span>' : ($units < 30 ? '<span class="badge badge-warning">Moderate</span>' : '<span class="badge badge-safe">Healthy Stock</span>');
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($bGroup); ?></strong></td>
                                <td><?php echo $units; ?> Pints</td>
                                <td><?php echo $statusBadge; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div> <!-- Scrollbar div එකෙහි අවසානය -->
            </div>
        </div>

        <!-- 2. HOSPITAL REQUESTS TAB (REQUEST & HISTORY) -->
        <div id="tab-request" class="tab-content">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <!-- Request Form -->
                <div class="card">
                    <div class="card-header"><span class="text-gradient">✍️</span> Request Blood Units</div>
                    
                    <?php if (!empty($successMsg)): ?>
                        <div class="alert-success"><?php echo $successMsg; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($errorMsg)): ?>
                        <div class="alert-error"><?php echo $errorMsg; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Blood Group Needed</label>
                            <select name="blood_group" required>
                                <option value="">-- Select Blood Group --</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Units Required (Pints)</label>
                            <input type="number" name="units" min="1" placeholder="e.g. 5" required>
                        </div>
                        <div class="form-group">
                            <label>Request Urgency Type</label>
                            <select name="request_type" required>
                                <option value="Normal">Normal Request</option>
                                <option value="Emergency">🚨 Emergency Appeal (Admin Alert)</option>
                            </select>
                        </div>
                        <button type="submit" name="submit_request" class="btn">Submit Request</button>
                    </form>
                </div>

                <!-- Request History -->
                <div class="card">
                    <div class="card-header"><span class="text-gradient">📋</span> Request History</div>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Group</th>
                            <th>Units</th>
                            <th>Status</th>
                        </tr>
                        <?php
                        try {
                            $reqStmt = $conn->prepare("SELECT * FROM hospital_requests WHERE hospital_name = :h_name ORDER BY request_id DESC");
                            $reqStmt->execute([':h_name' => $hospitalName]);
                            $myRequests = $reqStmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) { $myRequests = []; }

                        if (empty($myRequests)):
                        ?>
                            <tr><td colspan="4" style="text-align: center; color: #64748b;">No requests found.</td></tr>
                        <?php else: foreach ($myRequests as $req): 
                            $status = $req['STATUS'] ?? $req['status'] ?? 'Pending';
                            $statusBadge = ($status == 'Approved') ? '<span class="badge badge-safe">Approved</span>' : (($status == 'Rejected') ? '<span class="badge badge-danger">Rejected</span>' : '<span class="badge badge-warning">Pending</span>');
                        ?>
                            <tr>
                                <td>REQ-<?php echo htmlspecialchars($req['REQUEST_ID'] ?? $req['request_id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($req['BLOOD_GROUP'] ?? $req['blood_group']); ?></strong></td>
                                <td><?php echo htmlspecialchars($req['UNITS'] ?? $req['units']); ?></td>
                                <td><?php echo $statusBadge; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </table>
                </div>
            </div>
        </div>

<!-- 3. CAMPS & EVENTS TAB -->
        <div id="tab-camps" class="tab-content">
            <div class="card">
                <div class="card-header"><span class="text-gradient">⛺</span> Upcoming Blood Donation Camps</div>
                <div style="max-height: 500px; overflow-y: auto; overflow-x: hidden; padding-right: 8px;">
                    <table>
                        <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 1;">
                            <tr>
                                <th>Camp Name</th>
                                <th>Venue / Location</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        try {
                            // start_date වෙනුවට Admin පැනල් එකේ මෙන් camp_id එකෙන් Order කර ඇත
                            $campsList = $conn->query("SELECT * FROM camps ORDER BY camp_id DESC")->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) { 
                            // යම් දෝෂයක් ආවොත් එය රතු පාටින් පෙන්වයි
                            echo "<tr><td colspan='3' style='color:red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            $campsList = []; 
                        }

                        if (empty($campsList)):
                        ?>
                            <tr><td colspan="3" style="text-align: center; color: #64748b;">No upcoming camps scheduled.</td></tr>
                        <?php else: foreach ($campsList as $camp): 
                            $cName = $camp['CAMP_NAME'] ?? $camp['camp_name'] ?? '';
                            $cVenue = $camp['VENUE'] ?? $camp['venue'] ?? '';
                            $cDate = $camp['START_DATE'] ?? $camp['start_date'] ?? $camp['CAMP_DATE'] ?? $camp['camp_date'] ?? '';
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cName); ?></strong></td>
                                <td>📍 <?php echo htmlspecialchars($cVenue); ?></td>
                                <td>📅 <?php echo htmlspecialchars($cDate); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>

    <script>
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
        }

        window.addEventListener('DOMContentLoaded', () => {
            <?php if (isset($_POST['submit_request'])): ?>
                // ඉල්ලීමක් සබ්මිට් කළ පසු නැවත Hospital Requests ටැබ് එක පෙන්වීම
                const reqBtn = document.querySelector("a[onclick*='tab-request']");
                showTab('tab-request', reqBtn);
            <?php else: ?>
                // වෙනත් අවස්ථාවලදී මුලින්ම Blood Stock පෙන්වීම
                const defaultTab = 'tab-stock';
                const defaultLink = document.querySelector(`a[onclick*='${defaultTab}']`);
                showTab(defaultTab, defaultLink);
            <?php endif; ?>
        });
    </script>
</body>
</html>