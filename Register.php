<?php
// Start session for future use (like showing error or success messages)
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LifeLine Connect — Modern Registration</title>
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

  /* Split Screen Wrapper */
  .register-wrapper {
    width: 100%;
    max-width: 950px;
    background: #FFFFFF;
    border-radius: 24px;
    display: flex;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
  }

  /* Left Panel (Info & Branding) */
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
  }

  .info-content h2 {
    font-size: 2rem;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 16px;
  }

  .info-content p {
    font-size: 0.95rem;
    line-height: 1.6;
    opacity: 0.9;
  }

  /* Right Panel (Form area) */
  .form-panel {
    width: 60%;
    padding: 40px 50px;
    background: #FFFFFF;
  }

  /* STEPPER */
  .stepper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    position: relative;
  }
  .stepper::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    transform: translateY(-50%);
    height: 4px;
    width: 100%;
    background: #f1f5f9;
    z-index: 1;
    border-radius: 2px;
  }
  .progress-line {
    position: absolute;
    top: 50%;
    left: 0;
    transform: translateY(-50%);
    height: 4px;
    width: 0%;
    background: var(--primary-gradient);
    z-index: 1;
    transition: width 0.4s ease;
    border-radius: 2px;
  }
  .step-node {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #FFFFFF;
    border: 3px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--text-muted);
    position: relative;
    z-index: 2;
    transition: all 0.3s ease;
  }
  .step-node.active {
    border-color: var(--primary-red);
    color: var(--primary-red);
    box-shadow: 0 0 0 4px rgba(255, 65, 108, 0.15);
  }
  .step-node.completed {
    border-color: transparent;
    background: var(--primary-gradient);
    color: white;
  }

  /* FORMS */
  .step-content { display: none; }
  .step-content.active { display: block; animation: fadeIn 0.4s ease; }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateX(10px); }
    to { opacity: 1; transform: translateX(0); }
  }

  .step-title {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--text-main);
    margin-bottom: 24px;
  }

  .form-group { margin-bottom: 16px; }
  .form-row { display: flex; gap: 16px; }
  .form-row .form-group { flex: 1; }

  .form-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 8px;
  }

  .form-input, select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--text-main);
    background: #f8fafc;
    outline: none;
    transition: all 0.2s;
  }

  .form-input:focus, select:focus {
    background: #FFFFFF;
    border-color: var(--primary-red);
    box-shadow: 0 0 0 4px rgba(255, 65, 108, 0.1);
  }

  .form-input.error, select.error {
    border-color: var(--error-red) !important;
    background-color: #FEF2F2;
  }

  /* Password Wrapper for Eye Icon */
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
    color: var(--error-red);
    font-size: 0.8rem;
    font-weight: 600;
    margin-top: 6px;
    display: none;
  }

  .btn-group { display: flex; justify-content: space-between; margin-top: 32px; gap: 16px; }
  
  .btn {
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    border: none;
    transition: all 0.3s;
  }
  .btn-secondary { 
    background: #f1f5f9; 
    color: var(--text-main); 
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

  .login-link { text-align: center; margin-top: 24px; font-size: 0.9rem; font-weight: 600; color: var(--text-muted); }
  .login-link a { color: var(--primary-red); text-decoration: none; }
  .login-link a:hover { text-decoration: underline; }

  /* CUSTOM MODAL POPUP STYLES */
  .custom-modal-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(43, 54, 116, 0.5);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
  }
  .custom-modal-overlay.show {
    opacity: 1;
    visibility: visible;
  }
  .custom-modal-box {
    background: #ffffff;
    width: 100%;
    max-width: 400px;
    border-radius: 20px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    transform: translateY(20px);
    transition: all 0.3s ease;
  }
  .custom-modal-overlay.show .custom-modal-box {
    transform: translateY(0);
  }
  .modal-icon {
    width: 60px; height: 60px;
    background: #fee2e2;
    color: #EF4444;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin: 0 auto 20px auto;
  }
  .modal-title {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--text-main);
    margin-bottom: 8px;
  }
  .modal-desc {
    font-size: 0.9rem;
    color: var(--text-muted);
    line-height: 1.5;
    margin-bottom: 24px;
  }
  .modal-btn {
    background: var(--primary-gradient);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    width: 100%;
    box-shadow: 0 6px 15px rgba(255, 65, 108, 0.25);
    transition: all 0.2s;
  }
  .modal-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .register-wrapper { flex-direction: column; }
    .info-panel { width: 100%; padding: 30px; }
    .form-panel { width: 100%; padding: 30px 20px; }
    .form-row { flex-direction: column; gap: 0; }
  }
</style>
</head>
<body>

<!-- CUSTOM MODAL POPUP HTML -->
<div class="custom-modal-overlay" id="customModal">
  <div class="custom-modal-box">
    <div class="modal-icon">⚠️</div>
    <div class="modal-title" id="modalTitle">Registration Notice</div>
    <div class="modal-desc" id="modalDesc">This NIC or Email is already registered.</div>
    <button class="modal-btn" onclick="closeModal()">OK, Understood</button>
  </div>
</div>

<div class="register-wrapper">
  
  <!-- Left Side Banner -->
  <div class="info-panel">
    <div class="brand-logo-text">
        <span style="font-size: 24px;">♥</span> LifeLine Connect
    </div>
    <div class="info-content">
        <h2>Join the Lifesaver Community</h2>
        <p>Your single donation can save up to 3 lives. Register today to easily book camps, track your donation history, and receive emergency alerts.</p>
    </div>
    <div style="font-size: 0.85rem; opacity: 0.8;">Secure & Confidential Portal</div>
  </div>

  <!-- Right Side Form -->
  <div class="form-panel">
      <div class="stepper">
        <div class="progress-line" id="progressLine"></div>
        <div class="step-node active" id="node1">1</div>
        <div class="step-node" id="node2">2</div>
        <div class="step-node" id="node3">3</div>
        <div class="step-node" id="node4">4</div>
      </div>

      <!-- Form action points to register_backend.php -->
      <form id="regForm" action="register_backend.php" method="POST" novalidate onsubmit="handleRegistration(event)">

        <div class="step-content active" id="step1">
          <div class="step-title">Personal Info</div>
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-input" id="fullName" name="fullName" placeholder="e.g. Ushan Sachiru">
            <div class="error-message" id="fullNameErr">Please enter your full name.</div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">NIC Number</label>
              <input type="text" class="form-input" id="nic" name="nic" placeholder="e.g. 199812345678">
              <div class="error-message" id="nicErr">Enter a valid SL NIC.</div>
            </div>
            <div class="form-group">
              <label class="form-label">Date of Birth</label>
              <input type="date" class="form-input" id="dob" name="dob">
              <div class="error-message" id="dobErr">Must be 18+ years old.</div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Gender</label>
            <select id="gender" name="gender">
              <option value="">Select Gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
            <div class="error-message" id="genderErr">Please select your gender.</div>
          </div>
        </div>

        <div class="step-content" id="step2">
          <div class="step-title">Medical Profile</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Blood Group</label>
              <select id="bloodGroup" name="bloodGroup">
                <option value="">Select Group</option>
                <option value="O+">O Positive (O+)</option>
                <option value="O-">O Negative (O-)</option>
                <option value="A+">A Positive (A+)</option>
                <option value="A-">A Negative (A-)</option>
                <option value="B+">B Positive (B+)</option>
                <option value="B-">B Negative (B-)</option>
                <option value="AB+">AB Positive (AB+)</option>
                <option value="AB-">AB Negative (AB-)</option>
              </select>
              <div class="error-message" id="bloodGroupErr">Select a valid group.</div>
            </div>
            <div class="form-group">
              <label class="form-label">Weight (Kg)</label>
              <input type="number" class="form-input" id="weight" name="weight" placeholder="Min 50kg">
              <div class="error-message" id="weightErr">Minimum weight is 50kg.</div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Last Blood Donation</label>
            <select id="lastDonation" name="lastDonation">
              <option value="Never">Never Donated</option>
              <option value="MoreThan4">More than 4 months ago</option>
              <option value="LessThan4">Less than 4 months ago</option>
            </select>
            <div class="error-message" id="lastDonationErr">Please select an option.</div>
          </div>
          <div class="form-group">
            <label class="form-label">Health Conditions / Allergies</label>
            <input type="text" class="form-input" id="conditions" name="conditions" placeholder="Type 'None' if applicable">
            <div class="error-message" id="conditionsErr">Specify conditions or type 'None'.</div>
          </div>
        </div>

        <div class="step-content" id="step3">
          <div class="step-title">Contact Details</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">District</label>
              <select id="district" name="district">
                <option value="">Select District</option>
                <option value="Colombo">Colombo</option>
                <option value="Kalutara">Kalutara</option>
                <option value="Galle">Galle</option>
                <option value="Kandy">Kandy</option>
              </select>
              <div class="error-message" id="districtErr">Please select your district.</div>
            </div>
            <div class="form-group">
              <label class="form-label">City / Town</label>
              <input type="text" class="form-input" id="city" name="city" placeholder="e.g. Panadura">
              <div class="error-message" id="cityErr">Enter your city.</div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Mobile Number</label>
            <input type="tel" class="form-input" id="mobile" name="mobile" placeholder="07XXXXXXXX">
            <div class="error-message" id="mobileErr">Enter a valid 10-digit number.</div>
          </div>
          <div class="form-group">
            <label class="form-label">Emergency Contact Info</label>
            <input type="text" class="form-input" id="emergencyContact" name="emergencyContact" placeholder="Name - Number">
            <div class="error-message" id="emergencyErr">Provide emergency info.</div>
          </div>
        </div>

        <div class="step-content" id="step4">
          <div class="step-title">Account Security</div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-input" id="email" name="email" placeholder="name@example.com">
            <div class="error-message" id="emailErr">Enter a valid email.</div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Password</label>
              <div class="password-container">
                <input type="password" class="form-input" id="password" name="password" placeholder="Min 8 chars" style="padding-right: 45px;">
                <button type="button" class="toggle-password" onclick="togglePassword('password', 'eye1')" id="eye1">👁️</button>
              </div>
              <div class="error-message" id="passwordErr">Minimum 8 characters.</div>
            </div>
            <div class="form-group">
              <label class="form-label">Confirm Password</label>
              <div class="password-container">
                <input type="password" class="form-input" id="confirmPassword" placeholder="Confirm password" style="padding-right: 45px;">
                <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword', 'eye2')" id="eye2">👁️</button>
              </div>
              <div class="error-message" id="confirmPasswordErr">Passwords must match.</div>
            </div>
          </div>
          <div class="form-group" style="margin-top: 16px;">
            <label style="display: flex; gap: 10px; font-size: 0.88rem; font-weight: 600; cursor: pointer; color: var(--text-main);">
              <input type="checkbox" id="termsCheck" style="width: 18px; height: 18px; accent-color: var(--primary-red);">
              I agree to the Terms of Service & Privacy Policy
            </label>
            <div class="error-message" id="termsErr">You must accept the terms.</div>
          </div>
        </div>

        <div class="btn-group">
          <button type="button" class="btn btn-secondary" id="prevBtn" onclick="changeStep(-1)" style="display: none;">Back</button>
          <button type="submit" class="btn btn-primary" id="nextBtn">Next Step</button>
        </div>

      </form>

      <div class="login-link">
        Already have an account? <a href="login.php">Sign in</a>
      </div>
  </div>

</div>

<script>
  let currentStep = 1;
  const totalSteps = 4;

  // Custom Modal Show / Hide Functions
  function showModal(title, message) {
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalDesc').innerText = message;
    document.getElementById('customModal').classList.add('show');
  }

  function closeModal() {
    document.getElementById('customModal').classList.remove('show');
  }

  // ඔබට Backend එකෙන් එන එරර් මැසේජ් එකක් මෙලෙස මොඩාල් එකෙන් පෙන්විය හැක (උදාහරණයක් ලෙස URL එකේ error=1 නම්):
  window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
      const errCode = urlParams.get('error');
      if (errCode === 'exists' || errCode === '1') {
        showModal('Registration Failed', 'This NIC or Email is already registered in the system.');
      } else {
        showModal('Notice', 'An error occurred during registration. Please try again.');
      }
    }
  });

  function showError(fieldId, errId, msg = null) {
    const field = document.getElementById(fieldId);
    const errBox = document.getElementById(errId);
    if(field) field.classList.add('error');
    if(errBox) {
      if(msg) errBox.innerText = msg;
      errBox.style.display = 'block';
    }
  }

  function clearError(fieldId, errId) {
    const field = document.getElementById(fieldId);
    const errBox = document.getElementById(errId);
    if(field) field.classList.remove('error');
    if(errBox) errBox.style.display = 'none';
  }

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

  function validateCurrentStep() {
    let isValid = true;

    if (currentStep === 1) {
      const name = document.getElementById('fullName').value.trim();
      const nic = document.getElementById('nic').value.trim();
      const dob = document.getElementById('dob').value;
      const gender = document.getElementById('gender').value;

      if (!name || name.length < 3) { showError('fullName', 'fullNameErr'); isValid = false; } else { clearError('fullName', 'fullNameErr'); }
      const nicPattern = /^([0-9]{9}[vVxX]|[0-9]{12})$/;
      if (!nicPattern.test(nic)) { showError('nic', 'nicErr'); isValid = false; } else { clearError('nic', 'nicErr'); }
      if (!dob) { showError('dob', 'dobErr'); isValid = false; } else {
        const age = new Date().getFullYear() - new Date(dob).getFullYear();
        if (age < 18) { showError('dob', 'dobErr'); isValid = false; } else { clearError('dob', 'dobErr'); }
      }
      if (!gender) { showError('gender', 'genderErr'); isValid = false; } else { clearError('gender', 'genderErr'); }
    }
    else if (currentStep === 2) {
      const bg = document.getElementById('bloodGroup').value;
      const w = parseInt(document.getElementById('weight').value);
      const c = document.getElementById('conditions').value.trim();

      if (!bg) { showError('bloodGroup', 'bloodGroupErr'); isValid = false; } else { clearError('bloodGroup', 'bloodGroupErr'); }
      if (isNaN(w) || w < 50) { showError('weight', 'weightErr'); isValid = false; } else { clearError('weight', 'weightErr'); }
      if (!c) { showError('conditions', 'conditionsErr'); isValid = false; } else { clearError('conditions', 'conditionsErr'); }
    }
    else if (currentStep === 3) {
      const d = document.getElementById('district').value;
      const c = document.getElementById('city').value.trim();
      const m = document.getElementById('mobile').value.trim();
      const e = document.getElementById('emergencyContact').value.trim();
      const phoneRegex = /^(?:0|94|\+94)?7[01245678]\d{7}$/;

      if (!d) { showError('district', 'districtErr'); isValid = false; } else { clearError('district', 'districtErr'); }
      if (!c) { showError('city', 'cityErr'); isValid = false; } else { clearError('city', 'cityErr'); }
      if (!phoneRegex.test(m)) { showError('mobile', 'mobileErr'); isValid = false; } else { clearError('mobile', 'mobileErr'); }
      if (!e || e.length < 5) { showError('emergencyContact', 'emergencyErr'); isValid = false; } else { clearError('emergencyContact', 'emergencyErr'); }
    }
    else if (currentStep === 4) {
      const email = document.getElementById('email').value.trim();
      const pass = document.getElementById('password').value;
      const conf = document.getElementById('confirmPassword').value;
      const terms = document.getElementById('termsCheck').checked;
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!emailRegex.test(email)) { showError('email', 'emailErr'); isValid = false; } else { clearError('email', 'emailErr'); }
      if (!pass || pass.length < 8) { showError('password', 'passwordErr'); isValid = false; } else { clearError('password', 'passwordErr'); }
      if (pass !== conf || !conf) { showError('confirmPassword', 'confirmPasswordErr'); isValid = false; } else { clearError('confirmPassword', 'confirmPasswordErr'); }
      if (!terms) { showError('termsCheck', 'termsErr'); isValid = false; } else { clearError('termsCheck', 'termsErr'); }
    }
    return isValid;
  }

  function changeStep(direction) {
    if (direction === 1 && !validateCurrentStep()) return;
    currentStep += direction;
    
    if (currentStep > totalSteps) {
      document.getElementById('regForm').submit();
      return;
    }
    updateUI();
  }

  document.getElementById('nextBtn').addEventListener('click', function(e) {
    if (currentStep < totalSteps) {
      e.preventDefault();
      changeStep(1);
    } else {
      if (validateCurrentStep()) {
        document.getElementById('regForm').submit();
      }
    }
  });

  function updateUI() {
    document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
    document.getElementById(`step${currentStep}`).classList.add('active');

    const progressPercent = ((currentStep - 1) / (totalSteps - 1)) * 100;
    document.getElementById('progressLine').style.width = `${progressPercent}%`;

    for (let i = 1; i <= totalSteps; i++) {
      const node = document.getElementById(`node${i}`);
      if (i < currentStep) {
        node.classList.add('completed');
        node.classList.remove('active');
        node.innerText = '✓';
      } else if (i === currentStep) {
        node.classList.add('active');
        node.classList.remove('completed');
        node.innerText = i;
      } else {
        node.classList.remove('active', 'completed');
        node.innerText = i;
      }
    }

    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    if (currentStep === 1) {
      prevBtn.style.display = 'none';
      nextBtn.innerText = 'Next Step';
    } else if (currentStep === totalSteps) {
      prevBtn.style.display = 'inline-block';
      nextBtn.innerText = 'Complete Registration';
      prevBtn.style.background = '#f1f5f9';
      nextBtn.style.background = 'linear-gradient(135deg, #11998e 0%, #38ef7d 100% )'; 
    } else {
      prevBtn.style.display = 'inline-block';
      nextBtn.innerText = 'Next Step';
      nextBtn.style.background = 'var(--primary-gradient)';
    }
  }

  function handleRegistration(event) {
    if (!validateCurrentStep()) {
      event.preventDefault();
    }
  }
</script>
</body>
</html>