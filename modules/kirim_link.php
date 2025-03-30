<?php
include '../auth/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitasi input
    $id = (int)$_POST['id'];
    $link = mysqli_real_escape_string($conn, $_POST['link']);
    
    // Validasi URL sederhana
    if (!empty($link) && !filter_var($link, FILTER_VALIDATE_URL)) {
        $_SESSION['error'] = "URL tidak valid! Pastikan dimulai dengan http:// atau https://";
        header("Location: ../dashboard/anggota_dashboard.php");
        exit();
    }
    
    // Query update - ubah link_drive menjadi link jika nama kolom di database sudah diubah
    $query = "UPDATE tugas_media SET link_drive = '$link' WHERE id = $id";
    
    // Eksekusi query dan redirect
    if(mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Link berhasil diperbarui!";
        header("Location: ../dashboard/anggota_dashboard.php");
    } else {
        $_SESSION['error'] = "Gagal memperbarui link!";
        header("Location: ../dashboard/anggota_dashboard.php");
    }
    exit();
}
?>