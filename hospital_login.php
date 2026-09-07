<?php
session_start();
require_once 'db.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $hospital_username = $_POST['username'];
    $password = $_POST['password'];

    if ($hospital_username == "National Hospital" && $password == "hospital123") {
        $_SESSION['hospital_name'] = "National Hospital Colombo";
        $_SESSION['role'] = "hospital";
        header("Location: hospital-request-form.php");
        exit();
    } else {
        $error = "Invalid Hospital Username or Password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Portal Login - LifeLine Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { 
            background-color: #f0f4f8; 
            background-image: radial-gradient(at 0% 0%, hsla(359, 100%, 74%, 0.15) 0px, transparent 50%), radial-gradient(at 100% 0%, hsla(204, 100%, 74%, 0.15) 0px, transparent 50%); 
            color: #2b3674; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
        }
        .login-card { 
            background: white; 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
            width: 100%; 
            max-width: 420px; 
        }
        .brand { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 10px; 
            font-size: 20px; 
            font-weight: 800; 
            color: #ff416c; 
            margin-bottom: 24px; 
        }
        .login-card h2 { color: #2b3674; margin-bottom: 8px; text-align: center; font-size: 24px; font-weight: 800; }
        .subtitle { text-align: center; color: #64748b; font-size: 14px; margin-bottom: 24px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: #2b3674; }
        .form-group input { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 14px; transition: all 0.3s; }
        .form-group input:focus { border-color: #5B86E5; box-shadow: 0 0 0 4px rgba(91, 134, 229, 0.1); }
        .btn { 
            background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); 
            color: white; 
            border: none; 
            padding: 14px; 
            width: 100%; 
            border-radius: 12px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 65, 108, 0.3);
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255, 65, 108, 0.4); }
        .error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; font-size: 13px; text-align: center; margin-bottom: 20px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg> LifeLine Connect
        </div>
        <h2>Hospital Portal</h2>
        <p class="subtitle">Sign in to submit emergency blood requests</p>
        
        <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Hospital Username</label>
                <input type="text" name="username" required placeholder="e.g. National Hospital">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn">Login Portal</button>
        </form>
    </div>
</body>
</html>