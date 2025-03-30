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
