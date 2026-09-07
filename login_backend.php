<?php
session_start();
require_once 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $role = $_POST['role'] ?? 'user';
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    try {
        // ==========================================
        // 1. DONOR (USER) LOGIN
        // ==========================================
        if ($role === 'user') {
            
            $sql = "SELECT donor_id, full_name, user_password FROM Donors WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['USER_PASSWORD'])) {
                $_SESSION['donor_id'] = $user['DONOR_ID'];
                $_SESSION['full_name'] = $user['FULL_NAME'];
                $_SESSION['role'] = 'donor';
                
                header("Location: user-dashboard.php"); 
                exit();
            } else {
                header("Location: login.php?error=1");
                exit();
            }
        } 
        
        // ==========================================
        // 2. ADMIN LOGIN
        // ==========================================
        else if ($role === 'admin') {
            
            $adminEmail = "sachiruushan@gmail.com";
            $adminPassword = "admin"; 
            
            if ($email === $adminEmail && $password === $adminPassword) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['role'] = 'admin';
                
                header("Location: admin-dashboard.php");
                exit();
            } else {
                header("Location: login.php?error=1");
                exit();
            }
        }

        // ==========================================
        // 3. HOSPITAL LOGIN (DATABASE & STATUS CHECK)
        // ==========================================
        else if ($role === 'hospital') {
            
            // ඩේටාබේස් එකෙන් අදාළ ඊමේල් එක ඇති රෝහල සෙවීම
            $stmt = $conn->prepare("SELECT * FROM hospitals WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $hospital = $stmt->fetch(PDO::FETCH_ASSOC);

            // Oracle සහ අනෙකුත් DB වල Case sensitivity නිසා විවිධ ආකාරයට අගයන් ලබා ගැනීම
            $dbPassword = $hospital['PASSWORD'] ?? $hospital['password'] ?? '';
            $status = $hospital['STATUS'] ?? $hospital['status'] ?? 'Pending';
            $hospitalName = $hospital['HOSPITAL_NAME'] ?? $hospital['hospital_name'] ?? 'Hospital';
            $hospitalId = $hospital['HOSPITAL_ID'] ?? $hospital['hospital_id'] ?? 1;

            // පාස්වර්ඩ් එක නිවැරදිදැයි පරීක්ෂා කිරීම
            if ($hospital && password_verify($password, $dbPassword)) {
                
                // ඇමින් විසින් අනුමත කර ඇත්දැයි (Approved) පරීක්ෂා කිරීම
                if ($status === 'Approved') {
                    $_SESSION['role'] = 'hospital';
                    $_SESSION['hospital_id'] = $hospitalId;
                    $_SESSION['hospital_name'] = $hospitalName;
                    
                    // සාර්ථකව හෝස්පිට්ල් ඩෑෂ්බෝඩ් එකට යැවීම
                    header("Location: hospital-dashboard.php");
                    exit();
                } else {
                    // ගිණුම තවමත් Pending හෝ Reject වී ඇත්නම් ලොග් වීමට නොහැක
                    header("Location: login.php?error=pending");
                    exit();
                }
            } else {
                // ඊමේල් හෝ පාස්වර්ඩ් වැරදි නම්
                header("Location: login.php?error=1");
                exit();
            }
        }

    } catch (PDOException $e) {
        die("System Error: " . $e->getMessage());
    }
} else {
    header("Location: login.php");
    exit();
}
?>