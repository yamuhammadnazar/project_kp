<?php
include 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    
    $query = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);
    
    if ($user) {
        if (password_verify($password, $user["password"])) {
            // Simpan semua data session di sini
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];
            
            if ($_SESSION["role"] === "kabid") {
                header("Location: ../dashboard/kabid_dashboard.php");
            } else if ($_SESSION["role"] === "admin") {
                header("Location: ../dashboard/admin_dashboard.php");
            } else {
                header("Location: ../dashboard/anggota_dashboard.php");
            }
            exit();
        } else {
            $error_message = "❌ Password salah!";
        }
    } else {
        $error_message = "❌ Username tidak ditemukan!";
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Bootstrap 5.3.2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: rgba(255, 255, 255, 0.95);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, rgb(128, 235, 249) 0%, rgb(100, 150, 250) 50%, #2575fc 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.1rem 0;
        }
        
        .navbar .container {
            display: flex;
            justify-content: flex-start;
            padding-left: 0; /* Menghilangkan padding kiri container */
            margin-left: 0; /* Menghilangkan margin kiri container */
            max-width: 100%; /* Memastikan container menggunakan lebar penuh */
        }
        
        .navbar-brand {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-left: 5px; /* Menghilangkan margin kiri */
            padding-left: 0; /* Menghilangkan padding kiri */
        }
        
        .navbar-brand img {
            height: auto;
            max-height: 55px; /* Ukuran logo */
            width: auto;
            margin-left: 0; /* Memastikan tidak ada margin kiri pada gambar */
        }
        
        .login-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .login-container {
            max-width: 360px;
            width: 100%;
            padding: 2rem;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            margin: 0 auto; /* Memastikan pemusatan horizontal */
        }
        
        .login-container::before {
            content: "";
            position: absolute;
            top: -50px;
            right: -50px;
            width: 100px;
            height: 100px;
            background-color: rgba(106, 17, 203, 0.1);
            border-radius: 50%;
            z-index: 0;
        }
        
        .login-container::after {
            content: "";
            position: absolute;
            bottom: -50px;
            left: -50px;
            width: 120px;
            height: 120px;
            background-color: rgba(37, 117, 252, 0.1);
            border-radius: 50%;
            z-index: 0;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }
        
        .login-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgb(128, 235, 249) 0%, rgb(100, 150, 250) 50%, #2575fc 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.3);
        }
        
        .login-icon i {
            font-size: 1.8rem;
            color: white;
        }
        
        h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }
        
        .login-subtitle {
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 0;
        }
        
        .form-group {
            position: relative;
            margin-bottom: 1.25rem;
            z-index: 1;
        }
        
        .form-control {
            height: 45px;
            padding-left: 40px;
            border: 1px solid #e1e5eb;
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #6a11cb;
            box-shadow: 0 0 0 0.25rem rgba(106, 17, 203, 0.25);
        }
        
        .form-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 1rem;
        }
        
        .btn-login {
            height: 45px;
            background: linear-gradient(135deg, rgb(128, 235, 249) 0%, rgb(100, 150, 250) 50%, #2575fc 100%);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.2);
            transition: all 0.3s;
            color: #ffffff;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(106, 17, 203, 0.3);
            color: #f8f9fa;
        }
        
        .btn-login i {
            margin-right: 8px;
        }
        
        .error-alert {
            border-radius: 10px;
            animation: fadeIn 0.5s;
            border: none;
            background-color: #fff5f5;
            color: #e53e3e;
            padding: 0.75rem;
            margin-bottom: 1.25rem;
            position: relative;
            z-index: 1;
            box-shadow: 0 2px 10px rgba(229, 62, 62, 0.1);
            font-size: 0.85rem;
        }
        
        .error-alert i {
            margin-right: 8px;
            font-size: 1rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.25rem;
            color: #6c757d;
            font-size: 0.75rem;
            position: relative;
            z-index: 1;
        }
        
        /* Pemusatan yang lebih baik untuk semua ukuran layar */
        .login-row {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 100px); /* Menyesuaikan dengan tinggi navbar yang baru */
        }
        
        /* Responsif untuk layar kecil */
        @media (max-width: 576px) {
            .login-container {
                padding: 1.5rem;
                max-width: 320px;
            }
            
            .navbar-brand img {
                max-height: 45px; /* Logo lebih kecil untuk ponsel */
            }
            
            .login-icon {
                width: 50px;
                height: 50px;
            }
            
            .login-icon i {
                font-size: 1.5rem;
            }
            
            .login-footer p {
                font-size: 0.7rem;
            }
            
            h2 {
                font-size: 1.3rem;
            }
        }
        
        /* Responsif untuk ukuran layar berbeda */
        @media (min-width: 768px) {
            .navbar-brand img {
                max-height: 60px; /* Logo untuk tablet */
            }
        }
        
        @media (min-width: 992px) {
            .navbar-brand img {
                max-height: 65px; /* Logo untuk desktop */
            }
        }
        
        @media (min-width: 1200px) {
            .navbar-brand img {
                max-height: 70px; /* Logo lebih besar untuk desktop besar */
            }
        }
        
        @media (max-width: 400px) {
            .navbar-brand img {
                max-height: 35px; /* Logo lebih kecil untuk ponsel kecil */
            }
        }
        
        /* Override Bootstrap container untuk mentokkan logo ke kiri */
        .container-fluid {
            padding-left: 5px;
            padding-right: 15px;
        }
    </style>
</head>
<body>
    <!-- Navbar untuk logo - menggunakan container-fluid untuk mentokkan ke kiri -->
    <nav class="navbar navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="../assets/images/logo_diskominfo.png" alt="Logo" class="img-fluid">
                <!-- Ganti path logo sesuai dengan struktur folder Anda -->
            </a>
        </div>
    </nav>

    <div class="login-wrapper">
        <div class="container">
            <div class="login-row">
                <div class="login-container">
                    <div class="login-header">
                        <div class="login-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h2>Selamat Datang</h2>
                        <p class="login-subtitle">Masukkan Username dan Password Anda</p>
                    </div>
                    
                    <?php if(isset($error_message)): ?>
                        <div class="error-alert">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <i class="fas fa-user form-icon"></i>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                        </div>
                        <div class="form-group">
                            <i class="fas fa-lock form-icon"></i>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-login">
                                <i class="fas fa-sign-in-alt"></i> Masuk
                            </button>
                        </div>
                    </form>
                    
                    <div class="login-footer">
                        <p>Sistem Pencatatan dan Monitoring Tugas<br>Bidang Informasi Komunikasi dan Publikasi &copy; 2025</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3.2 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <!-- Tambahkan JavaScript untuk meningkatkan UX -->
<script>
// Menambahkan JavaScript untuk meningkatkan pengalaman pengguna
document.addEventListener('DOMContentLoaded', function() {
    // Animasi fade-in untuk container login
    const loginContainer = document.querySelector('.login-container');
    loginContainer.style.opacity = '0';
    setTimeout(() => {
        loginContainer.style.transition = 'opacity 0.8s ease-in-out';
        loginContainer.style.opacity = '1';
    }, 100);

    // Animasi untuk form fields saat fokus
    const formControls = document.querySelectorAll('.form-control');
    formControls.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transition = 'transform 0.3s ease';
            this.parentElement.style.transform = 'translateY(-5px)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'translateY(0)';
        });
    });

    // Tambahkan toggle untuk password visibility
    const passwordField = document.getElementById('password');
    const passwordGroup = passwordField.parentElement;
    
    // Buat tombol toggle password
    const toggleBtn = document.createElement('button');
    toggleBtn.type = 'button';
    toggleBtn.className = 'btn btn-sm position-absolute';
    toggleBtn.style.right = '10px';
    toggleBtn.style.top = '50%';
    toggleBtn.style.transform = 'translateY(-50%)';
    toggleBtn.style.border = 'none';
    toggleBtn.style.background = 'transparent';
    toggleBtn.style.color = '#6c757d';
    toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
    toggleBtn.style.zIndex = '5';
    
    toggleBtn.addEventListener('click', function() {
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            this.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
            passwordField.type = 'password';
            this.innerHTML = '<i class="fas fa-eye"></i>';
        }
    });
    
    passwordGroup.appendChild(toggleBtn);

    // Form validation
    const loginForm = document.querySelector('form');
    const usernameField = document.getElementById('username');
    
    loginForm.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validasi username
        if (usernameField.value.trim() === '') {
            showError(usernameField, 'Username tidak boleh kosong');
            isValid = false;
        } else {
            removeError(usernameField);
        }
        
        // Validasi password
        if (passwordField.value.trim() === '') {
            showError(passwordField, 'Password tidak boleh kosong');
            isValid = false;
        } else {
            removeError(passwordField);
        }
        
        if (!isValid) {
            e.preventDefault();
            return;
        }
        
        // Tampilkan loading spinner saat submit
        const loginBtn = document.querySelector('.btn-login');
        const originalBtnText = loginBtn.innerHTML;
        loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
        loginBtn.disabled = true;
        
        // Kita tidak mencegah submit default karena form perlu diproses oleh PHP
        // Namun dalam kasus nyata, Anda mungkin ingin menambahkan timeout untuk mengembalikan tombol ke keadaan semula
        // jika server tidak merespons dalam waktu tertentu
        setTimeout(() => {
            loginBtn.innerHTML = originalBtnText;
            loginBtn.disabled = false;
        }, 3000); // Timeout 3 detik jika server tidak merespons
    });
    
    // Fungsi untuk menampilkan error
    function showError(input, message) {
        const formGroup = input.parentElement;
        let errorDiv = formGroup.querySelector('.error-feedback');
        
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-feedback';
            errorDiv.style.color = '#dc3545';
            errorDiv.style.fontSize = '0.8rem';
            errorDiv.style.marginTop = '5px';
            errorDiv.style.animation = 'fadeIn 0.3s';
            formGroup.appendChild(errorDiv);
        }
        
        input.style.borderColor = '#dc3545';
        errorDiv.textContent = message;
    }
    
    // Fungsi untuk menghapus error
    function removeError(input) {
        const formGroup = input.parentElement;
        const errorDiv = formGroup.querySelector('.error-feedback');
        
        input.style.borderColor = '#e1e5eb';
        if (errorDiv) {
            formGroup.removeChild(errorDiv);
        }
    }
    
    // Efek hover untuk tombol login
    const loginButton = document.querySelector('.btn-login');
    loginButton.addEventListener('mouseenter', function() {
        this.style.transition = 'all 0.3s ease';
        this.style.transform = 'translateY(-3px)';
        this.style.boxShadow = '0 8px 20px rgba(106, 17, 203, 0.3)';
    });
    
    loginButton.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '0 5px 15px rgba(106, 17, 203, 0.2)';
    });
    
    // Animasi untuk error alert jika ada
    const errorAlert = document.querySelector('.error-alert');
    if (errorAlert) {
        errorAlert.style.opacity = '0';
        errorAlert.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            errorAlert.style.transition = 'all 0.5s ease';
            errorAlert.style.opacity = '1';
            errorAlert.style.transform = 'translateY(0)';
        }, 300);
    }
});
</script>
</body>
</html>