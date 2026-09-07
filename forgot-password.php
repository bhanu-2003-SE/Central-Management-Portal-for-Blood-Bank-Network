<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db.php';

$errorMsg = "";
$successMsg = "";
$step = 1;

// Step 1: ඊමේල් එක පරීක්ෂා කිරීම
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_otp'])) {
    $account = trim($_POST['reset_account']);
    
    if (!empty($account)) {
        try {
            $stmt = $conn->prepare("SELECT email FROM donors WHERE email = :acc UNION SELECT email FROM hospitals WHERE email = :acc2");
            $stmt->execute([':acc' => $account, ':acc2' => $account]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_SESSION['reset_email'] = $user['EMAIL'] ?? $user['email'];
                $step = 2; 
            } else {
                $errorMsg = "No account found with this email address.";
            }
        } catch (Exception $e) {
            $errorMsg = "Database Error: " . $e->getMessage();
        }
    } else {
        $errorMsg = "Please enter your email address.";
    }
}

// Step 2: නව පාස්වර්ඩ් එක අප්ඩේට් කිරීම
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $step = 2; 
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword) || strlen($newPassword) < 8) {
        $errorMsg = "Password must be at least 8 characters.";
    } elseif ($newPassword !== $confirmPassword) {
        $errorMsg = "Passwords do not match.";
    } else {
        try {
            $email = $_SESSION['reset_email'] ?? '';
            
            if (empty($email)) {
                header("Location: forgot-password.php");
                exit();
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt1 = $conn->prepare("UPDATE donors SET user_password = :pass WHERE email = :email");
            $stmt1->execute([':pass' => $hashedPassword, ':email' => $email]);

            $stmt2 = $conn->prepare("UPDATE hospitals SET password = :pass WHERE email = :email");
            $stmt2->execute([':pass' => $hashedPassword, ':email' => $email]);

            unset($_SESSION['reset_email']);

            echo "<script>alert('Password Updated Successfully! Please sign in with your new password.'); window.location.href='login.php';</script>";
            exit();

        } catch (Exception $e) {
            $errorMsg = "Error updating password: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LifeLine Connect — Reset Password</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --primary-red: #FF416C;
    --primary-gradient: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);
    --bg-light: #f0f4f8;
    --text-main: #2b3674;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
    --error-red: #EF4444;
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

  .reset-wrapper {
    width: 100%;
    max-width: 900px;
    background: #FFFFFF;
    border-radius: 24px;
    display: flex;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
  }

  .info-panel {
    width: 45%;
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
    width: 250px;
    height: 250px;
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

  .info-content { z-index: 2; }
  .info-content h2 { font-size: 2.2rem; font-weight: 800; line-height: 1.2; margin-bottom: 16px; }
  .info-content p { font-size: 1rem; line-height: 1.6; opacity: 0.9; }

  .form-panel {
    width: 55%;
    padding: 40px 50px;
    background: #FFFFFF;
  }

  .form-header-title {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--text-main);
    margin-bottom: 8px;
  }

  .form-header-sub {
    font-size: 0.9rem;
    color: var(--text-muted);
    font-weight: 500;
    margin-bottom: 24px;
    line-height: 1.4;
  }

  .step-panel { display: none; }
  .step-panel.active { display: block; animation: fadeIn 0.4s ease; }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .form-group { margin-bottom: 20px; }
  .form-label { display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-main); margin-bottom: 8px; }
  .form-input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--text-main);
    background: #f8fafc;
    outline: none;
    transition: all 0.2s;
  }
  .form-input:focus {
    background: #FFFFFF;
    border-color: var(--primary-red);
    box-shadow: 0 0 0 4px rgba(255, 65, 108, 0.1);
  }

  /* Password Container & Eye Icon */
  .password-container {
    position: relative;
  }
  .toggle-password {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    color: var(--text-muted);
  }

  .error-message {
    background: #fee2e2;
    color: var(--error-red);
    padding: 10px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 15px;
    text-align: center;
  }

  .submit-btn {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 12px;
    background: var(--primary-gradient);
    color: #FFFFFF;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 6px 15px rgba(255, 65, 108, 0.25);
    transition: all 0.3s;
    margin-top: 10px;
  }
  .submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(255, 65, 108, 0.35);
  }

  .footer-link {
    text-align: center;
    margin-top: 24px;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-muted);
  }
  .footer-link a { color: var(--primary-red); text-decoration: none; }
  .footer-link a:hover { text-decoration: underline; }

  @media (max-width: 768px) {
    .reset-wrapper { flex-direction: column; }
    .info-panel { width: 100%; padding: 30px; }
    .form-panel { width: 100%; padding: 30px 20px; }
  }
</style>
</head>
<body>

<div class="reset-wrapper">
  
  <!-- Left Panel -->
  <div class="info-panel">
    <div class="brand-logo-text">
        <span style="font-size: 24px;">♥</span> LifeLine Connect
    </div>
    <div class="info-content">
        <h2>Password Recovery</h2>
        <p>Don't worry! Enter your registered email, and we will guide you to securely reset your password.</p>
    </div>
    <div style="font-size: 0.85rem; opacity: 0.8; z-index: 2;">Secure Account Recovery</div>
  </div>

  <!-- Right Panel -->
  <div class="form-panel">
    
    <div class="form-header-title" id="pageTitle">
        <?php echo (isset($_SESSION['reset_email'])) ? 'Set New Password' : 'Reset Password'; ?>
    </div>
    <div class="form-header-sub" id="pageSub">
        <?php echo (isset($_SESSION['reset_email'])) ? 'Enter your new password below.' : 'Enter your registered email to continue.'; ?>
    </div>

    <?php if (!empty($errorMsg)): ?>
        <div class="error-message"><?php echo htmlspecialchars($errorMsg); ?></div>
    <?php endif; ?>

    <!-- STEP 1: EMAIL VERIFICATION -->
    <div class="step-panel <?php echo (!isset($_SESSION['reset_email'])) ? 'active' : ''; ?>" id="step1">
      <form method="POST" action="">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" class="form-input" name="reset_account" placeholder="e.g. ushan@example.com" required>
        </div>
        <button type="submit" name="send_otp" class="submit-btn">Verify Email</button>
      </form>
    </div>

    <!-- STEP 2: SET NEW PASSWORD -->
    <div class="step-panel <?php echo (isset($_SESSION['reset_email'])) ? 'active' : ''; ?>" id="step2">
      <form method="POST" action="">
        <div class="form-group">
          <label class="form-label">New Password</label>
          <div class="password-container">
            <input type="password" class="form-input" id="newPassword" name="new_password" placeholder="Min 8 characters" style="padding-right: 45px;" required>
            <button type="button" class="toggle-password" onclick="togglePassword('newPassword', 'eye1')" id="eye1">👁️</button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm New Password</label>
          <div class="password-container">
            <input type="password" class="form-input" id="confirmPassword" name="confirm_password" placeholder="••••••••" style="padding-right: 45px;" required>
            <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword', 'eye2')" id="eye2">👁️</button>
          </div>
        </div>

        <button type="submit" name="reset_password" class="submit-btn">Update Password</button>
      </form>
    </div>

    <div class="footer-link">
      Remembered your password? <a href="login.php">Back to Sign In</a>
    </div>

  </div>

</div>

<script>
  function togglePassword(fieldId, btnId) {
    const passwordInput = document.getElementById(fieldId);
    const eyeBtn = document.getElementById(btnId);
    
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