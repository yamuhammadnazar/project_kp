<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$id = (int)$_GET['id'];
$query = "SELECT * FROM tugas_media WHERE id = $id";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Tugas</title>
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
            height: 120px;
            resize: vertical;
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
        .back-link {
            display: inline-block;
            padding: 12px 20px;
            color: #666;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .back-link:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Edit Tugas</h2>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        <form action="../controllers/proses_edit.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
            
            <div class="form-group">
                <label>Judul:</label>
                <input type="text" name="judul" value="<?php echo htmlspecialchars($data['judul']); ?>" required>
            </div>

            <div class="form-group">
                <label>Platform:</label>
                <select name="platform" required>
                    <option value="Instagram" <?php echo ($data['platform'] == 'Instagram') ? 'selected' : ''; ?>>Instagram</option>
                    <option value="Facebook" <?php echo ($data['platform'] == 'Facebook') ? 'selected' : ''; ?>>Facebook</option>
                    <option value="Twitter" <?php echo ($data['platform'] == 'Twitter') ? 'selected' : ''; ?>>Twitter</option>
                    <option value="TikTok" <?php echo ($data['platform'] == 'TikTok') ? 'selected' : ''; ?>>TikTok</option>
                    <option value="YouTube" <?php echo ($data['platform'] == 'YouTube') ? 'selected' : ''; ?>>YouTube</option>
                    <option value="LinkedIn" <?php echo ($data['platform'] == 'LinkedIn') ? 'selected' : ''; ?>>LinkedIn</option>
                    <option value="Website" <?php echo ($data['platform'] == 'Website') ? 'selected' : ''; ?>>Website</option>
                </select>
            </div>

            <div class="form-group">
                <label>Deskripsi:</label>
                <textarea name="deskripsi" required><?php echo htmlspecialchars($data['deskripsi']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Status:</label>
                <select name="status" required>
                    <option value="Belum Dikerjakan" <?php echo ($data['status'] == 'Belum Dikerjakan') ? 'selected' : ''; ?>>Belum Dikerjakan</option>
                    <option value="Sedang Dikerjakan" <?php echo ($data['status'] == 'Sedang Dikerjakan') ? 'selected' : ''; ?>>Sedang Dikerjakan</option>
                    <option value="Selesai" <?php echo ($data['status'] == 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                </select>
            </div>

            <div class="form-group">
                <label>Tanggal Mulai:</label>
                <input type="date" name="tanggal_mulai" value="<?php echo $data['tanggal_mulai']; ?>" required>
            </div>

            <div class="form-group">
                <label>Deadline:</label>
                <input type="date" name="deadline" value="<?php echo $data['deadline']; ?>" required>
            </div>

            <div class="form-group">
                <label>Link Drive:</label>
                <input type="text" name="link_drive" value="<?php echo htmlspecialchars($data['link_drive']); ?>" placeholder="Masukkan link Google Drive">
            </div>

            <button type="submit">Update Tugas</button>
            <a href="../dashboard/admin_dashboard.php" class="back-link">Kembali</a>
        </form>
    </div>
</body>
</html>