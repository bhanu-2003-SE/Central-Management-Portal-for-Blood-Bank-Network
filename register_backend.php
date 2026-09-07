<?php
// Database Connection එක ලින්ක් කරගන්න
require_once 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Form එකෙන් එන දත්ත අරගැනීම
    $fullName         = $_POST['fullName'] ?? '';
    $nic              = $_POST['nic'] ?? '';
    $dob              = $_POST['dob'] ?? '';
    $gender           = $_POST['gender'] ?? '';
    $bloodGroup       = $_POST['bloodGroup'] ?? '';
    $weight           = (int)($_POST['weight'] ?? 0);
    $lastDonation     = $_POST['lastDonation'] ?? '';
    $conditions       = $_POST['conditions'] ?? 'None';
    $district         = $_POST['district'] ?? '';
    $city             = $_POST['city'] ?? '';
    $mobile           = $_POST['mobile'] ?? '';
    $emergencyContact = $_POST['emergencyContact'] ?? '';
    $email            = $_POST['email'] ?? '';
    $rawPassword      = $_POST['password'] ?? '';

    // Password එක Secure කිරීම
    $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

    // Stored Procedure එක Call කිරීම සඳහා SQL එක
    // ඔයාගේ Procedure එකේ පරාමිතීන් (parameters) පිළිවෙලට මෙතන තියෙන්න ඕනේ
    $sql = "BEGIN 
                register_new_donor(
                    :full_name, :nic, TO_DATE(:dob, 'YYYY-MM-DD'), :gender, :blood_group, 
                    :weight, :last_donation, :conditions, :district, :city, 
                    :mobile, :emergency, :email, :password
                ); 
            END;";
            
    try {
        $stmt = $conn->prepare($sql);

        // Parameters Bind කිරීම
        $stmt->bindParam(':full_name', $fullName);
        $stmt->bindParam(':nic', $nic);
        $stmt->bindParam(':dob', $dob);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':blood_group', $bloodGroup);
        $stmt->bindParam(':weight', $weight);
        $stmt->bindParam(':last_donation', $lastDonation);
        $stmt->bindParam(':conditions', $conditions);
        $stmt->bindParam(':district', $district);
        $stmt->bindParam(':city', $city);
        $stmt->bindParam(':mobile', $mobile);
        $stmt->bindParam(':emergency', $emergencyContact);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);

        // Procedure එක Run කිරීම
        $stmt->execute();

        // සාර්ථකව සේව් වුණාම Login පිටුවට යැවීම
        echo "<script>
                alert('Registration Successful! Welcome to LifeLine Connect.');
                window.location.href = 'login.php';
              </script>";

    } catch (PDOException $e) {
        // Error එකක් ආවොත් ඒක අල්ලගැනීම (උදා: NIC හෝ Email කලින් තියෙනවා නම්)
        if (strpos($e->getMessage(), 'ORA-00001') !== false) {
            echo "<script>
                    alert('Error: This NIC or Email is already registered.');
                    window.history.back();
                  </script>";
        } else {
            echo "Error executing query: " . $e->getMessage();
        }
    }
}
?>