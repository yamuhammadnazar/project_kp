<?php session_start(); ?>
<!DOCTYPE html>
<html>

<head>
    <title>Logging Out</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #ffffff;
            overflow: hidden;
        }

        .logout-container {
            text-align: center;
            padding: 40px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            position: relative;
        }

        h2 {
            color: #333333;
            margin-bottom: 15px;
            font-weight: 600;
        }

        p {
            color: #666666;
            margin-bottom: 25px;
            font-weight: 300;
        }

        .spinner {
            display: inline-block;
            width: 50px;
            height: 50px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: #4e73df;
            animation: spin 1s ease-in-out infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .checkmark {
            display: none;
            width: 50px;
            height: 50px;
            margin: 0 auto 20px;
            position: relative;
        }

        .checkmark__circle {
            width: 50px;
            height: 50px;
            stroke-width: 2;
            stroke: #4e73df;
            stroke-miterlimit: 10;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            stroke: #4e73df;
            stroke-width: 3;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        .btn-login {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #4e73df;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        }

        .btn-login.show {
            opacity: 1;
            transform: translateY(0);
        }

        .btn-login:hover {
            background-color: #2e59d9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 89, 217, 0.2);
        }
    </style>
</head>

<body>
    <div class="logout-container animate__animated animate__fadeIn" id="logoutBox">
        <h2>Logging Out</h2>
        <div class="spinner" id="spinner"></div>
        <svg class="checkmark" id="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
            <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
            <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
        </svg>
        <p id="logoutMessage">Please wait while we securely log you out...</p>
        <a href="login.php" class="btn-login" id="loginBtn">Return to Login</a>
    </div>

    <script>
        // Animasi logout dengan transisi yang lebih halus
        setTimeout(function () {
            const spinner = document.getElementById('spinner');
            const checkmark = document.getElementById('checkmark');
            const logoutMessage = document.getElementById('logoutMessage');
            const loginBtn = document.getElementById('loginBtn');

            // Sembunyikan spinner dan tampilkan checkmark
            spinner.style.display = 'none';
            checkmark.style.display = 'block';

            // Ubah pesan
            logoutMessage.textContent = 'You have been successfully logged out!';
            logoutMessage.classList.add('animate__animated', 'animate__fadeIn');

            // Tampilkan tombol login
            setTimeout(function () {
                loginBtn.classList.add('show');
            }, 500);

            // Redirect otomatis setelah beberapa detik (opsional)
            setTimeout(function () {
                window.location.href = "login.php";
            }, 5000);

        }, 2000);
    </script>
</body>

</html>
<?php session_destroy(); ?>