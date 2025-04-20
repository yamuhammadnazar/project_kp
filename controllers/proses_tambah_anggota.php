<?php
include '../auth/koneksi.php';

// Cek session
if (!isset($_SESSION["username"])) {
    header("Location: ../auth/login.php");
    exit();
}

// Sanitasi input
$judul = mysqli_real_escape_string($conn, $_POST['judul']);
$platform = mysqli_real_escape_string($conn, $_POST['platform']);
$deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
$status = mysqli_real_escape_string($conn, $_POST['status']);
$tanggal_mulai = mysqli_real_escape_string($conn, $_POST['tanggal_mulai']);
$deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
$link_drive = mysqli_real_escape_string($conn, $_POST['link_drive']);
$penanggung_jawab = mysqli_real_escape_string($conn, $_POST['penanggung_jawab']);
$pemberi_tugas = mysqli_real_escape_string($conn, $_POST['pemberi_tugas']);

// Tambahkan pemberi_tugas_id dari session
$pemberi_tugas_id = $_SESSION["user_id"];

// Query insert dengan pemberi_tugas_id
$query = "INSERT INTO tugas_media (judul, platform, deskripsi, status, tanggal_mulai, deadline, link_drive, penanggung_jawab, pemberi_tugas, pemberi_tugas_id)
          VALUES ('$judul', '$platform', '$deskripsi', '$status', '$tanggal_mulai', '$deadline', '$link_drive', '$penanggung_jawab', '$pemberi_tugas', $pemberi_tugas_id)";

// Eksekusi query
if(mysqli_query($conn, $query)) {
    if ($_SESSION["role"] == "admin") {
        header("Location: ../modules/tambah_tugas_anggota.php");
    } else {
        header("Location: ../modules/tambah_tugas_anggota.php");
    }
    exit();
} else {
    $_SESSION['error'] = "Terjadi kesalahan: " . mysqli_error($conn);
    if ($_SESSION["role"] == "admin") {
        header("Location: ../modules/tambah_tugas_anggota.php");
    } else {
        header("Location: ../modules/tambah_tugas_anggota.php");
    }
    exit();
}
?>
