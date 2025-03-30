<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "kabid") {
    header("Location: ../auth/login.php");
    exit();
}

$query = "SELECT * FROM users WHERE role = 'admin' ORDER BY username ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f5f5f5;
        }
        .tombol-edit {
            background: #2196F3;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .tombol-hapus {
            background: #ff4444;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        .tombol-hapus:hover {
            background: #cc0000;
        }
        .pesan-sukses {
            color: green;
            padding: 15px;
            margin-bottom: 20px;
            background: #e8f5e9;
            border-radius: 4px;
        }
        .pesan-gagal {
            color: red;
            padding: 15px;
            margin-bottom: 20px;
            background: #ffebee;
            border-radius: 4px;
        }
        .kembali-link {
            display: inline-block;
            margin-top: 20px;
            color: #2196F3;
            text-decoration: none;
        }
        .kembali-link:hover {
            text-decoration: underline;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .modal-content {
            background: white;
            margin: 15% auto;
            padding: 20px;
            width: 40%;
            border-radius: 8px;
        }
        .close {
            float: right;
            cursor: pointer;
            font-size: 24px;
        }
        .modal input[type="text"],
        .modal input[type="password"] {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .modal button {
            background: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        .modal button:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Kelola Admin</h2>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="pesan-sukses"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="pesan-gagal"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <table>
            <tr>
                <th>Nama Pengguna</th>
                <th>Aksi</th>
            </tr>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td>
                    <a href="#" class="tombol-edit" onclick="openModal(<?php echo $row['id']; ?>, '<?php echo $row['username']; ?>')">
                        Edit
                    </a>
                    <a href="hapus_admin.php?id=<?php echo $row['id']; ?>" 
                       class="tombol-hapus" 
                       onclick="return confirm('Yakin ingin menghapus admin ini?')">
                        Hapus
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        
        <a href="../dashboard/kabid_dashboard.php" class="kembali-link">Kembali ke Dashboard</a>
    </div>

    <!-- Modal Edit Username dan Reset Password -->
    <div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Edit Username</h3>
        <form action="../controllers/update_username_admin.php" method="POST">
            <input type="hidden" id="userId" name="id">
            <input type="text" id="newUsername" name="new_username" placeholder="Username baru" required>
            <button type="submit">Simpan Username</button>
        </form>

        <hr style="margin: 20px 0;">
        
        <h3>Reset Password</h3>
        <form action="../controllers/update_password_admin.php" method="POST">
            <input type="hidden" id="userIdReset" name="id">
            <input type="password" name="new_password" placeholder="Password baru" required>
            <button type="submit" style="background: #ff9800;">Reset Password</button>
        </form>
    </div>
</div>

    <script>
        function openModal(id, username) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('userId').value = id;
            document.getElementById('userIdReset').value = id;
            document.getElementById('newUsername').value = username;
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>