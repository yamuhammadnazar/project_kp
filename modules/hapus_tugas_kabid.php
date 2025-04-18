<?php
include '../auth/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION["username"])) {
    header("Location: ../auth/login.php");
    exit();
}

// Cek apakah ada parameter id
if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Query untuk menghapus tugas
    $query = "DELETE FROM tugas_media WHERE id = $id";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Tugas berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus tugas!";
    }
}

// Redirect kembali ke dashboard sesuai role
if ($_SESSION["role"] == "kabid") {
    header("Location: ../modules/daftar_tugas_kabid.php");
} elseif ($_SESSION["role"] == "admin") {
    header("Location: ../dashboard/admin_dashboard.php");
} else {
    header("Location: ../dashboard/anggota_dashboard.php");
}
exit();
?>