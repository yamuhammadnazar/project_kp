<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "kabid") {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int)$_POST['id'];
    $new_username = mysqli_real_escape_string($conn, $_POST['new_username']);
    
    $query = "UPDATE users SET username = '$new_username' WHERE id = $id AND role = 'admin'";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Username berhasil diperbarui!";
    } else {
        $_SESSION['error'] = "Gagal memperbarui username!";
    }
}

header("Location: ../modules/kelola_admin.php");
exit();
?>