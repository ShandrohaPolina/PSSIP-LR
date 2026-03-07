<?php
// Включаем отображение ошибок для отладки (уберите на продакшене)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



session_start();

// Проверка авторизации
function checkAdminAuth() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

// Данные администратора
define('ADMIN_USERNAME', 'soundwave_admin');
define('ADMIN_PASSWORD', 'SoundWave2026!');

// Параметры подключения к БД
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'yaustala17');
define('DB_NAME', 'soundwave');

// Подключение к базе данных
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Получение списка таблиц
function getTables($pdo) {
    $stmt = $pdo->query("SHOW TABLES");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Получение структуры таблицы
function getTableColumns($pdo, $table) {
    $stmt = $pdo->prepare("DESCRIBE `$table`");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Получение данных из таблицы с пагинацией и поиском
function getTableData($pdo, $table, $page = 1, $perPage = 20, $search = '') {
    // Проверяем, что таблица не пустая
    if (empty($table)) {
        return [
            'data' => [],
            'total' => 0,
            'pages' => 0,
            'currentPage' => $page
        ];
    }
    
    $offset = ($page - 1) * $perPage;
    
    try {
        // Получаем список колонок для поиска
        $columnsStmt = $pdo->prepare("DESCRIBE `$table`");
        $columnsStmt->execute();
        $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Формируем WHERE условие для поиска
        $whereClause = "";
        $searchParams = [];
        
        if (!empty($search)) {
            $searchTerms = explode(' ', $search);
            $searchConditions = [];
            
            foreach ($searchTerms as $term) {
                if (empty(trim($term))) continue;
                
                $termConditions = [];
                foreach ($columns as $column) {
                    // Исключаем поля с ID из поиска (опционально)
                    if (strpos($column, 'id_') === 0) continue;
                    if (in_array($column, ['пароль'])) continue; // исключаем пароли
                    
                    $termConditions[] = "`$column` LIKE ?";
                    $searchParams[] = "%$term%";
                }
                
                if (!empty($termConditions)) {
                    $searchConditions[] = "(" . implode(' OR ', $termConditions) . ")";
                }
            }
            
            if (!empty($searchConditions)) {
                $whereClause = " WHERE " . implode(' AND ', $searchConditions);
            }
        }
        
        // Получаем общее количество записей с учетом поиска
        $countQuery = "SELECT COUNT(*) FROM `$table`" . $whereClause;
        $countStmt = $pdo->prepare($countQuery);
        if (!empty($searchParams)) {
            $countStmt->execute($searchParams);
        } else {
            $countStmt->execute();
        }
        $total = $countStmt->fetchColumn();
        
        // Получаем данные с учетом поиска и пагинации
        $dataQuery = "SELECT * FROM `$table`" . $whereClause . " LIMIT :offset, :perPage";
        $stmt = $pdo->prepare($dataQuery);
        
        if (!empty($searchParams)) {
            foreach ($searchParams as $index => $value) {
                $stmt->bindValue($index + 1, $value, PDO::PARAM_STR);
            }
        }
        
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll();
        
        return [
            'data' => $data,
            'total' => $total,
            'pages' => ceil($total / $perPage),
            'currentPage' => $page
        ];
    } catch (PDOException $e) {
        // В случае ошибки возвращаем пустой результат
        error_log("Ошибка в getTableData: " . $e->getMessage());
        return [
            'data' => [],
            'total' => 0,
            'pages' => 0,
            'currentPage' => $page
        ];
    }
}
?>