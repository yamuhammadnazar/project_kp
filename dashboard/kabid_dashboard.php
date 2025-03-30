<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "kabid") {
    header("Location: ../auth/login.php");
    exit();
}

// Query untuk tugas admin
$query_admin = "SELECT t.* FROM tugas_media t 
                JOIN users u ON t.penanggung_jawab = u.username 
                WHERE u.role = 'admin' 
                ORDER BY t.tanggal_mulai DESC";
$result_admin = mysqli_query($conn, $query_admin);

// Query untuk tugas anggota yang sudah selesai dan diverifikasi oleh admin
// Di kabid_dashboard.php
$query_anggota = "SELECT t.* FROM tugas_media t 
                  JOIN users u ON t.penanggung_jawab = u.username 
                  WHERE u.role = 'anggota' AND t.status = 'Selesai' AND t.verified_by_admin = 1
                  ORDER BY t.tanggal_mulai DESC";
$result_anggota = mysqli_query($conn, $query_anggota);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Kepala Bidang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        nav {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        nav a {
            text-decoration: none;
            color: #333;
            padding: 8px 15px;
            margin-right: 10px;
        }
        nav a:hover {
            background: #f0f0f0;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f5f5f5;
            font-weight: bold;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .aksi-link {
            color: #2196F3;
            text-decoration: none;
            margin-right: 8px;
        }
        .aksi-link:hover {
            text-decoration: underline;
        }
        .hapus-link {
            color: #f44336;
        }
        .section-title {
            background-color: #2196F3;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 20px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dashboard Kepala Bidang</h1>
        
        <nav>
            <a href="../modules/tambah_tugas_kabid.php">Tambah Tugas</a>
            <a href="../auth/register_admin.php">Tambah Admin Staf</a>
            <a href="../modules/kelola_admin.php">Kelola Admin Staff</a>
            <a href="../auth/logout.php">Keluar</a>
        </nav>

        <!-- Admin Tasks Section -->
        <h2 class="section-title">Tugas Admin</h2>
        <table>
            <tr>
                <th>Judul</th>
                <th>Deskripsi</th>
                <th>Status</th>
                <th>Deadline</th>
                <th>Link Drive</th>
                <th>Penanggung Jawab</th>
                <th>Aksi</th>
            </tr>
            <?php if(mysqli_num_rows($result_admin) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result_admin)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['judul']); ?></td>
                    <td><?php echo htmlspecialchars($row['deskripsi']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo date('d-m-Y', strtotime($row['deadline'])); ?></td>
                    <td><?php echo $row['link_drive'] ? '<a href="'.htmlspecialchars($row['link_drive']).'" target="_blank">Lihat File</a>' : 'Belum ada link'; ?></td>
                    <td><?php echo htmlspecialchars($row['penanggung_jawab']); ?></td>
                    <td>
                        <a href="../modules/edit_tugas_kabid.php?id=<?php echo $row['id']; ?>" class="aksi-link">Edit</a>
                        <a href="../views/catatan_kabid.php?id=<?php echo $row['id']; ?>" class="aksi-link">Catatan</a>
                        <a href="../modules/hapus_tugas_kabid.php?id=<?php echo $row['id']; ?>" class="aksi-link hapus-link" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                    </td>
                </tr>
                <?php } ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Tidak ada tugas untuk admin</td>
                </tr>
            <?php endif; ?>
        </table>

        <!-- Anggota Tasks Section -->
        <h2 class="section-title">Tugas Anggota (Selesai)</h2>
        <table>
            <tr>
                <th>Judul</th>
                <th>Platform</th>
                <th>Deskripsi</th>
                <th>Status</th>
                <th>Link Drive</th>
                <th>Penanggung Jawab</th>
                <th>Aksi</th>
            </tr>
            <?php if(mysqli_num_rows($result_anggota) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result_anggota)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['judul']); ?></td>
                    <td><?php echo htmlspecialchars($row['platform']); ?></td>
                    <td><?php echo htmlspecialchars($row['deskripsi']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo $row['link_drive'] ? '<a href="'.htmlspecialchars($row['link_drive']).'" target="_blank">Lihat File</a>' : 'Belum ada link'; ?></td>
                    <td><?php echo htmlspecialchars($row['penanggung_jawab']); ?></td>
                    <td>
                        <a href="../modules/edit.php?id=<?php echo $row['id']; ?>" class="aksi-link">Edit</a>
                        <a href="../views/catatan_admin.php?id=<?php echo $row['id']; ?>" class="aksi-link">Catatan</a>
                        <a href="../modules/hapus_tugas.php?id=<?php echo $row['id']; ?>" class="aksi-link hapus-link" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                    </td>
                </tr>
                <?php } ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Tidak ada tugas selesai untuk anggota</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>
