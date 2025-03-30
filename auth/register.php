<?php
include 'koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST["username"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $role = mysqli_real_escape_string($conn, $_POST["role"]);

    $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
    
    if(mysqli_query($conn, $query)) {
        $success_message = "✅ User berhasil ditambahkan!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Anggota</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .form-container {
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 5px;
            background: white;
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
        input[type="password"], 
        select {
            width: 95%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        .success-message {
            color: green;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #e8f5e9;
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
        <h2>Tambah Anggota</h2>
        
        <?php if(isset($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Role:</label>
                <select name="role">
                    <option value="anggota">Anggota</option>
                </select>
            </div>

            <button type="submit">Tambah Anggota</button>
        </form>
        <a href="../dashboard/admin_dashboard.php" class="back-link">Kembali ke Dashboard</a>
    </div>
</body>
</html>