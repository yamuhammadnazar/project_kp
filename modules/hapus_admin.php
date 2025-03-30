<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "kabid") {
    header("Location: ../auth/login.php");
    exit();
}

if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $query = "DELETE FROM users WHERE id = $id AND role = 'admin'";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Admin berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus admin!";
    }
}

header("Location: kelola_admin.php");
exit();
?>