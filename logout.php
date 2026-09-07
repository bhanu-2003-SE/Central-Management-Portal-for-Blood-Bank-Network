<?php
session_start();
session_unset();    // Session එකේ තියෙන දත්ත ඔක්කොම අයින් කරනවා
session_destroy();  // Session එක සම්පූර්ණයෙන්ම මකනවා

// ලොග් අවුට් වුණාට පස්සේ ආපහු login පිටුවට යවනවා
header("Location: login.php");
exit();
?>