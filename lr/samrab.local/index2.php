<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Страница 2</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .label { font-weight: bold; display: inline-block; width: 130px; }
    </style>
</head>
<body>
    <div>
        <h2>Задание 2: Сессии</h2>
    
            <?php if(isset($_SESSION['user_name'])): ?>
                <p><span class="label">Имя:</span> <?php echo $_SESSION['user_name']; ?></p>
                <p><span class="label">Email:</span> <?php echo $_SESSION['user_email']; ?></p>
            <?php else: ?>
                <p>Данные отсутствуют</p>
            <?php endif; ?>
                <p><span class="label">ID сессии:</span> <?php echo session_id(); ?></p>
        
        <a href="index.php"><- Назад</a>
    </div>
</body>
</html>