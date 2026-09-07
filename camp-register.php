<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db.php';

// Login වෙලා නැත්නම් login පිටුවට යවනවා
if (!isset($_SESSION['donor_id'])) {
    header("Location: login.php");
    exit();
}

$camp_id = isset($_GET['camp_id']) ? $_GET['camp_id'] : 1;
$camp_name = "Unknown Camp";
$camp_date = "TBD";
$camp_venue = "TBD";
$camp_time = "08:30 AM - 02:00 PM";

// Database එකෙන් තෝරාගත් කඳවුරේ විස්තර නිවැරදිව ලබා ගැනීම (වෙනස් කالم නේම්ස් පරීක්ෂා කරමින්)
try {
    $stmt = $conn->prepare("SELECT * FROM camps WHERE camp_id = :camp_id");
    $stmt->execute([':camp_id' => $camp_id]);
    $campInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($campInfo) {
        // විවිධ විය හැකි කالم නාමයන් සියල්ල පරීක්ෂා කිරීම
        $camp_name = $campInfo['CAMP_NAME'] ?? $campInfo['camp_name'] ?? $campInfo['NAME'] ?? $campInfo['name'] ?? $campInfo['CAMP_TITLE'] ?? "Unknown Camp";
        $camp_venue = $campInfo['VENUE'] ?? $campInfo['venue'] ?? $campInfo['LOCATION'] ?? $campInfo['location'] ?? "TBD";
        
        $db_date = $campInfo['CAMP_DATE'] ?? $campInfo['camp_date'] ?? $campInfo['START_DATE'] ?? $campInfo['start_date'] ?? '';
        $camp_date = !empty($db_date) ? date('D, d M Y', strtotime($db_date)) : "TBD";
        
        $start_time = $campInfo['START_TIME'] ?? $campInfo['start_time'] ?? "08:30 AM";
        $end_time = $campInfo['END_TIME'] ?? $campInfo['end_time'] ?? "02:00 PM";
        $camp_time = "$start_time - $end_time";
    }
} catch (PDOException $e) {
    // දෝෂයක් මතු වුවහොත්
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LifeLine Connect — Camp Appointment Registration</title>
<!-- Google Fonts: Plus Jakarta Sans -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --primary-red: #FF416C;
    --primary-gradient: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);
    --bg-light: #f0f4f8;
    --text-main: #2b3674;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  }

  * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }

  body {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--bg-light);
    background-image: 
        radial-gradient(at 0% 0%, hsla(359, 100%, 74%, 0.15) 0px, transparent 50%),
        radial-gradient(at 100% 100%, hsla(204, 100%, 74%, 0.15) 0px, transparent 50%);
    padding: 20px;
  }

  /* Split Screen Wrapper */
  .booking-wrapper {
    width: 100%;
    max-width: 950px;
    background: #FFFFFF;
    border-radius: 24px;
    display: flex;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
  }

  /* Left Panel (Camp Info) */
  .info-panel {
    width: 40%;
    background: var(--primary-gradient);
    padding: 40px;
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
  }

  .info-panel::after {
    content: '';
    position: absolute;
    bottom: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
  }

  .brand-logo-text {
    font-size: 1.5rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 2;
  }

  .camp-details-box {
    z-index: 2;
    margin: 30px 0;
  }

  .camp-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
  }

  .camp-title {
    font-size: 1.6rem;
    font-weight: 800;
    line-height: 1.3;
    margin-bottom: 16px;
  }

  .camp-meta {
    font-size: 0.9rem;
    display: flex;
    flex-direction: column;
    gap: 8px;
    opacity: 0.95;
    font-weight: 600;
  }

  /* Right Panel (Form Area) */
  .form-panel {
    width: 60%;
    padding: 40px;
    background: #FFFFFF;
    max-height: 90vh;
    overflow-y: auto;
  }

  .section-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--text-main);
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  /* TIME SLOTS GRID */
  .slot-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 24px;
  }

  .slot-option input[type="radio"] { display: none; }

  .slot-label {
    display: block;
    padding: 12px 8px;
    text-align: center;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    background: #f8fafc;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--text-main);
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .slot-option input[type="radio"]:checked + .slot-label {
    border-color: var(--primary-red);
    background: #FFFFFF;
    color: var(--primary-red);
    box-shadow: 0 0 0 4px rgba(255, 65, 108, 0.15);
  }

  /* HEALTH CHECKLIST */
  .checklist-group {
    background: #f8fafc;
    border: 2px solid var(--border-color);
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 24px;
  }

  .check-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.85rem;
    color: var(--text-main);
    font-weight: 600;
    cursor: pointer;
    line-height: 1.4;
  }

  .check-item:last-child { border-bottom: none; padding-bottom: 0; }
  .check-item:first-child { padding-top: 0; }

  .check-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary-red);
    cursor: pointer;
    margin-top: 1px;
  }

  /* BUTTONS */
  .action-btns {
    display: flex;
    gap: 12px;
  }

  .btn {
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    border: none;
    transition: all 0.3s;
  }

  .btn-secondary {
    background: #f1f5f9;
    color: var(--text-main);
    border: 2px solid var(--border-color);
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .btn-secondary:hover { background: #e2e8f0; }

  .btn-primary {
    background: var(--primary-gradient);
    color: white;
    flex: 1;
    box-shadow: 0 6px 15px rgba(255, 65, 108, 0.25);
  }

  .btn-primary:hover { 
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(255, 65, 108, 0.35); 
  }

  /* SUCCESS MODAL POPUP */
  .modal-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(43, 54, 116, 0.7);
    backdrop-filter: blur(8px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 100;
  }

  .modal-overlay.active { display: flex; animation: fadeIn 0.3s ease; }

  .modal-card {
    background: #FFFFFF;
    border-radius: 24px;
    padding: 40px;
    max-width: 450px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
  }

  .success-icon {
    width: 70px; height: 70px;
    background: var(--success-gradient);
    color: white;
    border-radius: 50%; 
    display: inline-flex;
    align-items: center; justify-content: center;
    font-size: 2rem; margin-bottom: 20px;
    box-shadow: 0 8px 20px rgba(56, 239, 125, 0.3);
  }

  .modal-title {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--text-main);
    margin-bottom: 8px;
  }

  .pass-code {
    background: #f8fafc;
    border: 2px solid var(--border-color);
    padding: 16px;
    border-radius: 12px;
    font-family: monospace;
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--primary-red);
    letter-spacing: 3px;
    margin: 20px 0;
  }

  @media (max-width: 768px) {
    .booking-wrapper { flex-direction: column; }
    .info-panel { width: 100%; padding: 30px; }
    .form-panel { width: 100%; padding: 30px 20px; }
  }
</style>
</head>
<body>

<div class="booking-wrapper">
  
  <!-- Left Panel: Camp Info Banner -->
  <div class="info-panel">
    <div class="brand-logo-text">
        <span style="font-size: 24px;">♥</span> LifeLine Connect
    </div>
    
    <div class="camp-details-box">
      <span class="camp-badge">Selected Blood Drive</span>
      <h2 class="camp-title"><?php echo htmlspecialchars($camp_name); ?></h2>
      <div class="camp-meta">
        <span>📅 <?php echo $camp_date; ?></span>
        <span>⏰ <?php echo htmlspecialchars($camp_time); ?></span>
        <span>📍 <?php echo htmlspecialchars($camp_venue); ?></span>
      </div>
    </div>

    <div style="font-size: 0.85rem; opacity: 0.8; z-index: 2;">Secure Appointment Portal</div>
  </div>

  <!-- Right Panel: Booking Form -->
  <div class="form-panel">
    <form id="bookingForm" action="book_slot_backend.php" method="POST" onsubmit="handleSlotBooking(event)">
      
      <input type="hidden" name="camp_id" value="<?php echo htmlspecialchars($camp_id); ?>">

      <!-- Time Slot Selection -->
      <div class="section-title">⏱️ Select Arrival Time</div>
      <div class="slot-grid">
        <label class="slot-option">
          <input type="radio" name="timeSlot" value="08:30 AM - 09:30 AM" required>
          <span class="slot-label">08:30 - 09:30 AM</span>
        </label>
        <label class="slot-option">
          <input type="radio" name="timeSlot" value="09:30 AM - 10:30 AM">
          <span class="slot-label">09:30 - 10:30 AM</span>
        </label>
        <label class="slot-option">
          <input type="radio" name="timeSlot" value="10:30 AM - 11:30 AM">
          <span class="slot-label">10:30 - 11:30 AM</span>
        </label>
        <label class="slot-option">
          <input type="radio" name="timeSlot" value="11:30 AM - 12:30 PM">
          <span class="slot-label">11:30 - 12:30 PM</span>
        </label>
      </div>

      <!-- Health Declaration Check -->
      <div class="section-title">📋 Health Quick Check</div>
      <div class="checklist-group">
        <label class="check-item">
          <input type="checkbox" class="health-chk" required>
          I have slept at least 6 hours last night.
        </label>
        <label class="check-item">
          <input type="checkbox" class="health-chk" required>
          I weigh more than 50 kg and feel healthy today.
        </label>
        <label class="check-item">
          <input type="checkbox" class="health-chk" required>
          I am not taking heavy antibiotic medications.
        </label>
        <label class="check-item">
          <input type="checkbox" class="health-chk" required>
          No major surgeries in the last 6 months.
        </label>
      </div>

      <div class="action-btns">
        <a href="user-dashboard.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Confirm Slot</button>
      </div>

    </form>
  </div>

</div>

<!-- Confirmation Popup Modal -->
<div class="modal-overlay" id="successModal">
  <div class="modal-card">
    <div class="success-icon">✓</div>
    <h3 class="modal-title">Slot Confirmed!</h3>
    <p style="font-size: 0.95rem; color: var(--text-muted); font-weight: 500;">Your donation appointment is locked in successfully.</p>
    
    <div class="pass-code" id="passCode">LLC-2026-XXXX</div>

    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 24px; font-weight: 600;">Show this Donor Pass Code at the reception desk upon arrival.</p>
    
    <button class="btn btn-primary" onclick="redirectToDashboard()" style="width: 100%;">Back to Dashboard</button>
  </div>
</div>

<script>
  function handleSlotBooking(event) {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('booking') === 'success') {
      event.preventDefault();
      const code = urlParams.get('code') || 'LLC-2026-' + Math.floor(1000 + Math.random() * 9000);
      document.getElementById('passCode').innerText = code;
      document.getElementById('successModal').classList.add('active');
    }
  }

  window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('booking') === 'success') {
      const code = urlParams.get('code') || 'LLC-2026-8942';
      document.getElementById('passCode').innerText = code;
      document.getElementById('successModal').classList.add('active');
    }
  };

  function redirectToDashboard() {
    window.location.href = 'user-dashboard.php';
  }
</script>

</body>
</html>