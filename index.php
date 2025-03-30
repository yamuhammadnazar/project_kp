<?php
session_start();
include 'koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION["username"];
$role = $_SESSION["role"];

// Cek hak akses: Admin bisa melihat semua tugas, anggota hanya tugas mereka
if ($role === "admin") {
    $query = "SELECT * FROM tugas_media";  
} else {
    $query = "SELECT * FROM tugas_media WHERE penanggung_jawab='$username'";
}

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error: " . mysqli_error($conn)); // Menampilkan error jika query gagal
}
?>

<!DOCTYPE html>
<html>
<head><title>Dashboard Tugas</title></head>
<body>
    <h1>Dashboard Tugas Media Sosial</h1>
    <a href="tambah.php">Tambah Tugas</a> |
    <a href="profile.php">Ganti Password</a> |
    <a href="logout.php">Logout</a>
    <table border="1">
        <tr>
            <th>Judul</th><th>Deskripsi</th><th>Platform</th><th>Deadline</th><th>Penanggung Jawab</th><th>Status</th><th>Aksi</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <tr>
            <td><?php echo $row['judul']; ?></td>
            <td><?php echo $row['deskripsi']; ?></td>
            <td><?php echo $row['platform']; ?></td>
            <td><?php echo $row['deadline']; ?></td>
            <td><?php echo $row['penanggung_jawab']; ?></td>
            <td><?php echo $row['status']; ?></td>
            <td>
                <a href="update.php?id=<?php echo $row['id']; ?>">Selesaikan</a> |
                <a href="hapus.php?id=<?php echo $row['id']; ?>">Hapus</a>
            </td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>
