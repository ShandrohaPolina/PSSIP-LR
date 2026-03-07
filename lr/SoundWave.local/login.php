<?php
session_start();

// Если пользователь уже авторизован, перенаправляем на профиль
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

// Подключение к базе данных
$servername = "localhost";
$username = "root";
$password = "yaustala17";
$dbname = "soundwave";

// Создаем соединение
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверяем соединение
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Устанавливаем кодировку
$conn->set_charset("utf8mb4");

$error = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Введите email и пароль';
    } else {
        // Ищем пользователя по email
        $query = "SELECT id_пользователя, имя_пользователя, email, пароль, аватар FROM пользователи WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Проверяем пароль
            if (password_verify($password, $user['пароль'])) {
                // Успешный вход
                $_SESSION['user_id'] = $user['id_пользователя'];
                $_SESSION['username'] = $user['имя_пользователя'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['avatar'] = $user['аватар'];
                
                header('Location: profile.php');
                exit;
            } else {
                $error = 'Неверный пароль';
            }
        } else {
            $error = 'Пользователь с таким email не найден';
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - SoundWave</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/particles.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <!-- Плавающие музыкальные ноты -->
    <div id="floatingNotes"></div>

    <!-- Простая шапка для страниц авторизации -->
    <header class="simple-header">
        <a href="index.php" class="logo">SoundWave</a>
    </header>

    <main class="auth-main">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h2>Добро пожаловать!</h2>
                    <p>Войдите в свой аккаунт SoundWave</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="error-message" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="auth-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="your@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="••••••••" required>
                            <button type="button" class="password-toggle">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="auth-submit-btn">
                        <span>Войти</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    
                    <div class="auth-footer">
                        <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script src="js/floating-notes.js"></script>
    <script src="js/auth.js"></script>
</body>
</html>