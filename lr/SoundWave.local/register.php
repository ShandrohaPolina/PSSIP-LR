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
$success = '';

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Валидация
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif (strlen($username) < 3) {
        $error = 'Имя пользователя должно быть не менее 3 символов';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введите корректный email адрес';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } else {
        // Проверяем, существует ли уже пользователь с таким email
        $checkQuery = "SELECT id_пользователя FROM пользователи WHERE email = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = 'Пользователь с таким email уже существует';
        } else {
            // Обработка загрузки аватара
            $avatar_path = 'default-avatar.png'; // аватар по умолчанию
            
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'images/';
                $file_name = time() . '_' . basename($_FILES['avatar']['name']);
                $target_path = $upload_dir . $file_name;
                
                // Проверяем тип файла
                $imageFileType = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($imageFileType, $allowed_types)) {
                    // Проверяем размер (максимум 5 МБ)
                    if ($_FILES['avatar']['size'] <= 5 * 1024 * 1024) {
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_path)) {
                            $avatar_path = $file_name;
                        } else {
                            $error = 'Ошибка при загрузке файла';
                        }
                    } else {
                        $error = 'Файл слишком большой. Максимальный размер 5 МБ';
                    }
                } else {
                    $error = 'Разрешены только файлы JPG, JPEG, PNG, GIF, WEBP';
                }
            }
            
            if (empty($error)) {
                // Хешируем пароль
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Вставляем нового пользователя
                $insertQuery = "INSERT INTO пользователи (имя_пользователя, email, пароль, аватар, дата_регистрации) 
                                VALUES (?, ?, ?, ?, NOW())";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param("ssss", $username, $email, $hashed_password, $avatar_path);
                
                if ($insertStmt->execute()) {
                    $user_id = $insertStmt->insert_id;
                    
                    // Автоматически входим после регистрации
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['avatar'] = $avatar_path;
                    
                    // Перенаправляем на страницу профиля
                    header('Location: profile.php');
                    exit;
                } else {
                    $error = 'Ошибка при регистрации: ' . $conn->error;
                }
            }
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
    <title>Регистрация - SoundWave</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/particles.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <style>
        /* Дополнительные стили для загрузки аватара */
        .avatar-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #9333ea;
            box-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .avatar-preview:hover {
            transform: scale(1.05);
            border-color: #fbbf24;
            box-shadow: 0 0 30px rgba(251, 191, 36, 0.3);
        }
        
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-upload-btn {
            background: rgba(147, 51, 234, 0.2);
            border: 2px solid #9333ea;
            color: #f0f0f0;
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .avatar-upload-btn:hover {
            background: rgba(147, 51, 234, 0.4);
            border-color: #fbbf24;
        }
        
        .avatar-upload-btn i {
            color: #fbbf24;
        }
        
        #avatarInput {
            display: none;
        }
        
        .upload-hint {
            color: #b0b0b0;
            font-size: 0.85rem;
            margin-top: 5px;
        }
    </style>
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
                    <h2>Создать аккаунт</h2>
                    <p>Присоединяйтесь к музыкальному сообществу</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="error-message" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="auth-form" enctype="multipart/form-data">
                    <!-- Поле для загрузки аватара -->
                    <div class="avatar-upload">
                        <div class="avatar-preview" id="avatarPreview" onclick="document.getElementById('avatarInput').click()">
                            <img src="https://ui-avatars.com/api/?name=User&background=9333ea&color=fff&size=120" alt="Avatar preview" id="previewImage">
                        </div>
                        <label for="avatarInput" class="avatar-upload-btn">
                            <i class="fas fa-camera"></i> Выбрать аватар
                        </label>
                        <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="upload-hint">PNG, JPG, GIF, WEBP до 5 МБ</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Имя пользователя</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" placeholder="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="your@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="password">Пароль</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="password" name="password" placeholder="••••••••" required>
                                <button type="button" class="password-toggle">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group half">
                            <label for="confirm_password">Подтверждение</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                                <button type="button" class="password-toggle">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Индикатор сложности пароля -->
                    <div class="password-strength">
                        <div class="strength-bar" id="strengthBar">
                            <div class="strength-fill" style="width: 0%;"></div>
                        </div>
                        <span id="strengthText">Введите пароль</span>
                    </div>
                    
                    <button type="submit" class="auth-submit-btn">
                        <span>Зарегистрироваться</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    
                    <div class="auth-footer">
                        <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="js/floating-notes.js"></script>
    <script src="js/auth.js"></script>
    <script>
        // Предпросмотр аватара перед загрузкой
        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImage').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>