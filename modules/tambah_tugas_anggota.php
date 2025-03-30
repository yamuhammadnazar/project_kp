<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION["username"];

// Query untuk mendapatkan daftar anggota
$query_anggota = "SELECT username FROM users WHERE role = 'anggota'";
$result_anggota = mysqli_query($conn, $query_anggota);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Tugas Baru</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        input[type="text"], textarea, input[type="date"], select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        select {
            background-color: white;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        button:hover {
            background: #45a049;
        }
        .kembali-link {
            display: inline-block;
            padding: 12px 20px;
            color: #666;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .kembali-link:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Tambah Tugas Baru</h2>
        <form action="../controllers/proses_tambah_anggota.php" method="POST">
            <div class="form-group">
                <label>Judul:</label>
                <input type="text" name="judul" required>
            </div>

            <div class="form-group">
                <label>Platform:</label>
                <select name="platform" required>
                    <option value="">Pilih Platform</option>
                    <option value="Instagram">Instagram</option>
                    <option value="Facebook">Facebook</option>
                    <option value="Twitter">Twitter</option>
                    <option value="TikTok">TikTok</option>
                    <option value="YouTube">YouTube</option>
                    <option value="LinkedIn">LinkedIn</option>
                    <option value="Website">Website</option>
                </select>
            </div>

            <div class="form-group">
                <label>Deskripsi:</label>
                <textarea name="deskripsi" required></textarea>
            </div>

            <div class="form-group">
                <label>Status:</label>
                <select name="status" required>
                    <option value="Belum Dikerjakan">Belum Dikerjakan</option>
                    <option value="Sedang Dikerjakan">Sedang Dikerjakan</option>
                    <option value="Kirim">Kirim</option>
                </select>
            </div>

            <div class="form-group">
                <label>Tanggal Mulai:</label>
                <input type="date" name="tanggal_mulai" required>
            </div>

            <div class="form-group">
                <label>Deadline:</label>
                <input type="date" name="deadline" required>
            </div>

            <div class="form-group">
                <label>Link Drive:</label>
                <input type="text" name="link_drive" placeholder="Masukkan link Google Drive">
            </div>

            <div class="form-group">
                <label>Penanggung Jawab:</label>
                <select name="penanggung_jawab" required>
                    <?php while($row = mysqli_fetch_assoc($result_anggota)): ?>
                        <option value="<?php echo htmlspecialchars($row['username']); ?>">
                            <?php echo htmlspecialchars($row['username']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <input type="hidden" name="pemberi_tugas" value="admin">
            
            <button type="submit">Simpan Tugas</button>
            <a href="../dashboard/admin_dashboard.php" class="kembali-link">Kembali</a>
        </form>
    </div>
</body>
</html>