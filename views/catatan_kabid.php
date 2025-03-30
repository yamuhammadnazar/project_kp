<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "kabid") {
    header("Location: ../auth/login.php");
    exit();
}

$id = (int)$_GET['id'];
$query = "SELECT * FROM tugas_media WHERE id = $id";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
    $action = $_POST['action'];
    
    if ($action == 'revisi') {
        $update_query = "UPDATE tugas_media SET 
                        catatan_admin = '$catatan',
                        status = 'Revisi',
                        link_drive = ''
                        WHERE id = $id";
    } else if ($action == 'terima') {
        $update_query = "UPDATE tugas_media SET 
                        catatan_admin = '$catatan',
                        status = 'Selesai'
                        WHERE id = $id";
    }
    
    if(mysqli_query($conn, $update_query)) {
        header("Location: ../dashboard/kabid_dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Catatan Kabid</title>
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
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Catatan Kabid</h2>
        
        <div class="task-info">
            <p><strong>Judul:</strong> <?php echo htmlspecialchars($data['judul']); ?></p>
            <p><strong>Platform:</strong> <?php echo htmlspecialchars($data['platform']); ?></p>
            <p><strong>PJ:</strong> <?php echo htmlspecialchars($data['penanggung_jawab']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($data['status']); ?></p>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Catatan:</label>
                <textarea name="catatan" required><?php echo htmlspecialchars($data['catatan_admin'] ?? ''); ?></textarea>
            </div>
            
            <div class="button-group">
                <button type="submit" name="action" value="terima" class="btn btn-terima">Terima Tugas</button>
                <button type="submit" name="action" value="revisi" class="btn btn-revisi">Minta Revisi</button>
                <a href="../dashboard/kabid_dashboard.php" class="back-link">Kembali</a>
            </div>
        </form>
    </div>
</body>
</html>