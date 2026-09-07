<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db.php';

// Login එක හරහා Session එකට donor_id එකක් ඇවිත් නැත්නම්, කෙලින්ම Login පිටුවට හරවා යැවීම
if (!isset($_SESSION['donor_id']) || empty($_SESSION['donor_id'])) {
    header("Location: login.php");
    exit();
}

$donor_id = $_SESSION['donor_id'];

// 1. Oracle: පරිශීලකයාගේ නම, රුධිර ගණ්ඩය සහ දිස්ත්‍රික්කය ලබා ගැනීම
try {
    $stmtUser = $conn->prepare("SELECT full_name, blood_group, district FROM donors WHERE donor_id = :donor_id");
    $stmtUser->execute([':donor_id' => $donor_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    $user_name = $user['FULL_NAME'] ?? $user['full_name'] ?? 'Unknown User';
    $user_bg = $user['BLOOD_GROUP'] ?? $user['blood_group'] ?? 'N/A';
    $user_district = trim($user['DISTRICT'] ?? $user['district'] ?? 'Colombo'); 
    $user_initial = strtoupper(substr($user_name, 0, 1));
} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}

// 2. Oracle: පරිත්‍යාග ඉතිහාසය ලබා ගැනීම (start_date භාවිතා කරමින්)
$stmtHistory = $conn->prepare("SELECT a.appointment_id, c.camp_name, c.start_date, a.status 
                               FROM camp_appointments a 
                               JOIN camps c ON a.camp_id = c.camp_id 
                               WHERE a.donor_id = :donor_id 
                               ORDER BY c.start_date DESC");
$stmtHistory->execute([':donor_id' => $donor_id]);
$donations = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
$total_donations = count($donations);
$lives_saved = $total_donations * 3;

// ස්වයංක්‍රීයව ඊළඟට ලේ දිය හැකි දිනය ගණනය කිරීම (මාස 4කට පසු)
$next_eligible_date = "Eligible Now";
if ($total_donations > 0) {
    $last_donation_date = $donations[0]['START_DATE'] ?? $donations[0]['start_date'];
    $next_eligible_date = date('M d, Y', strtotime('+4 months', strtotime($last_donation_date)));
}

// 3. Oracle: ඉදිරි කඳවුරු (Upcoming Camps) ලබා ගැනීම
$stmtCamps = $conn->prepare("SELECT * FROM camps WHERE start_date >= TO_CHAR(CURRENT_DATE, 'YYYY-MM-DD') ORDER BY start_date ASC");
$stmtCamps->execute();
$camps = $stmtCamps->fetchAll(PDO::FETCH_ASSOC);

// 4. පරිශීලකයාගේ දිස්ත්‍රික්කයට අදාළ ආසන්න කඳවුරු (Nearby Camps) පෙරීම
$nearby_camps = [];
foreach ($camps as $camp) {
    $venue = $camp['VENUE'] ?? $camp['venue'] ?? '';
    $cName = $camp['CAMP_NAME'] ?? $camp['camp_name'] ?? '';
    if (empty($user_district) || stripos($venue, $user_district) !== false || stripos($cName, $user_district) !== false) {
        $nearby_camps[] = $camp;
    }
}
if (empty($nearby_camps)) {
    $nearby_camps = $camps;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeLine Connect - Donor Dashboard</title>
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
        .nav-links li a:hover, .nav-links li a.active { background: rgba(255, 255, 255, 0.2); color: white; transform: translateX(5px); }
        .logout-btn { background: rgba(255, 255, 255, 0.15) !important; color: white !important; margin-top: 20px; }
        .logout-btn:hover { background: rgba(255, 255, 255, 0.25) !important; transform: translateX(0) !important; }
        
        .main-content { flex: 1; margin-left: 260px; padding: 32px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .topbar h1 { font-size: 26px; background: linear-gradient(135deg, #1A2980 0%, #26D0CE 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; }
        .user-profile { display: flex; align-items: center; gap: 12px; background: white; padding: 8px 20px; border-radius: 30px; font-weight: 600; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        .tab-content { display: none; animation: fadeIn 0.4s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .card { background: white; padding: 24px; border-radius: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.04); margin-bottom: 24px; }
        .card-header { font-size: 18px; font-weight: 800; color: #2b3674; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .text-gradient { background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .btn { background: linear-gradient(135deg, #36D1DC 0%, #5B86E5 100%); color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-weight: 700; transition: all 0.3s; display: inline-block; text-align: center; text-decoration: none;}
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(91, 134, 229, 0.4); }
        .btn-red { background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 700; color: #64748b; }
        tr:hover { background: #f1f5f9; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .badge-safe { background: #dcfce7; color: #166534; }
        
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 32px; }
        .kpi-card { padding: 24px; border-radius: 20px; display: flex; align-items: center; gap: 16px; color: white; box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .bg-red { background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); }
        .bg-blue { background: linear-gradient(135deg, #36D1DC 0%, #5B86E5 100%); }
        .bg-yellow { background: linear-gradient(135deg, #FDC830 0%, #F37335 100%); }
        .bg-purple { background: linear-gradient(135deg, #8E2DE2 0%, #4A00E0 100%); }
        .kpi-icon { width: 52px; height: 52px; border-radius: 50%; background: rgba(255, 255, 255, 0.25); display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px; }
        
        #map { height: 350px; width: 100%; border-radius: 16px; z-index: 1; border: 2px solid #f0f4f8; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; }
        .form-group select, .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px; outline: none; }

        /* Scrollbar Styling for webkit browsers */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg> LifeLine Connect
        </div>
        <ul class="nav-links">
            <li><a class="nav-btn active" onclick="showTab('tab-my-dash', this)">👤 My Profile</a></li>
            <li><a class="nav-btn" onclick="showTab('tab-history', this)">🩸 Donation History</a></li>
            <li><a class="nav-btn" onclick="showTab('tab-find-camps', this)">📍 Find Camps (<?php echo htmlspecialchars($user_district); ?>)</a></li>
            <li><a class="nav-btn" onclick="showTab('tab-add-feedback', this)">✍️ Submit Feedback</a></li>
            <li><a class="nav-btn" onclick="showTab('tab-rewards', this)">🏆 Certificates</a></li>
            <li><a href="logout.php" class="logout-btn">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h1 id="page-title">Donor Portal</h1>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($user_name) . " (" . htmlspecialchars($user_bg) . ")"; ?></span>
                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #36D1DC 0%, #5B86E5 100%); display:flex; justify-content:center; align-items:center; color:white;">
                    <?php echo $user_initial; ?>
                </div>
            </div>
        </header>

        <!-- 1. MY PROFILE -->
        <div id="tab-my-dash" class="tab-content active">
            <div class="kpi-grid">
                <div class="kpi-card bg-red">
                    <div class="kpi-icon">🩸</div><div><p style="font-size: 13px;">Total Donations</p><h3 style="font-size: 24px;"><?php echo $total_donations; ?> Times</h3></div>
                </div>
                <div class="kpi-card bg-blue">
                    <div class="kpi-icon">❤️</div><div><p style="font-size: 13px;">Estimated Lives Saved</p><h3 style="font-size: 24px;"><?php echo $lives_saved; ?> Lives</h3></div>
                </div>
                <div class="kpi-card bg-yellow">
                    <div class="kpi-icon">📅</div><div><p style="font-size: 13px;">Next Eligible Date</p><h3 style="font-size: 24px;"><?php echo $next_eligible_date; ?></h3></div>
                </div>
                <div class="kpi-card bg-purple">
                    <div class="kpi-icon">💉</div><div><p style="font-size: 13px;">My Blood Group</p><h3 style="font-size: 24px;"><?php echo htmlspecialchars($user_bg); ?></h3></div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header"><span class="text-gradient">🔔</span> Recent Activity</div>
                    <?php if ($total_donations > 0): 
                        $recent_date = $donations[0]['START_DATE'] ?? $donations[0]['start_date'];
                    ?>
                        <div style="border-left: 4px solid #38ef7d; padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 12px;">
                            <h4 style="color: #2b3674;">Successfully Donated at <?php echo htmlspecialchars($donations[0]['CAMP_NAME'] ?? $donations[0]['camp_name']); ?></h4>
                            <p style="font-size: 13px; color: #64748b; margin-top: 4px;"><?php echo date('F d, Y', strtotime($recent_date)); ?> • 450ml Whole Blood</p>
                        </div>
                    <?php else: ?>
                        <p style="color: #64748b;">No recent activity found. Book your first camp today!</p>
                    <?php endif; ?>
                    <button class="btn" onclick="showTab('tab-history', document.querySelectorAll('.nav-btn')[1])">View Full History</button>
                </div>
            </div>
        </div>

        <!-- 2. DONATION HISTORY -->
        <div id="tab-history" class="tab-content">
            <div class="card">
                <div class="card-header"><span class="text-gradient">🩸</span> My Donation Records</div>
                <table>
                    <tr><th>Donation ID</th><th>Date</th><th>Camp / Location</th><th>Volume</th><th>Status</th></tr>
                    <?php foreach ($donations as $donation): 
                        $d_date = $donation['START_DATE'] ?? $donation['start_date'];
                    ?>
                        <tr>
                            <td><?php echo "DN-00" . htmlspecialchars($donation['APPOINTMENT_ID'] ?? $donation['appointment_id']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($d_date)); ?></td>
                            <td><?php echo htmlspecialchars($donation['CAMP_NAME'] ?? $donation['camp_name']); ?></td>
                            <td>450 ml</td>
                            <td><span class="badge badge-safe"><?php echo htmlspecialchars($donation['STATUS'] ?? $donation['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($donations)): ?>
                        <tr><td colspan="5" style="text-align:center; color:#64748b;">No donation history available.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- 3. FIND CAMPS (NEARBY LOCATIONS) -->
        <div id="tab-find-camps" class="tab-content">
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header"><span class="text-gradient">📍</span> Live Camps Map (<?php echo htmlspecialchars($user_district); ?> & Islandwide)</div>
                    <div id="map"></div>
                </div>
                <div class="card">
                    <div class="card-header"><span class="text-gradient">📅</span> Book Appointment</div>
                    
                    <!-- Scrollbar එක සඳහා එකතු කළ div එක -->
                    <div style="max-height: 400px; overflow-y: auto; overflow-x: hidden; padding-right: 10px;">
                        <?php foreach ($nearby_camps as $camp): 
                            $c_name = $camp['CAMP_NAME'] ?? $camp['camp_name'] ?? 'Unknown Camp';
                            $c_venue = $camp['VENUE'] ?? $camp['venue'] ?? '';
                            $c_date = $camp['START_DATE'] ?? $camp['start_date'] ?? '';
                            $c_id = $camp['CAMP_ID'] ?? $camp['camp_id'] ?? '';
                        ?>
                            <div style="background: #f8fafc; padding: 15px; border-left: 4px solid #36D1DC; border-radius: 8px; margin-bottom: 15px;">
                                <h4><?php echo htmlspecialchars($c_name); ?></h4>
                                <p style="font-size: 13px; color: #64748b; margin-bottom: 10px;"><?php echo htmlspecialchars($c_venue); ?> • <?php echo !empty($c_date) ? date('M d, Y', strtotime($c_date)) : ''; ?></p>
                                <!-- camp_id එක URL එක හරහා නිවැරදිව පාස් කිරීම -->
                                <a href="camp-register.php?camp_id=<?php echo urlencode($c_id); ?>" class="btn" style="width: 100%; font-size: 13px;">Book Slot</a>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($nearby_camps)): ?>
                            <p style="color: #64748b; text-align: center;">No camps scheduled in your district at this time.</p>
                        <?php endif; ?>
                    </div> <!-- Scrollbar div එකෙහි අවසානය -->

                </div>
            </div>
        </div>

        <!-- 4. SUBMIT FEEDBACK -->
        <div id="tab-add-feedback" class="tab-content">
            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <div class="card-header"><span class="text-gradient">✍️</span> Share Your Experience</div>
                <form action="submit_feedback.php" method="POST">
                    <div class="form-group">
                        <label>Select Camp</label>
                        <select name="camp_name" required>
                            <option value="">-- Choose a camp --</option>
                            <?php foreach ($camps as $camp): ?>
                                <option value="<?php echo htmlspecialchars($camp['CAMP_NAME'] ?? $camp['camp_name']); ?>"><?php echo htmlspecialchars($camp['CAMP_NAME'] ?? $camp['camp_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Rating</label>
                        <select name="rating" required>
                            <option value="5">⭐⭐⭐⭐⭐ - Excellent</option>
                            <option value="4">⭐⭐⭐⭐ - Good</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Review</label>
                        <textarea name="review" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn" style="width: 100%;">Submit</button>
                </form>
            </div>
        </div>

        <!-- 5. CERTIFICATES -->
        <div id="tab-rewards" class="tab-content">
            <div class="card">
                <div class="card-header"><span class="text-gradient">🏆</span> Appreciation Certificate</div>
                <div style="border: 8px solid #f1f5f9; padding: 40px; text-align: center; border-radius: 12px; max-width: 700px; margin: 0 auto;">
                    <h2 style="color: #2b3674; font-size: 28px;">Certificate of Appreciation</h2>
                    <h1 style="color: #d32f2f; font-size: 32px; margin: 20px 0; border-bottom: 2px solid #e2e8f0; display: inline-block;">
                        <?php echo htmlspecialchars($user_name); ?>
                    </h1>
                    <p style="color: #64748b;">For donating blood <strong><?php echo $total_donations; ?> times</strong> through LifeLine Connect.</p>
                </div>
            </div>
        </div>
    </main>

    <script>
        var map = L.map('map').setView([7.8731, 80.7718], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        <?php 
        foreach($nearby_camps as $index => $camp): 
            $c_name = $camp['CAMP_NAME'] ?? $camp['camp_name'];
            $c_date = $camp['START_DATE'] ?? $camp['start_date'];
            
            $lat = $camp['LATITUDE'] ?? $camp['latitude'] ?? null;
            $lng = $camp['LONGITUDE'] ?? $camp['longitude'] ?? null;
            
            if ($lat && $lng && $lat !== 'null' && $lng !== 'null'):
        ?>
            L.marker([<?php echo $lat; ?>, <?php echo $lng; ?>]).addTo(map)
             .bindPopup("<b><?php echo addslashes(htmlspecialchars($c_name)); ?></b><br><?php echo date('M d, Y', strtotime($c_date)); ?>");
        <?php 
            endif;
        endforeach; 
        ?>

        function showTab(tabId, clickedElement) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(l => l.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            if(clickedElement) clickedElement.classList.add('active');
            
            if(tabId === 'tab-find-camps') {
                setTimeout(() => { map.invalidateSize(); }, 100);
            }
        }
    </script>
</body>
</html>