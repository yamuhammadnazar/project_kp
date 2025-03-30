<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "kabid") {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil ID tugas dari parameter URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Query untuk mendapatkan data tugas
$query_tugas = "SELECT * FROM tugas_media WHERE id = $id";
$result_tugas = mysqli_query($conn, $query_tugas);

if (mysqli_num_rows($result_tugas) == 0) {
    // Tugas tidak ditemukan
    header("Location: ../dashboard/kabid_dashboard.php");
    exit();
}

$tugas = mysqli_fetch_assoc($result_tugas);

// Query untuk mendapatkan daftar anggota
$query_anggota = "SELECT username FROM users WHERE role = 'anggota'";
$result_anggota = mysqli_query($conn, $query_anggota);

// Proses form jika disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $judul = mysqli_real_escape_string($conn, $_POST["judul"]);
    $platform = mysqli_real_escape_string($conn, $_POST["platform"]);
    $deskripsi = mysqli_real_escape_string($conn, $_POST["deskripsi"]);
    $status = mysqli_real_escape_string($conn, $_POST["status"]);
    $tanggal_mulai = $_POST["tanggal_mulai"];
    $deadline = $_POST["deadline"];
    $penanggung_jawab = mysqli_real_escape_string($conn, $_POST["penanggung_jawab"]);
    
    // Update tugas
    $query = "UPDATE tugas_media SET 
              judul = '$judul',
              platform = '$platform',
              deskripsi = '$deskripsi',
              status = '$status',
              tanggal_mulai = '$tanggal_mulai',
              deadline = '$deadline',
              penanggung_jawab = '$penanggung_jawab'
              WHERE id = $id";
    
    if(mysqli_query($conn, $query)) {
        $success_message = "✅ Tugas berhasil diperbarui!";
        // Refresh data tugas
        $result_tugas = mysqli_query($conn, $query_tugas);
        $tugas = mysqli_fetch_assoc($result_tugas);
    } else {
        $error_message = "❌ Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Tugas Anggota</title>
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
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Edit Tugas Anggota</h2>
        
        <?php if(isset($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Judul Tugas:</label>
                <input type="text" name="judul" value="<?php echo htmlspecialchars($tugas['judul']); ?>" required>
            </div>

            <div class="form-group">
                <label>Platform:</label>
                <select name="platform" required>
                    <option value="Pemberitahuan">Pemberitahuan</option>
                    <option value="Tugas">Tugas</option>
                    <option value="Laporan">Laporan</option>
                </select>
            </div>

            <div class="form-group">
                <label>Deskripsi:</label>
                <textarea name="deskripsi" required><?php echo htmlspecialchars($tugas['deskripsi']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Status:</label>
                <select name="status" required>
                    <option value="Belum Dikerjakan" <?php if($tugas['status'] == 'Belum Dikerjakan') echo 'selected'; ?>>Belum Dikerjakan</option>
                    <option value="Sedang Dikerjakan" <?php if($tugas['status'] == 'Sedang Dikerjakan') echo 'selected'; ?>>Sedang Dikerjakan</option>
                    <option value="Selesai" <?php if($tugas['status'] == 'Selesai') echo 'selected'; ?>>Selesai</option>
                </select>
            </div>

            <div class="form-group">
                <label>Tanggal Mulai:</label>
                <input type="date" name="tanggal_mulai" value="<?php echo $tugas['tanggal_mulai']; ?>" required>
            </div>

            <div class="form-group">
                <label>Deadline:</label>
                <input type="date" name="deadline" value="<?php echo $tugas['deadline']; ?>" required>
            </div>

            <div class="form-group">
                <label>Penanggung Jawab:</label>
                <select name="penanggung_jawab" required>
                    <?php 
                    mysqli_data_seek($result_anggota, 0); // Reset pointer
                    while($row = mysqli_fetch_assoc($result_anggota)): 
                    ?>
                        <option value="<?php echo htmlspecialchars($row['username']); ?>" <?php if($tugas['penanggung_jawab'] == $row['username']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($row['username']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit">Perbarui Tugas</button>
        </form>
        
        <a href="../dashboard/kabid_dashboard.php" class="back-link">Kembali ke Dashboard</a>
    </div>
</body>
</html>