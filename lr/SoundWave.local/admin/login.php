<?php
session_start();

// Если уже авторизован, перенаправляем в админку
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

// Данные администратора (хранятся в коде)
define('ADMIN_USERNAME', 'soundwave_admin');
define('ADMIN_PASSWORD', 'SoundWave2026!');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_login_time'] = time();
        
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверное имя пользователя или пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель | SoundWave</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(20, 20, 30, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(147, 51, 234, 0.3);
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            animation: fadeInUp 0.5s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo {
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(90deg, #9333ea, #fbbf24);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-align: center;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }
        
        .admin-badge {
            text-align: center;
            color: #fbbf24;
            font-size: 14px;
            margin-bottom: 30px;
            padding: 5px 15px;
            background: rgba(147, 51, 234, 0.1);
            border-radius: 30px;
            display: inline-block;
            width: auto;
            margin-left: auto;
            margin-right: auto;
        }
        
        .admin-badge i {
            margin-right: 5px;
            color: #9333ea;
        }
        
        h2 {
            color: #fbbf24;
            font-size: 24px;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .subtitle {
            color: #b0b0b0;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        .error-message i {
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            color: #b0b0b0;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #fbbf24;
        }
        
        .input-wrapper input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            background: rgba(25, 25, 35, 0.8);
            border: 2px solid transparent;
            border-radius: 15px;
            color: white;
            font-size: 15px;
            transition: all 0.3s ease;
            background: linear-gradient(90deg, rgba(25, 25, 35, 0.8), rgba(30, 30, 40, 0.8)) padding-box,
                        linear-gradient(90deg, #9333ea, #fbbf24) border-box;
        }
        
        .input-wrapper input:focus {
            outline: none;
            box-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
        }
        
        .input-wrapper input::placeholder {
            color: #666;
        }
        
        .login-btn {
            width: 100%;
            background: linear-gradient(90deg, #9333ea, #a855f7);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(147, 51, 234, 0.4);
        }
        
        .login-btn i {
            transition: transform 0.3s ease;
        }
        
        .login-btn:hover i {
            transform: translateX(5px);
        }
        
        .back-link {
            text-align: center;
            margin-top: 25px;
        }
        
        .back-link a {
            color: #b0b0b0;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .back-link a:hover {
            color: #fbbf24;
        }
        
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">SoundWave</div>
            <div class="admin-badge">
                <i class="fas fa-shield-alt"></i> Админ-панель
            </div>
            
            <h2>Добро пожаловать</h2>
            <div class="subtitle">Войдите в систему управления</div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Имя пользователя</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="soundwave_admin" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Пароль</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>
                
                <button type="submit" class="login-btn">
                    <span>Войти в админ-панель</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="back-link">
                <a href="../index.php">
                    <i class="fas fa-arrow-left"></i>
                    Вернуться на сайт
                </a>
            </div>
        </div>
    </div>
</body>
</html>