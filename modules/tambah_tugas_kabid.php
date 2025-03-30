<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "kabid") {
    header("Location: ../auth/login.php");
    exit();
}

// Query untuk mendapatkan daftar admin
$query_admin = "SELECT username FROM users WHERE role = 'admin'";
$result_admin = mysqli_query($conn, $query_admin);

// Simpan daftar admin dalam array untuk digunakan nanti jika "Semua Staf" dipilih
$admin_list = array();
while($row = mysqli_fetch_assoc($result_admin)) {
    $admin_list[] = $row['username'];
}
// Reset pointer hasil query untuk digunakan di dropdown
mysqli_data_seek($result_admin, 0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $judul = mysqli_real_escape_string($conn, $_POST["judul"]);
    $platform = mysqli_real_escape_string($conn, $_POST["platform"]);
    $deskripsi = mysqli_real_escape_string($conn, $_POST["deskripsi"]);
    $tanggal_mulai = $_POST["tanggal_mulai"];
    $deadline = $_POST["deadline"];
    $penanggung_jawab = $_POST["penanggung_jawab"];
    
    $success_count = 0;
    $error_messages = array();
    
    // Jika "Semua Staf" dipilih, buat tugas untuk setiap admin
    if ($penanggung_jawab === "semua_staf") {
        foreach ($admin_list as $admin) {
            $query = "INSERT INTO tugas_media (judul, platform, deskripsi, status, tanggal_mulai, deadline, penanggung_jawab, pemberi_tugas) 
                      VALUES ('$judul', '$platform', '$deskripsi', 'Belum Dikerjakan', '$tanggal_mulai', '$deadline', '$admin', 'kabid')";
            
            if(mysqli_query($conn, $query)) {
                $success_count++;
            } else {
                $error_messages[] = "Error untuk $admin: " . mysqli_error($conn);
            }
        }
        
        if ($success_count == count($admin_list)) {
            $success_message = "✅ Tugas berhasil ditambahkan untuk semua staf!";
        } else {
            $error_message = "❌ Beberapa tugas gagal ditambahkan: " . implode(", ", $error_messages);
        }
    } else {
        // Jika admin tertentu dipilih, buat tugas hanya untuk admin tersebut
        $query = "INSERT INTO tugas_media (judul, platform, deskripsi, status, tanggal_mulai, deadline, penanggung_jawab, pemberi_tugas) 
                  VALUES ('$judul', '$platform', '$deskripsi', 'Belum Dikerjakan', '$tanggal_mulai', '$deadline', '$penanggung_jawab', 'kabid')";
        
        if(mysqli_query($conn, $query)) {
            $success_message = "✅ Tugas berhasil ditambahkan!";
        } else {
            $error_message = "❌ Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Tugas Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input[type="text"],
        textarea,
        select,
        input[type="date"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background: #45a049;
        }
        .success-message {
            color: green;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 4px;
        }
        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #2196F3;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .all-staff-option {
            font-weight: bold;
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Tambah Tugas Admin</h2>
        
        <?php if(isset($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Judul Tugas:</label>
                <input type="text" name="judul" required>
            </div>

            <div class="form-group">
                <label>Kategori:</label>
                <select name="platform" required>
                    <option value="Pemberitahuan">Pemberitahuan</option>
                    <option value="Tugas">Tugas</option>
                    <option value="Laporan">Laporan</option>
                </select>
            </div>

            <div class="form-group">
                <label>Deskripsi:</label>
                <textarea name="deskripsi" required></textarea>
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
                <label>Penanggung Jawab:</label>
                <select name="penanggung_jawab" required>
                    <option value="semua_staf" class="all-staff-option">Semua Staf</option>
                    <?php while($row = mysqli_fetch_assoc($result_admin)): ?>
                        <option value="<?php echo htmlspecialchars($row['username']); ?>">
                            <?php echo htmlspecialchars($row['username']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit">Tambah Tugas</button>
        </form>
        
        <a href="../dashboard/kabid_dashboard.php" class="back-link">Kembali ke Dashboard</a>
    </div>
</body>
</html>
