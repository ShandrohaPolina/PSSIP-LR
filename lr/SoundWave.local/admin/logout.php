<?php
session_start();

// Очищаем сессию
$_SESSION = array();

// Уничтожаем сессию
session_destroy();

// Перенаправляем на страницу входа
header('Location: login.php');
exit;
?>