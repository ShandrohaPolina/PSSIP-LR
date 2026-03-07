<?php
require_once 'includes/config.php';
checkAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$table = $_POST['table'] ?? '';
$id = $_POST['id'] ?? '';

// Собираем данные для сохранения
$data = [];

foreach ($_POST as $key => $value) {
    // Пропускаем служебные поля
    if ($key == 'table' || $key == 'id') {
        continue;
    }
    
    // Пропускаем поля с _url, они будут обработаны отдельно
    if (strpos($key, '_url') !== false) {
        continue;
    }
    
    // Добавляем обычные поля
    $data[$key] = ($value === '') ? null : $value;
}

// Обрабатываем URL для изображений
$imageFields = ['фото', 'обложка', 'аватар'];
foreach ($imageFields as $field) {
    $urlField = $field . '_url';
    if (isset($_POST[$urlField]) && !empty($_POST[$urlField])) {
        // Если указан URL, используем его (перезаписываем, если было пустое поле файла)
        $data[$field] = $_POST[$urlField];
    }
}

// Обработка загруженных файлов
$upload_dir = '../images/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

foreach ($imageFields as $field) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
        $file_name = time() . '_' . basename($_FILES[$field]['name']);
        $target_path = $upload_dir . $file_name;
        
        $imageFileType = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($imageFileType, $allowed_types) && $_FILES[$field]['size'] <= 5 * 1024 * 1024) {
            if (move_uploaded_file($_FILES[$field]['tmp_name'], $target_path)) {
                // Загруженный файл имеет приоритет над URL
                $data[$field] = $file_name;
            }
        }
    }
}

try {
    if (empty($id)) {
        // НОВАЯ ЗАПИСЬ
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        $_SESSION['success_message'] = 'Запись успешно добавлена!';
    } else {
        // РЕДАКТИРОВАНИЕ
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "$column = ?";
        }
        $setClause = implode(', ', $setParts);
        
        $idField = 'id_' . rtrim($table, 'ы') . 'а';
        $sql = "UPDATE `$table` SET $setClause WHERE $idField = ?";
        
        $values = array_values($data);
        $values[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        $_SESSION['success_message'] = 'Запись успешно обновлена!';
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Ошибка: ' . $e->getMessage();
}

header('Location: index.php?table=' . urlencode($table));
exit;
?>