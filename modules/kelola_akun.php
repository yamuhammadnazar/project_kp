<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}
$username = $_SESSION['username'];
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$query = "SELECT * FROM users WHERE 
          ((role = 'anggota' AND username LIKE '%$search%') 
          OR (role = 'admin' AND username = '$username')) 
          ORDER BY role ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Akun</title>
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
        .admin-badge {
            background: #4CAF50;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }
        .anggota-badge {
            background: #2196F3;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
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
        .tombol-edit {
            background: #2196F3;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .tombol-edit:hover {
            background: #1976D2;
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
        .modal input[type="text"] {
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
        }
        .tombol-reset {
            background: #ff9800;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .tombol-reset:hover {
            background: #f57c00;
        }
        .search-box {
    margin-bottom: 20px;
    }
    .search-box input[type="text"] {
        padding: 8px;
        width: 200px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-right: 10px;
    }
    .search-box button {
        padding: 8px 15px;
        background: #4CAF50;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .search-box button:hover {
        background: #45a049;
    }

    </style>
</head>
<body>
    <div class="container">
        <h2>Kelola Semua Akun</h2>
        <div class="search-box">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Cari username..." value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
                <button type="submit">Cari</button>
            </form>
        </div>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="pesan-sukses"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="pesan-gagal"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <table>
            <tr>
                <th>Nama Pengguna</th>
                <th>Peran</th>
                <th>Aksi</th>
            </tr>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td>
                    <span class="<?php echo $row['role'] == 'admin' ? 'admin-badge' : 'anggota-badge'; ?>">
                        <?php echo ucfirst($row['role']); ?>
                    </span>
                </td>
                <td>
                    <?php if($row['role'] == 'admin'): ?>
                        <a href="#" class="tombol-edit" onclick="openModal(<?php echo $row['id']; ?>, '<?php echo $row['username']; ?>')">
                            Edit
                        </a>
                    <?php elseif($row['role'] == 'anggota'): ?>
                        <a href="#" class="tombol-edit" onclick="openModal(<?php echo $row['id']; ?>, '<?php echo $row['username']; ?>')">
                            Edit
                        </a>
                        <a href="hapus_akun.php?id=<?php echo $row['id']; ?>" 
                        class="tombol-hapus" 
                        onclick="return confirm('Yakin ingin menghapus akun ini?')">
                            Hapus
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        
        <a href="../dashboard/admin_dashboard.php" class="kembali-link">Kembali ke Dashboard</a>
    </div>

        <!-- Modal Edit Username dan Reset Password -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h3>Edit Username</h3>
                <form action="../controllers/update_username.php" method="POST">
                    <input type="hidden" id="userId" name="id">
                    <input type="text" id="newUsername" name="new_username" placeholder="Username baru" required>
                    <button type="submit">Simpan Username</button>
                </form>

                <hr style="margin: 20px 0;">
                
                <h3>Reset Password</h3>
                <form action="../controllers/update_password_anggota.php" method="POST">
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
            document.getElementById('newUsername').value = username;
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeModal();
            }
        }
        function openModal(id, username) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('userId').value = id;
            document.getElementById('userIdReset').value = id;
            document.getElementById('newUsername').value = username;
        }
    </script>
</body>
</html>