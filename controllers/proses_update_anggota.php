<?php
include '../auth/koneksi.php';

// Validate session
if (!isset($_SESSION["username"])) {
    header("Location: ../auth/login.php");
    exit();
}

// Sanitize inputs
$id = (int)$_POST['id'];
$judul = mysqli_real_escape_string($conn, $_POST['judul']);
$platform = mysqli_real_escape_string($conn, $_POST['platform']);
$deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
$status = mysqli_real_escape_string($conn, $_POST['status']);
$tanggal_mulai = mysqli_real_escape_string($conn, $_POST['tanggal_mulai']);
$deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
$link_drive = mysqli_real_escape_string($conn, $_POST['link_drive']);

// Pastikan tidak mengubah pemberi_tugas_id
$query_check = "SELECT pemberi_tugas, pemberi_tugas_id FROM tugas_media WHERE id = $id";
$result_check = mysqli_query($conn, $query_check);
$task = mysqli_fetch_assoc($result_check);

// Update query
$query = "UPDATE tugas_media SET 
            judul = '$judul',
            platform = '$platform',
            deskripsi = '$deskripsi',
            status = '$status',
            tanggal_mulai = '$tanggal_mulai',
            deadline = '$deadline',
            link_drive = '$link_drive'
          WHERE id = $id";

// Execute and redirect
if(mysqli_query($conn, $query)) {
    $_SESSION['success'] = "Tugas berhasil diperbarui!";
    header("Location: ../dashboard/anggota_dashboard.php");
    exit();
} else {
    $_SESSION['error'] = "Gagal memperbarui tugas: " . mysqli_error($conn);
    header("Location: ../dashboard/anggota_dashboard.php");
    exit();
}
?>
