<?php
error_log("get_record.php called with table=" . $_GET['table'] . " id=" . $_GET['id']);
require_once 'includes/config.php';
checkAdminAuth();

header('Content-Type: application/json');

if (!isset($_GET['table']) || !isset($_GET['id'])) {
    echo json_encode(['error' => 'Не указана таблица или ID']);
    exit;
}

$table = $_GET['table'];
$id = (int)$_GET['id'];

// Определяем поле ID для таблицы
$idFields = [
    'артисты' => 'id_артиста',
    'альбомы' => 'id_альбома',
    'песни' => 'id_песни',
    'жанры' => 'id_жанра',
    'пользователи' => 'id_пользователя',
    'плейлисты' => 'id_плейлиста',
    'треки_плейлистов' => 'id_записи'
];

$idField = $idFields[$table] ?? 'id';

try {
    // Проверяем, существует ли таблица
    $tableCheck = $pdo->query("SHOW TABLES LIKE '$table'");
    if ($tableCheck->rowCount() == 0) {
        echo json_encode(['error' => 'Таблица не существует']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE $idField = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($record) {
        echo json_encode($record);
    } else {
        echo json_encode(['error' => 'Запись не найдена']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
exit;
?>