<?php
// КОНФИГУРАЦИЯ
define('EMAIL_ENABLED', false); // Изменить на true, чтобы включить отправку email
define('DEMO_MESSAGE', 'В иной версии этот текст отправился бы на email, но в текущем состоянии сайта это невозможно');

$msg = '';
$showForm = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($_POST['text'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if ($text && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Сохраняем в файл
        $file = 'saved_' . date('Y-m-d_H-i-s') . '.txt';
        $fileContent = "Дата: " . date('Y-m-d H:i:s') . "\nEmail: $email\nТекст: $text\n";
        file_put_contents($file, $fileContent, LOCK_EX);
        
        // Отправляем email если включено
        if (EMAIL_ENABLED) {
            $subject = 'Сохраненный текст';
            $headers = 'From: no-reply@' . $_SERVER['HTTP_HOST'] . "\r\n" .
                      'Reply-To: no-reply@' . $_SERVER['HTTP_HOST'] . "\r\n" .
                      'X-Mailer: PHP/' . phpversion();
            
            if (mail($email, $subject, $text, $headers)) {
                $msg = "Сохранено и отправлено на $email";
            } else {
                $msg = "Сохранено в файл, но ошибка отправки email";
            }
        } else {
            // Демо-режим
            $msg = "Сохранено! " . DEMO_MESSAGE . " ($email)";
        }
        
        // Очищаем поля
        $_POST['text'] = '';
        $_POST['email'] = '';
    } else {
        $msg = "Заполните все поля правильно";
    }
}
?>
    
</body>
</html>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма обратной связи</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #764ba2;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 50%;
        }

        .box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }


        h3 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
            text-align: center;
            font-weight: 600;
            letter-spacing: -0.5px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }

        .demo {
            background: <?php echo EMAIL_ENABLED ? 'linear-gradient(135deg, #4CAF50 0%, #45a049 100%)' : 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'; ?>;
            color: white;
            padding: 15px 20px;
            margin-bottom: 30px;
            text-align: center;
            border-radius: 12px;
            font-size: 15px;
            box-shadow: 0 5px 15px <?php echo EMAIL_ENABLED ? 'rgba(76, 175, 80, 0.3)' : 'rgba(245, 87, 108, 0.3)'; ?>;
        }

        .status-badge {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 50px;
            font-size: 13px;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .dot-green {
            background: #4CAF50;
            box-shadow: 0 0 10px #4CAF50;
        }

        .dot-red {
            background: #dc3545;
            box-shadow: 0 0 10px #dc3545;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
        }

        input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
        }

        input:hover {
            border-color: #b0b0b0;
        }

        input::placeholder {
            color: #aaa;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }


        button:active {
            transform: translateY(0);
        }

        .msg {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 12px;
            font-size: 15px;
            line-height: 1.6;
           
        }

        .msg.success {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }

        .msg.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
       
    </style>
</head>
<body>
    <div class="container">
        <div class="box">
            <h3>Форма обратной связи</h3>            
            
            <?php if ($msg): ?>
                <div class="msg <?php echo strpos($msg, '✅') !== false ? 'success' : 'error'; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Ваш текст</label>
                    <div class="input-wrapper">
                        <input type="text" name="text" placeholder="Введите любой текст..." required 
                               value="<?php echo htmlspecialchars($_POST['text'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Ваш email</label>
                    <div class="input-wrapper">
                        
                        <input type="email" name="email" placeholder="example@mail.com" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <button type="submit">
                   
                    <?php echo EMAIL_ENABLED ? 'Сохранить и отправить' : 'Сохранить'; ?>
                </button>
            </form>
        </div>
    </div>
</body>
</html>