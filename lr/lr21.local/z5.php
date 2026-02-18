<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Задание №2.6</title>

    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px;
            background-color: #f2dbff;
        }

        h1, h2 { 
            color: #300042;
        }
    </style>
</head>
<body>
    <a href="index.html">BACK</a>
        <h1>Предопределенные константы и переменные PHP</h1>
        <h2>Магические константы, которые меняются в зависимости от контекста кода </h2>
        <ul>
            <li>Директория (__DIR__): <?php echo __DIR__; ?></li>
            <li>Путь к файлу (__FILE__): <?php echo __FILE__; ?></li>
            <li>Текущая строка (__LINE__): <?php echo __LINE__; ?></li>
        </ul>
        <h2>Предопределенные константы</h2>
        <ul>
            <li>Версия PHP (PHP_VERSION): <?php echo PHP_VERSION; ?></li>
            <li>Тип интерфейса сервера (PHP_SAPI): <?php echo PHP_SAPI; ?></li>
            <li>Операционная система (PHP_OS): <?php echo PHP_OS; ?></li>
            <li>Версия расширения Zend (zend_version()): <?php echo zend_version(); ?></li>
            <li>Максимальное значение целого числа (PHP_INT_MAX): <?php echo PHP_INT_MAX; ?></li>
            <li>Путь для подключения файлов (DEFAULT_INCLUDE_PATH): <?php echo DEFAULT_INCLUDE_PATH; ?></li>
            <li>Константа уровня ошибки (E_ERROR): <?php echo E_ERROR; ?></li>
        </ul>

        <h2>Суперглобальные переменные ($_SERVER)</h2>
        <ul>
            <li>Информация о сервере: <?php echo $_SERVER['SERVER_NAME'] ?? 'не определен'; ?></li>
            <li>IP-адрес сервера: <?php echo $_SERVER['SERVER_ADDR'] ?? 'не определен (Localhost)'; ?></li>
            <li>Название программного обеспечения веб-сервера: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'не определен'; ?></li>
            <li>IP-адрес: <?php echo $_SERVER['REMOTE_ADDR']; ?></li>
            <li>Браузер: <?php echo $_SERVER['HTTP_USER_AGENT']; ?></li>
            <li>Метод запроса: <?php echo $_SERVER['REQUEST_METHOD']; ?></li>
            <li>Метод запроса времени: <?php echo date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']); ?></li>
            <li>Текущий скрипт: <?php echo $_SERVER['PHP_SELF']; ?></li>
        </ul>
</body>
</html>