<?php
include '../auth/koneksi.php';

// Validasi admin
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

// Sanitasi input ID
$id = (int)$_GET['id'];

// Query hapus user
$query = "DELETE FROM users WHERE id = $id";

// Eksekusi dan redirect
if(mysqli_query($conn, $query)) {
    $_SESSION['success'] = "Akun berhasil dihapus!";
    header("Location: kelola_akun.php");
    exit();
} else {
    $_SESSION['error'] = "Gagal menghapus akun!";
    header("Location: kelola_akun.php");
    exit();
}
?>