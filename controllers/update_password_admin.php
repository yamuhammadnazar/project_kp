<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "kabid") {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int)$_POST['id'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    
    $query = "UPDATE users SET password = '$new_password' WHERE id = $id AND role = 'admin'";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Password berhasil direset!";
    } else {
        $_SESSION['error'] = "Gagal mereset password!";
    }
}

header("Location: ../modules/kelola_admin.php");
exit();
?>