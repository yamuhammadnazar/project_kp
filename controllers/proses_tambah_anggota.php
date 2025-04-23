<?php
include '../auth/koneksi.php';

// Cek session
if (!isset($_SESSION["username"])) {
    header("Location: ../auth/login.php");
    exit();
}

// Initialize errors array
$errors = [];

// Validate required fields
if (empty($_POST['judul'])) {
    $errors[] = "Judul tugas wajib diisi";
}

if (empty($_POST['platform'])) {
    $errors[] = "Platform wajib dipilih";
}

if (empty($_POST['deskripsi'])) {
    $errors[] = "Deskripsi wajib diisi";
}

if (empty($_POST['penanggung_jawab'])) {
    $errors[] = "Penanggung jawab wajib dipilih";
}

if (empty($_POST['status'])) {
    $errors[] = "Status wajib dipilih";
}

if (empty($_POST['tanggal_mulai'])) {
    $errors[] = "Tanggal mulai wajib diisi";
}

if (empty($_POST['deadline'])) {
    $errors[] = "Deadline wajib diisi";
}

// Validate date logic
if (!empty($_POST['tanggal_mulai']) && !empty($_POST['deadline'])) {
    $start_date = new DateTime($_POST['tanggal_mulai']);
    $end_date = new DateTime($_POST['deadline']);

    if ($end_date < $start_date) {
        $errors[] = "Deadline tidak boleh lebih awal dari tanggal mulai";
    }
}

// If there are validation errors, redirect back to the form
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: ../modules/tambah_tugas_anggota.php");
    exit();
}

// If validation passes, proceed with sanitization and database insertion
$judul = mysqli_real_escape_string($conn, $_POST['judul']);
$platform = mysqli_real_escape_string($conn, $_POST['platform']);
$deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
$status = mysqli_real_escape_string($conn, $_POST['status']);
$tanggal_mulai = mysqli_real_escape_string($conn, $_POST['tanggal_mulai']);
$deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
$link_drive = mysqli_real_escape_string($conn, $_POST['link_drive'] ?? '');
$penanggung_jawab = mysqli_real_escape_string($conn, $_POST['penanggung_jawab']);
$pemberi_tugas = mysqli_real_escape_string($conn, $_POST['pemberi_tugas']);

// Tambahkan pemberi_tugas_id dari session
$pemberi_tugas_id = $_SESSION["user_id"];

// Better approach: Use prepared statements to prevent SQL injection
$query = "INSERT INTO tugas_media (judul, platform, deskripsi, status, tanggal_mulai, deadline, link_drive, penanggung_jawab, pemberi_tugas, pemberi_tugas_id) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sssssssssi", $judul, $platform, $deskripsi, $status, $tanggal_mulai, $deadline, $link_drive, $penanggung_jawab, $pemberi_tugas, $pemberi_tugas_id);

// Execute the query
if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = "Tugas berhasil ditambahkan";
    if ($_SESSION["role"] == "admin") {
        header("Location: ../dashboard/admin_dashboard.php");
    } else {
        header("Location: ../dashboard/anggota_dashboard.php");
    }
    exit();
} else {
    $_SESSION['error'] = "Terjadi kesalahan: " . mysqli_error($conn);
    header("Location: ../modules/tambah_tugas_anggota.php");
    exit();
}
?>