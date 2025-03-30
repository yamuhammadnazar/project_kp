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

if (!$data) {
    // Jika data tidak ditemukan, redirect ke dashboard
    header("Location: ../dashboard/admin_dashboard.php?error=task_not_found");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
    $action = $_POST['action'];
    
    if ($action == 'revisi') {
        $update_query = "UPDATE tugas_media SET 
                        catatan_admin = '$catatan',
                        status = 'Revisi',
                        link_drive = ''
                        WHERE id = $id";
        
        if(mysqli_query($conn, $update_query)) {
            header("Location: ../dashboard/admin_dashboard.php?message=revisi_success");
            exit();
        }
    } else if ($action == 'terima') {
        // Periksa apakah tugas ini dari anggota
        $check_query = "SELECT u.role FROM tugas_media t 
                        JOIN users u ON t.penanggung_jawab = u.username 
                        WHERE t.id = $id";
        $check_result = mysqli_query($conn, $check_query);
        
        $user_role = '';
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $user_data = mysqli_fetch_assoc($check_result);
            $user_role = $user_data['role'];
        }
        
        // Update status tugas menjadi Selesai
        $update_query = "UPDATE tugas_media SET 
                        catatan_admin = '$catatan',
                        status = 'Selesai'
                        WHERE id = $id";
        
        if(mysqli_query($conn, $update_query)) {
            // Jika tugas dari anggota, tandai untuk ditampilkan di dashboard kabid
            if ($user_role == 'anggota') {
                // Tambahkan flag atau update field yang menandakan tugas ini sudah diverifikasi admin
                // dan siap ditampilkan di dashboard kabid
                $verified_query = "UPDATE tugas_media SET 
                                  verified_by_admin = 1
                                  WHERE id = $id";
                mysqli_query($conn, $verified_query);
                
                header("Location: ../dashboard/admin_dashboard.php?message=accept_success_to_kabid");
            } else {
                header("Location: ../dashboard/admin_dashboard.php?message=accept_success");
            }
            exit();
        }
    }
}

// Periksa penanggung jawab tugas
$check_query = "SELECT u.role FROM tugas_media t 
                JOIN users u ON t.penanggung_jawab = u.username 
                WHERE t.id = $id";
$check_result = mysqli_query($conn, $check_query);

$user_role = '';
$show_kabid_info = false;

if ($check_result && mysqli_num_rows($check_result) > 0) {
    $user_data = mysqli_fetch_assoc($check_result);
    $user_role = $user_data['role'];
    $show_kabid_info = ($user_role == 'anggota');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Catatan Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .form-container {
            max-width: 500px;
            margin: 20px auto;
            padding: 30px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
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
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            min-height: 120px;
            resize: vertical;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .task-info {
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #4CAF50;
        }
        .task-info p {
            margin: 8px 0;
        }
        .back-link {
            display: inline-block;
            margin-left: 15px;
            color: #666;
            text-decoration: none;
            padding: 12px 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .back-link:hover {
            background: #f5f5f5;
            color: #333;
        }
        .button-group {
            margin-top: 20px;
        }
        .btn-terima {
            background-color: #4CAF50;
        }
        .btn-revisi {
            background-color: #f44336;
        }
        .btn {
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Catatan Admin</h2>
        
        <?php if($show_kabid_info): ?>
            <div class="alert alert-info">
                <strong>Info:</strong> Jika Anda menerima tugas ini, tugas akan ditampilkan di dashboard Kepala Bidang.
            </div>
        <?php endif; ?>
        
        <div class="task-info">
            <p><strong>Judul:</strong> <?php echo htmlspecialchars($data['judul']); ?></p>
            <p><strong>Platform:</strong> <?php echo htmlspecialchars($data['platform']); ?></p>
            <p><strong>PJ:</strong> <?php echo htmlspecialchars($data['penanggung_jawab']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($data['status']); ?></p>
            <?php if(!empty($data['link_drive'])): ?>
                <p><strong>Link Drive:</strong> <a href="<?php echo htmlspecialchars($data['link_drive']); ?>" target="_blank">Lihat File</a></p>
            <?php else: ?>
                <p><strong>Link Drive:</strong> Belum ada link</p>
            <?php endif; ?>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Catatan:</label>
                <textarea name="catatan" required><?php echo htmlspecialchars($data['catatan_admin'] ?? ''); ?></textarea>
            </div>
            
            <div class="button-group">
                <button type="submit" name="action" value="terima" class="btn btn-terima">Terima Tugas</button>
                <button type="submit" name="action" value="revisi" class="btn btn-revisi">Minta Revisi</button>
                <a href="../dashboard/admin_dashboard.php" class="back-link">Kembali</a>
            </div>
        </form>
    </div>
</body>
</html>
