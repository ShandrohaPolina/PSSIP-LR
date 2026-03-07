<?php
session_start();

// Полностью уничтожаем сессию
$_SESSION = array();
session_destroy();

// Очищаем куки
setcookie(session_name(), '', time() - 3600, '/');

// Перенаправляем на главную с параметром времени, чтобы избежать кэширования
header('Location: index.php?t=' . time());
exit;
?>