<?php
session_start();
session_destroy(); // Hapuskan semua data session
header("Location: login.php"); // Balik ke page login
exit();
?>