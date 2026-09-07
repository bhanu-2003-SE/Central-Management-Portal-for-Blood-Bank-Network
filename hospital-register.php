<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db.php';

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $hospitalName = trim($_POST['hospital_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];

    if (!empty($hospitalName) && !empty($email) && !empty($password)) {
        try {
            $checkStmt = $conn->prepare("SELECT * FROM hospitals WHERE email = :email");
            $checkStmt->execute([':email' => $email]);
            
            if ($checkStmt->rowCount() > 0) {
                $error = "This hospital email is already registered!";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO hospitals (hospital_name, email, phone, address, password, status) VALUES (:name, :email, :phone, :address, :pass, 'Pending')");
                $stmt->execute([
                    ':name' => $hospitalName,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':address' => $address,
                    ':pass' => $hashedPassword
                ]);

                $success = "Registration request submitted successfully! Your account is currently pending Admin approval.";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Registration - LifeLine Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background-color: #f0f4f8; background-image: radial-gradient(at 0% 0%, hsla(160, 100%, 74%, 0.15) 0px, transparent 50%), radial-gradient(at 100% 100%, hsla(204, 100%, 74%, 0.15) 0px, transparent 50%); padding: 20px; }
        
        .register-wrapper { width: 100%; max-width: 900px; background: #FFFFFF; border-radius: 24px; display: flex; overflow: hidden; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08); }
        
        .info-panel { width: 42%; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 40px; color: white; display: flex; flex-direction: column; justify-content: space-between; position: relative; }
        .info-panel::after { content: ''; position: absolute; bottom: -50px; right: -50px; width: 250px; height: 250px; background: rgba(255,255,255,0.1); border-radius: 50%; }
        .brand-logo-text { font-size: 1.4rem; font-weight: 800; display: flex; align-items: center; gap: 10px; z-index: 2; }
        .info-content { z-index: 2; }
        .info-content h2 { font-size: 2.2rem; font-weight: 800; line-height: 1.2; margin-bottom: 16px; }
        .info-content p { font-size: 0.95rem; line-height: 1.6; opacity: 0.9; }
        
        .form-panel { width: 58%; padding: 35px 45px; background: #FFFFFF; }
        .form-header-title { font-size: 1.5rem; font-weight: 800; color: #2b3674; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-size: 0.82rem; font-weight: 700; color: #2b3674; margin-bottom: 6px; }
        .form-input { width: 100%; padding: 12px 14px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 0.9rem; font-weight: 500; color: #2b3674; background: #f8fafc; outline: none; transition: all 0.2s; }
        .form-input:focus { background: #FFFFFF; border-color: #11998e; box-shadow: 0 0 0 4px rgba(17, 153, 142, 0.15); }

        .password-container { position: relative; }
        .toggle-password { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.1rem; color: #64748b; }
        
        .submit-btn { width: 100%; padding: 13px; border: none; border-radius: 12px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: #FFFFFF; font-size: 0.95rem; font-weight: 700; cursor: pointer; box-shadow: 0 6px 15px rgba(17, 153, 142, 0.3); transition: all 0.3s; margin-top: 5px; }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(17, 153, 142, 0.4); }
        
        .alert-success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-weight: 600; font-size: 13px; line-height: 1.4; text-align: center; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-weight: 600; font-size: 13px; text-align: center; }
        
        .footer-text { text-align: center; margin-top: 20px; font-size: 0.85rem; font-weight: 600; color: #64748b; }
        .footer-text a { color: #11998e; text-decoration: none; font-weight: 700; }
        .footer-text a:hover { text-decoration: underline; }

        @media (max-width: 768px) { .register-wrapper { flex-direction: column; } .info-panel { width: 100%; padding: 25px; } .form-panel { width: 100%; padding: 25px 20px; } }
    </style>
</head>
<body>

<div class="register-wrapper">
  
  <div class="info-panel">
    <div class="brand-logo-text">
        <span>🏥</span> LifeLine Connect
    </div>
    <div class="info-content">
        <h2>Hospital Partnership</h2>
        <p>Register your institution to securely manage central blood inventory, request emergency blood units, and coordinate life-saving supplies.</p>
    </div>
    <div style="font-size: 0.8rem; opacity: 0.8; z-index: 2;">Secure Medical Gateway</div>
  </div>

  <div class="form-panel">
    <div class="form-header-title">Hospital Registration Request</div>

    <?php if (!empty($success)): ?>
        <div class="alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label">Hospital Name</label>
            <input type="text" class="form-input" name="hospital_name" placeholder="e.g. Asiri Central Hospital" required>
        </div>
        <div class="form-group">
            <label class="form-label">Official Email</label>
            <input type="email" class="form-input" name="email" placeholder="e.g. info@asiri.lk" required>
        </div>
        <div class="form-group">
            <label class="form-label">Contact Phone</label>
            <input type="text" class="form-input" name="phone" placeholder="e.g. 0112345678">
        </div>
        <div class="form-group">
            <label class="form-label">Address / Location</label>
            <input type="text" class="form-input" name="address" placeholder="e.g. Colombo 05">
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <div class="password-container">
                <input type="password" class="form-input" id="passwordInput" name="password" placeholder="••••••••" required style="padding-right: 45px;">
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility()" id="eyeBtn">👁️</button>
            </div>
        </div>
        <button type="submit" class="submit-btn">Submit Registration Request</button>
    </form>

    <div class="footer-text">
        Already approved? <a href="login.php">Sign In here</a>
    </div>

  </div>
</div>

<script>
  function togglePasswordVisibility() {
    const passwordInput = document.getElementById('passwordInput');
    const eyeBtn = document.getElementById('eyeBtn');
    
    if (passwordInput.type === 'password') {
      passwordInput.type = 'text';
      eyeBtn.innerText = '🙈';
    } else {
      passwordInput.type = 'password';
      eyeBtn.innerText = '👁️';
    }
  }
</script>

</body>
</html>