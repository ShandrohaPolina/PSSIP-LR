// Основной JavaScript для страниц авторизации
document.addEventListener('DOMContentLoaded', function () {
    initPasswordToggles();
    initPasswordStrength();
});

// Инициализация переключателей видимости пароля
function initPasswordToggles() {
    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', function () {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
}

// Инициализация индикатора сложности пароля
function initPasswordStrength() {
    const passwordInput = document.getElementById('password');
    if (!passwordInput) return;

    passwordInput.addEventListener('input', function () {
        const strength = calculatePasswordStrength(this.value);
        updateStrengthIndicator(strength);
    });
}

// Расчет сложности пароля
function calculatePasswordStrength(password) {
    if (!password) return 0;

    let strength = 0;

    // Длина
    if (password.length >= 8) strength += 25;
    else if (password.length >= 6) strength += 15;

    // Цифры
    if (/\d/.test(password)) strength += 25;

    // Буквы в разных регистрах
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;

    // Специальные символы
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 25;

    return strength;
}

// Обновление индикатора сложности
function updateStrengthIndicator(strength) {
    const strengthBar = document.querySelector('.strength-fill');
    const strengthText = document.getElementById('strengthText');

    if (!strengthBar || !strengthText) return;

    strengthBar.style.width = strength + '%';

    if (strength < 30) {
        strengthBar.style.background = '#ef4444';
        strengthText.textContent = 'Слабый пароль';
        strengthText.style.color = '#ef4444';
    } else if (strength < 60) {
        strengthBar.style.background = '#fbbf24';
        strengthText.textContent = 'Средний пароль';
        strengthText.style.color = '#fbbf24';
    } else {
        strengthBar.style.background = '#22c55e';
        strengthText.textContent = 'Надежный пароль';
        strengthText.style.color = '#22c55e';
    }
}