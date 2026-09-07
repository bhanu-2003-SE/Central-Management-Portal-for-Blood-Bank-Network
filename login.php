<?php
session_start();
// අවශ්‍ය නම් ලොගින් බැක්එන්ඩ් එක වෙනම තබා මෙහි include කළ හැක
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LifeLine Connect — Sign In</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --user-gradient: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);
    --user-shadow: rgba(255, 65, 108, 0.3);
    --admin-gradient: linear-gradient(135deg, #36D1DC 0%, #5B86E5 100%);
    --admin-shadow: rgba(54, 209, 220, 0.3);
    --hospital-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --hospital-shadow: rgba(17, 153, 142, 0.3);
    --current-gradient: var(--user-gradient);
    --current-shadow: var(--user-shadow);
    --bg-light: #f0f4f8;
    --text-main: #2b3674;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
    --error-red: #EF4444;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
  body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background-color: var(--bg-light); background-image: radial-gradient(at 0% 0%, hsla(359, 100%, 74%, 0.15) 0px, transparent 50%), radial-gradient(at 100% 100%, hsla(204, 100%, 74%, 0.15) 0px, transparent 50%); padding: 20px; }
  .login-wrapper { width: 100%; max-width: 900px; background: #FFFFFF; border-radius: 24px; display: flex; overflow: hidden; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08); }
  .info-panel { width: 45%; background: var(--current-gradient); padding: 40px; color: white; display: flex; flex-direction: column; justify-content: space-between; position: relative; transition: background 0.5s ease; }
  .info-panel::after { content: ''; position: absolute; bottom: -50px; right: -50px; width: 250px; height: 250px; background: rgba(255,255,255,0.1); border-radius: 50%; }
  .brand-logo-text { font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; gap: 10px; z-index: 2; }
  .info-content { z-index: 2; }
  .info-content h2 { font-size: 2.2rem; font-weight: 800; line-height: 1.2; margin-bottom: 16px; }
  .info-content p { font-size: 1rem; line-height: 1.6; opacity: 0.9; }
  .form-panel { width: 55%; padding: 40px 50px; background: #FFFFFF; }
  .form-header-title { font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin-bottom: 24px; }
  .role-switch { display: flex; background: #f1f5f9; border-radius: 12px; padding: 6px; margin-bottom: 30px; gap: 4px; }
  .role-btn { flex: 1; padding: 8px 4px; border: none; background: transparent; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); border-radius: 8px; cursor: pointer; transition: all 0.3s ease; text-align: center; }
  .role-btn.active { background: #FFFFFF; color: var(--text-main); box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); }
  .form-group { margin-bottom: 20px; }
  .form-label { display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-main); margin-bottom: 8px; }
  .form-input { width: 100%; padding: 14px 16px; border: 2px solid var(--border-color); border-radius: 12px; font-size: 0.95rem; font-weight: 500; color: var(--text-main); background: #f8fafc; outline: none; transition: all 0.2s; }
  .form-input:focus { background: #FFFFFF; border-color: #a3aed1; box-shadow: 0 0 0 4px rgba(163, 174, 209, 0.2); }
  .password-container { position: relative; }
  .toggle-password { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.1rem; color: var(--text-muted); }
  .error-message { color: var(--error-red); font-size: 0.8rem; font-weight: 600; margin-top: 6px; display: none; }
  .form-options { display: flex; justify-content: space-between; align-items: center; font-size: 0.88rem; margin-bottom: 30px; font-weight: 600; }
  .forgot-link { color: var(--text-muted); text-decoration: none; transition: color 0.2s; }
  .forgot-link:hover { color: var(--text-main); text-decoration: underline; }
  .submit-btn { width: 100%; padding: 14px; border: none; border-radius: 12px; background: var(--current-gradient); color: #FFFFFF; font-size: 1rem; font-weight: 700; cursor: pointer; box-shadow: 0 6px 15px var(--current-shadow); transition: all 0.3s; }
  .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px var(--current-shadow); }
  .footer-text { text-align: center; margin-top: 30px; font-size: 0.9rem; font-weight: 600; color: var(--text-muted); }
  .footer-text a { text-decoration: none; transition: opacity 0.2s; }
  .footer-text a:hover { opacity: 0.8; text-decoration: underline; }
  @media (max-width: 768px) { .login-wrapper { flex-direction: column; } .info-panel { width: 100%; padding: 30px; } .form-panel { width: 100%; padding: 30px 20px; } .info-content h2 { font-size: 1.8rem; } }
</style>
</head>
<body>

<div class="login-wrapper">
  
  <div class="info-panel" id="infoPanel">
    <div class="brand-logo-text">
        <span id="brandIcon" style="font-size: 24px;">♥</span> LifeLine Connect
    </div>
    <div class="info-content">
        <h2 id="infoTitle">Welcome Back to LifeLine</h2>
        <p id="infoDesc">Access your donor portal to manage your appointments, view donation history, and respond to emergency appeals.</p>
    </div>
    <div style="font-size: 0.85rem; opacity: 0.8; z-index: 2;" id="infoFooter">Secure Donor Access</div>
  </div>

  <div class="form-panel">
    <div class="form-header-title">Sign In</div>

    <div class="role-switch">
      <button type="button" class="role-btn active" id="userRoleBtn" onclick="selectRole('user')">Donor Access</button>
      <button type="button" class="role-btn" id="adminRoleBtn" onclick="selectRole('admin')">Admin Console</button>
      <button type="button" class="role-btn" id="hospitalRoleBtn" onclick="selectRole('hospital')">Hospital Portal</button>
    </div>

    <form action="login_backend.php" method="POST">
      <input type="hidden" id="roleInput" name="role" value="user">

      <div class="form-group">
        <label class="form-label">Email Address / Username</label>
        <input type="text" class="form-input" id="loginEmail" name="email" placeholder="e.g. ushan@example.com" required>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="password-container">
          <input type="password" class="form-input" id="loginPassword" name="password" placeholder="••••••••" required style="padding-right: 45px;">
          <button type="button" class="toggle-password" onclick="togglePasswordVisibility()" id="eyeBtn">👁️</button>
        </div>
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message" style="display:block; margin-top:10px;">
                <?php echo ($_GET['error'] === 'pending') ? 'Your hospital account is pending admin approval.' : 'Invalid email or password. Please try again.'; ?>
            </div>
        <?php endif; ?>
      </div>

      <div class="form-options">
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-main);">
          <input type="checkbox" style="width: 16px; height: 16px;"> Remember me
        </label>
        <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
      </div>

      <button type="submit" class="submit-btn" id="submitBtn">Secure Sign In</button>
    </form>

	<div class="footer-text" id="footerText">
		New to LifeLine? <a href="Register.php" style="color: #FF416C;">Create an account</a>
	</div>

  </div>
</div>

<script>
  function selectRole(role) {
    document.getElementById('roleInput').value = role;

    const userBtn = document.getElementById('userRoleBtn');
    const adminBtn = document.getElementById('adminRoleBtn');
    const hospitalBtn = document.getElementById('hospitalRoleBtn');
    const root = document.documentElement;

    const brandIcon = document.getElementById('brandIcon');
    const infoTitle = document.getElementById('infoTitle');
    const infoDesc = document.getElementById('infoDesc');
    const infoFooter = document.getElementById('infoFooter');
    const footerText = document.getElementById('footerText');
    const emailInput = document.getElementById('loginEmail');

    userBtn.classList.remove('active');
    adminBtn.classList.remove('active');
    hospitalBtn.classList.remove('active');

    if (role === 'user') {
      userBtn.classList.add('active');
      root.style.setProperty('--current-gradient', 'var(--user-gradient)');
      root.style.setProperty('--current-shadow', 'var(--user-shadow)');
      brandIcon.innerText = '♥';
      infoTitle.innerText = 'Welcome Back to LifeLine';
      infoDesc.innerText = 'Access your donor portal to manage your appointments, view donation history, and respond to emergency appeals.';
      infoFooter.innerText = 'Secure Donor Access';
      footerText.innerHTML = 'New to LifeLine? <a href="Register.php" style="color: #FF416C;">Create an account</a>';
      emailInput.value = '';
    } 
    else if (role === 'admin') {
      adminBtn.classList.add('active');
      root.style.setProperty('--current-gradient', 'var(--admin-gradient)');
      root.style.setProperty('--current-shadow', 'var(--admin-shadow)');
      brandIcon.innerText = '⚙';
      infoTitle.innerText = 'System Administration';
      infoDesc.innerText = 'Access the executive dashboard to manage blood stocks, organize camps, and approve hospital requests.';
      infoFooter.innerText = 'Authorized Personnel Only';
      footerText.innerHTML = 'Need admin access? <a href="mailto:sachiruushan@gmail.com?subject=Admin%20Access%20Request" style="color: #36D1DC;">Contact IT Dept</a>';
      emailInput.value = '';
    } 
    else if (role === 'hospital') {
      hospitalBtn.classList.add('active');
      root.style.setProperty('--current-gradient', 'var(--hospital-gradient)');
      root.style.setProperty('--current-shadow', 'var(--hospital-shadow)');
      brandIcon.innerText = '🏥';
      infoTitle.innerText = 'Hospital Portal';
      infoDesc.innerText = 'Access central blood inventory and submit emergency or normal blood unit requests directly.';
      infoFooter.innerText = 'Hospital Authorized Access';
      
      // මෙන්න මෙතැනින් hospital-register.php වෙත යාමට පහසුකම් සපයා ඇත
      footerText.innerHTML = 'New hospital? <a href="hospital-register.php" style="color: #11998e;">Register your hospital</a>';
      
      emailInput.value = '';
    }
  }

  function togglePasswordVisibility() {
    const passwordInput = document.getElementById('loginPassword');
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