<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        // රෝහල් යූසර්වරුන් (role = 'hospital') පරීක්ෂා කිරීම සඳහා 
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username AND role = 'hospital'");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['PASSWORD'] ?? $user['password'])) {
            $_SESSION['hospital_user'] = $user['USERNAME'] ?? $user['username'];
            $_SESSION['hospital_name'] = $user['HOSPITAL_NAME'] ?? $user['hospital_name'] ?? 'City Hospital';
            $_SESSION['role'] = 'hospital';
            
            header("Location: hospital-dashboard.php");
            exit();
        } else {
            $error = "Invalid hospital username or password!";
        }
    } catch (Exception $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Login - LifeLine Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f0f4f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 100%; max-width: 400px; }
        h2 { color: #2b3674; margin-bottom: 24px; font-weight: 800; text-align: center; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: #2b3674; }
        .form-group input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px; outline: none; }
        .btn { background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); color: white; border: none; padding: 12px; border-radius: 10px; cursor: pointer; font-weight: 700; width: 100%; transition: 0.3s; }
        .btn:hover { opacity: 0.9; }
        .error { color: #d32f2f; font-size: 13px; margin-bottom: 15px; text-align: center; font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>🏥 Hospital Login</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Hospital Username</label>
                <input type="text" name="username" placeholder="e.g. colombo_hospital" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn">Login to Portal</button>
        </form>
    </div>
</body>
</html>