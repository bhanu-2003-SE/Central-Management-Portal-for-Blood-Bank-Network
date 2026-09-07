<?php
session_start();
require_once 'db.php';

// Oracle ඩේටාබේස් එකෙන් ඩෝනර්ලාගේ ලැයිස්තුව ලබා ගැනීම
try {
    $stmt = $conn->prepare("SELECT * FROM donors ORDER BY donor_id DESC");
    $stmt->execute();
    $donorsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ඩේටාබේස් එකේ ටේබල් එක තවම නැත්නම් ඩිෆෝල්ට් ඩේටා පෙන්වීමට
    $donorsList = [
        ['DONOR_ID' => 1, 'FULL_NAME' => 'Kasun Perera', 'BLOOD_GROUP' => 'O+', 'EMAIL' => 'kasun@gmail.com', 'PHONE' => '0771234567', 'CITY' => 'Colombo', 'CREATED_AT' => '2026-01-15'],
        ['DONOR_ID' => 2, 'FULL_NAME' => 'Nadeesha Silva', 'BLOOD_GROUP' => 'A-', 'EMAIL' => 'nadeesha@gmail.com', 'PHONE' => '0719876543', 'CITY' => 'Gampaha', 'CREATED_AT' => '2026-02-10'],
        ['DONOR_ID' => 3, 'FULL_NAME' => 'Mohamed Rinos', 'BLOOD_GROUP' => 'B+', 'EMAIL' => 'rinos@gmail.com', 'PHONE' => '0754433221', 'CITY' => 'Kandy', 'CREATED_AT' => '2025-11-20']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Donors Directory - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f0f4f8; background-image: radial-gradient(at 0% 0%, hsla(359, 100%, 74%, 0.15) 0px, transparent 50%), radial-gradient(at 100% 0%, hsla(204, 100%, 74%, 0.15) 0px, transparent 50%); color: #2b3674; display: flex; min-height: 100vh; }
        
        /* Sidebar Styling */
        .sidebar { width: 260px; background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%); padding: 24px; box-shadow: 4px 0 24px rgba(255, 75, 43, 0.2); display: flex; flex-direction: column; position: fixed; height: 100vh; color: white; z-index: 10; }
        .brand { display: flex; align-items: center; gap: 12px; font-size: 22px; font-weight: 800; margin-bottom: 40px; }
        .nav-links { list-style: none; display: flex; flex-direction: column; gap: 12px; }
        .nav-links li a { text-decoration: none; color: rgba(255, 255, 255, 0.85); font-weight: 600; padding: 14px 16px; border-radius: 12px; display: flex; align-items: center; gap: 12px; transition: all 0.3s ease; cursor: pointer; }
        .nav-links li a:hover, .nav-links li a.active { background: rgba(255, 255, 255, 0.2); color: white; transform: translateX(5px); }
        .logout-btn { background: rgba(255, 255, 255, 0.15) !important; color: white !important; margin-top: 20px; }
        .logout-btn:hover { background: rgba(255, 255, 255, 0.25) !important; transform: translateX(0) !important; }

        /* Main Content Area */
        .main-content { flex: 1; margin-left: 260px; padding: 32px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .topbar h1 { font-size: 26px; background: linear-gradient(135deg, #1A2980 0%, #26D0CE 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; }

        .card { background: white; padding: 28px; border-radius: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.04); }
        .card-header { font-size: 18px; font-weight: 800; color: #2b3674; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f8fafc; font-weight: 700; color: #64748b; }
        tr:hover { background: #f1f5f9; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; background: #fee2e2; color: #991b1b; }
        
        .donor-link { color: #2b3674; font-weight: 700; text-decoration: none; cursor: pointer; transition: color 0.2s; }
        .donor-link:hover { color: #FF416C; text-decoration: underline; }

        .btn-view { background: linear-gradient(135deg, #36D1DC 0%, #5B86E5 100%); color: white; border: none; padding: 8px 16px; border-radius: 10px; cursor: pointer; font-weight: 700; font-size: 13px; transition: all 0.3s; }
        .btn-view:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(54, 209, 220, 0.3); }

        /* Modal Styles */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 100; }
        .modal-content { background: white; padding: 36px; border-radius: 20px; width: 100%; max-width: 480px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); animation: scaleUp 0.3s; }
        @keyframes scaleUp { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .detail-row { display: flex; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .detail-label { font-weight: 600; color: #64748b; }
        .detail-value { font-weight: 700; color: #2b3674; }
        .btn-close { background: #cbd5e1; color: #334155; border: none; padding: 12px; width: 100%; border-radius: 10px; font-weight: 700; cursor: pointer; margin-top: 24px; transition: 0.2s; }
        .btn-close:hover { background: #94a3b8; }
    </style>
</head>
<body>

    <!-- වම්පස ස්ථාවර Sidebar එක -->
    <aside class="sidebar">
        <div class="brand">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg> LifeLine Connect
        </div>
        <ul class="nav-links">
            <li><a href="admin-dashboard.php" class="nav-btn">📊 Dashboard Overview</a></li>
            <li><a href="admin-dashboard.php" class="nav-btn">🩸 Blood Stock (Oracle)</a></li>
            <li><a href="admin-dashboard.php" class="nav-btn">🏥 Hospital Requests</a></li>
            <li><a href="admin-dashboard.php" class="nav-btn">⛺ Camps & Events</a></li>
            <li><a href="admin-donors.php" class="nav-btn active">👥 Donors (Oracle)</a></li>
            <li><a href="admin-dashboard.php" class="nav-btn">💬 Feedback (MongoDB)</a></li>
            <li><a href="admin-dashboard.php" class="nav-btn">📄 PL/SQL Reports</a></li>
            <li><a href="logout.php" class="logout-btn">🚪 Logout</a></li>
        </ul>
    </aside>

    <!-- දකුණුපස ප්‍රධාන අන්තර්ගතය -->
    <main class="main-content">
        <header class="topbar">
            <h1>Registered Donors Directory</h1>
            <div style="display: flex; align-items: center; gap: 12px; background: white; padding: 8px 20px; border-radius: 30px; font-weight: 600;">
                <span>System Admin</span>
                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);"></div>
            </div>
        </header>

        <div class="card">
            <div class="card-header"><span>👥</span> All Registered Donors List</div>
            <table>
                <tr>
                    <th>Donor ID</th>
                    <th>Full Name</th>
                    <th>Blood Group</th>
                    <th>City</th>
                    <th>Action</th>
                </tr>
                <?php if (empty($donorsList)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b; padding: 30px;">No registered donors found.</td>
                </tr>
                <?php else: foreach ($donorsList as $donor): 
                    $id = 'N/A';
                    $name = 'N/A';
                    $bGroup = 'N/A';
                    $email = 'N/A';
                    $phone = 'N/A';
                    $city = 'N/A';
                    $addedDate = 'N/A';

                    // සියලුම කෝලම් ස්වයංක්‍රීයව පරික්ෂා කර අගයන් ලබා ගැනීම
                    foreach ($donor as $key => $val) {
                        $kLower = strtolower($key);
                        
                        if (in_array($kLower, ['id', 'donor_id', 'donorid'])) $id = $val;
                        if (in_array($kLower, ['name', 'full_name', 'fullname', 'donor_name'])) $name = $val;
                        if (in_array($kLower, ['blood_group', 'bloodgroup', 'b_group', 'group'])) $bGroup = $val;
                        if (in_array($kLower, ['email', 'mail'])) $email = $val;
                        
                        // දුරකථන අංකය සඳහා විවිධ නම් (phone, mobile, tel, contact, tp, number ආදිය)
                        if (in_array($kLower, ['phone', 'mobile', 'contact', 'tel', 'telephone', 'phone_number', 'tp', 'number', 'p_no', 'contact_no'])) {
                            $phone = $val;
                        }
                        
                        if (in_array($kLower, ['city', 'location', 'address'])) $city = $val;
                        if (in_array($kLower, ['created_at', 'reg_date', 'registered_date', 'date', 'timestamp'])) $addedDate = $val;
                    }

                    // උඩින් සඳහන් කළ කිසිදු නමකින් හමු නොවී, අගය තුළ අංක (digits) පමණක් හෝ නම්බර් ස්වරූපයක් තිබේ නම් එය ස්වයංක්‍රීයව phone එක ලෙස ගැනීම
                    if ($phone == 'N/A') {
                        foreach ($donor as $key => $val) {
                            if (!empty($val) && preg_match('/^[0-9\+\-\s]{8,15}$/', trim($val))) {
                                $phone = $val;
                                break;
                            }
                        }
                    }
                ?>
                <tr>
                    <td>#<?php echo htmlspecialchars($id); ?></td>
                    <td>
                        <a class="donor-link" onclick="viewDonor('<?php echo htmlspecialchars($name); ?>', '<?php echo htmlspecialchars($bGroup); ?>', '<?php echo htmlspecialchars($email); ?>', '<?php echo htmlspecialchars($phone); ?>', '<?php echo htmlspecialchars($city); ?>', '<?php echo htmlspecialchars($addedDate); ?>')">
                            <?php echo htmlspecialchars($name); ?>
                        </a>
                    </td>
                    <td><span class="badge"><?php echo htmlspecialchars($bGroup); ?></span></td>
                    <td><?php echo htmlspecialchars($city); ?></td>
                    <td>
                        <button class="btn-view" onclick="viewDonor('<?php echo htmlspecialchars($name); ?>', '<?php echo htmlspecialchars($bGroup); ?>', '<?php echo htmlspecialchars($email); ?>', '<?php echo htmlspecialchars($phone); ?>', '<?php echo htmlspecialchars($city); ?>', '<?php echo htmlspecialchars($addedDate); ?>')">View Profile</button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </table>
        </div>
    </main>

    <!-- Donor Detailed Profile Modal -->
    <div id="donorModal" class="modal">
        <div class="modal-content">
            <h3 style="color: #2b3674; margin-bottom: 20px; font-size: 20px; display: flex; align-items: center; gap: 8px;"><span>👤</span> Donor Profile Details</h3>
            
            <div class="detail-row">
                <span class="detail-label">Full Name:</span>
                <span class="detail-value" id="modalName">-</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Blood Group:</span>
                <span class="detail-value" id="modalBlood" style="color: #ff416c;">-</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email Address:</span>
                <span class="detail-value" id="modalEmail">-</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone Number:</span>
                <span class="detail-value" id="modalPhone">-</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">City / Location:</span>
                <span class="detail-value" id="modalCity">-</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Registered Date:</span>
                <span class="detail-value" id="modalAddedDate">-</span>
            </div>

            <button type="button" class="btn-close" onclick="closeModal()">Close Profile</button>
        </div>
    </div>

    <script>
        function viewDonor(name, blood, email, phone, city, addedDate) {
            document.getElementById('modalName').innerText = name;
            document.getElementById('modalBlood').innerText = blood;
            document.getElementById('modalEmail').innerText = email;
            document.getElementById('modalPhone').innerText = phone;
            document.getElementById('modalCity').innerText = city;
            document.getElementById('modalAddedDate').innerText = addedDate;
            document.getElementById('donorModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('donorModal').style.display = 'none';
        }
    </script>
</body>
</html>