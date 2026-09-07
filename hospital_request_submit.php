<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hospital') {
    header("Location: hospital_login.php");
    exit();
}
require_once 'db.php';

// Oracle ඩේටාබේස් එකෙන් වර්තමාන රුධිර තොගය (Blood Stock) ලබා ගැනීම
try {
    $stockStmt = $conn->prepare("SELECT * FROM blood_stock");
    $stockStmt->execute();
    $stockList = $stockStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ඩේටාබේස් එකේ ටේබල් එක තවම නැත්නම් ඩිෆෝල්ට් අගයන් පෙන්වීමට
    $stockList = [
        ['BLOOD_GROUP' => 'O+', 'TOTAL_UNITS' => 210, 'STATUS' => 'Healthy Stock'],
        ['BLOOD_GROUP' => 'O-', 'TOTAL_UNITS' => 2, 'STATUS' => 'Critical Low'],
        ['BLOOD_GROUP' => 'A+', 'TOTAL_UNITS' => 150, 'STATUS' => 'Healthy Stock'],
        ['BLOOD_GROUP' => 'A-', 'TOTAL_UNITS' => 8, 'STATUS' => 'Low Stock'],
        ['BLOOD_GROUP' => 'B+', 'TOTAL_UNITS' => 120, 'STATUS' => 'Healthy Stock'],
        ['BLOOD_GROUP' => 'B-', 'TOTAL_UNITS' => 5, 'STATUS' => 'Critical Low'],
        ['BLOOD_GROUP' => 'AB+', 'TOTAL_UNITS' => 45, 'STATUS' => 'Normal'],
        ['BLOOD_GROUP' => 'AB-', 'TOTAL_UNITS' => 3, 'STATUS' => 'Critical Low']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Live Inventory & Request Portal - LifeLine Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f0f4f8; background-image: radial-gradient(at 0% 0%, hsla(359, 100%, 74%, 0.15) 0px, transparent 50%), radial-gradient(at 100% 0%, hsla(204, 100%, 74%, 0.15) 0px, transparent 50%); color: #2b3674; min-height: 100vh; padding: 32px; }
        
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 24px 32px; border-radius: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.04); margin-bottom: 24px; }
        .header h1 { font-size: 22px; font-weight: 800; color: #2b3674; }
        .hospital-badge { background: #f1f5f9; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; color: #475569; }
        
        .card { background: white; padding: 28px; border-radius: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.04); margin-bottom: 24px; }
        .card-header { font-size: 18px; font-weight: 800; color: #2b3674; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .text-gradient { background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 700; color: #64748b; }
        tr:hover { background: #f1f5f9; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .badge-safe { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef08a; color: #854d0e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }

        .btn { background: linear-gradient(135deg, #36D1DC 0%, #5B86E5 100%); color: white; border: none; padding: 8px 16px; border-radius: 10px; cursor: pointer; font-weight: 700; transition: all 0.3s; font-size: 13px; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(54, 209, 220, 0.3); }
        .logout-btn { color: #ff416c; text-decoration: none; font-weight: 700; font-size: 14px; }

        /* Modal Styles */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 100; }
        .modal-content { background: white; padding: 32px; border-radius: 20px; width: 100%; max-width: 450px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); animation: scaleUp 0.3s; }
        @keyframes scaleUp { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: #2b3674; }
        .form-group select, .form-group input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px; outline: none; font-size: 14px; }
        .modal-actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn-red { background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); flex: 1; padding: 12px; }
        .btn-secondary { background: #cbd5e1; color: #334155; flex: 1; padding: 12px; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <div>
                <h1>🏥 Live Blood Inventory Portal</h1>
                <p style="font-size: 13px; color: #64748b; margin-top: 4px;">Select a blood group to request emergency units instantly.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 20px;">
                <span class="hospital-badge"><?php echo $_SESSION['hospital_name']; ?></span>
                <a href="logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="text-gradient">🩸</span> Central Stock Availability Matrix</div>
            <table>
                <tr>
                    <th>Blood Group</th>
                    <th>Available Units</th>
                    <th>Stock Status</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($stockList as $stock): 
                    $bGroup = $stock['BLOOD_GROUP'] ?? $stock['blood_group'];
                    $units = $stock['TOTAL_UNITS'] ?? $stock['total_units'];
                    $status = $stock['STATUS'] ?? $stock['status'];
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($bGroup); ?></strong></td>
                    <td><?php echo htmlspecialchars($units); ?> Pints</td>
                    <td>
                        <?php if($units > 20): ?>
                            <span class="badge badge-safe">Healthy Stock</span>
                        <?php elseif($units > 5): ?>
                            <span class="badge badge-warning">Low Stock</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Critical Low</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn" onclick="openRequestModal('<?php echo htmlspecialchars($bGroup); ?>')">Request Units</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- Request Popup Modal -->
    <div id="requestModal" class="modal">
        <div class="modal-content">
            <h3 style="color: #2b3674; margin-bottom: 20px;">Request Blood Units</h3>
            <form action="hospital_request_submit.php" method="POST">
                <input type="hidden" name="hospital_name" value="<?php echo $_SESSION['hospital_name']; ?>">
                
                <div class="form-group">
                    <label>Selected Blood Group</label>
                    <input type="text" id="modalBloodGroup" name="blood_group" readonly style="background: #f8fafc; font-weight: bold;">
                </div>
                
                <div class="form-group">
                    <label>Units Needed (Pints)</label>
                    <input type="number" name="units" min="1" required placeholder="e.g. 3">
                </div>
                
                <div class="form-group">
                    <label>Urgency Level</label>
                    <select name="urgency" required>
                        <option value="High/Urgent">High / Urgent</option>
                        <option value="Normal">Normal</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeRequestModal()">Cancel</button>
                    <button type="submit" class="btn btn-red">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRequestModal(bloodGroup) {
            document.getElementById('modalBloodGroup').value = bloodGroup;
            document.getElementById('requestModal').style.display = 'flex';
        }

        function closeRequestModal() {
            document.getElementById('requestModal').style.display = 'none';
        }
    </script>
</body>
</html>