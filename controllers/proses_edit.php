<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"])) {
    header("Location: ../auth/login.php");
    exit();
}

$id = (int)$_POST['id'];
$judul = mysqli_real_escape_string($conn, $_POST['judul']);
$platform = mysqli_real_escape_string($conn, $_POST['platform']);
$deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
$status = mysqli_real_escape_string($conn, $_POST['status']);
$tanggal_mulai = mysqli_real_escape_string($conn, $_POST['tanggal_mulai']);
$deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
$link_drive = $_POST['link_drive'];

// Validasi link drive
if (!empty($link_drive)) {
    if (!filter_var($link_drive, FILTER_VALIDATE_URL) || 
        !preg_match('/drive\.google\.com/', $link_drive)) {
        $_SESSION['error'] = "Format link Google Drive tidak valid!";
        header("Location: ../modules/edit.php?id=" . $id);
        exit();
    }
}

$query = "UPDATE tugas_media SET 
            judul = '$judul',
            platform = '$platform',
            deskripsi = '$deskripsi',
            status = '$status',
            tanggal_mulai = '$tanggal_mulai',
            deadline = '$deadline',
            link_drive = '$link_drive'
          WHERE id = $id";

if(mysqli_query($conn, $query)) {
    $_SESSION['success'] = "Data berhasil diupdate!";
    header("Location: ../dashboard/admin_dashboard.php");
} else {
    $_SESSION['error'] = "Gagal mengupdate data!";
    header("Location: ../modules/edit.php?id=" . $id);
}
exit();
?>