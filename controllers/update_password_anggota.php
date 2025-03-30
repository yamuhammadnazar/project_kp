<?php

include '../auth/koneksi.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION["username"]) || ($_SESSION["role"] !== "admin" && $_SESSION["role"] !== "kabid")) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int)$_POST['id'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    
    $query = "UPDATE users SET password = '$new_password' WHERE id = $id AND role = 'anggota'";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Password anggota berhasil direset!";
    } else {
        $_SESSION['error'] = "Gagal mereset password anggota!";
    }
}

// Redirect back to the member management page
header("Location: ../modules/kelola_akun.php");
exit();
?>
