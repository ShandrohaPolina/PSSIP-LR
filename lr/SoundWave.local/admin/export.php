<?php
require_once 'includes/config.php';
checkAdminAuth();

// Получаем параметры
$table = $_GET['table'] ?? 'артисты';

// Проверяем существование таблицы
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE '$table'");
    if ($tableCheck->rowCount() == 0) {
        die('Таблица не найдена');
    }
} catch (Exception $e) {
    die('Ошибка: ' . $e->getMessage());
}

// Названия таблиц для отображения
$tableNames = [
    'артисты' => 'Артисты',
    'альбомы' => 'Альбомы',
    'песни' => 'Песни',
    'жанры' => 'Жанры',
    'пользователи' => 'Пользователи',
    'плейлисты' => 'Плейлисты',
    'треки_плейлистов' => 'Треки плейлистов'
];

// Получаем все данные из таблицы
$dataStmt = $pdo->query("SELECT * FROM `$table`");
$data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем названия колонок
$columns = !empty($data) ? array_keys($data[0]) : [];

// Если данных нет, получаем структуру таблицы
if (empty($columns)) {
    $columnsStmt = $pdo->prepare("DESCRIBE `$table`");
    $columnsStmt->execute();
    $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
}

// Формируем имя файла
$filename = $tableNames[$table] ?? $table;
$filename .= '_' . date('Y-m-d_H-i-s');

// Устанавливаем заголовки для CSV файла
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
header('Cache-Control: max-age=0');

// Создаём поток вывода
$output = fopen('php://output', 'w');

// UTF-8 BOM для Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Разделитель - точка с запятой
$delimiter = ';';

// Записываем заголовки - добавляем параметр escape для совместимости с PHP 8.1+
fputcsv($output, $columns, $delimiter, '"', '\\');

// Записываем данные
foreach ($data as $row) {
    $rowData = [];
    foreach ($columns as $column) {
        $value = $row[$column] ?? '';
        if (is_null($value) || $value === '') {
            $value = '';
        }
        // Убираем переносы строк, которые могут сломать CSV
        $value = str_replace(["\r", "\n"], " ", $value);
        $rowData[] = $value;
    }
    // Добавляем параметр escape для совместимости с PHP 8.1+
    fputcsv($output, $rowData, $delimiter, '"', '\\');
}

fclose($output);
exit;
?>