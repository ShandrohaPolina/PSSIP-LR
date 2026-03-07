<?php
require_once 'includes/config.php';
checkAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$table = $_POST['table'] ?? '';
$id = $_POST['id'] ?? '';

if (empty($table) || empty($id)) {
    $_SESSION['error_message'] = 'Не указана таблица или ID записи';
    header('Location: index.php');
    exit;
}

try {
    $idField = 'id_' . rtrim($table, 'ы') . 'а';
    $sql = "DELETE FROM `$table` WHERE $idField = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    $_SESSION['success_message'] = 'Запись успешно удалена';
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Ошибка при удалении: ' . $e->getMessage();
}

header('Location: index.php?table=' . urlencode($table));
exit;
?>