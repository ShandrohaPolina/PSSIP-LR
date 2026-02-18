<?php
session_start();

// Задание 3.1
$a = 100;
$b = 20;
$min = min($a, $b);
file_put_contents('1.txt', "Минимальное число: $min");

// Подключение к БД
$host = 'localhost';
$user = 'root';
$pass = 'yaustala17';
$db = 'library';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Ошибка подключения к БД: " . $conn->connect_error);
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $comment = $_POST['comment'];
    
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    
    header("Location: index2.php");
    exit;
}

// Задание 4.2
$update_sql = "UPDATE readers SET address = REPLACE(address, 'ул. Кабанчик', 'ул. Бегаетпополювеселокабанчик')";
$conn->query($update_sql);

// Задание 4.3
$readers = $conn->query("SELECT * FROM readers");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Вариант 3</title>
    <style>
        .form-group { margin: 10px 0; }
        input, textarea { padding: 5px; width: 250px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #04004a; padding: 8px; text-align: left; }
        th { background: #e6feff; }
        .info-box { background: #e6feff; padding: 10px;}
    </style>
</head>
<body>
    <h1>Вариант 3</h1>
<!--     
    Задание 1 -->
    <div>
        <h2>1. Форма (POST)</h2>
        <form method="POST">
            <div class="form-group">
                <label>Ваше имя:</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Ваша фамилия:</label>
                <input type="text" name="surname" required>
            </div>
            <div class="form-group">
                <label>Ваш адрес эл.почты:</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Ваш комментарий:</label>
                <p></p>
                <textarea name="comment"></textarea>
            </div>
            <div class="form-group">
                <input type="submit" name="submit" value="Отправить">
                <input type="reset" value="Очистить">
            </div>
        </form>
    </div>
    
    <!-- Задание 3 -->
    <div>
        <h2>3. Работа с файлами</h2>
        <p>Переменные: a = 100, b = 20</p>
        <p>Минимум: <?php echo $min; ?></p>
        <?php if(file_exists('1.txt')): ?>
            <div class="info-box">
                Файл 1.txt: <?php echo file_get_contents('1.txt'); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Задание 4 -->
    <div>
        <h2>4. База данных</h2>
        
        <div>
            <strong>4.1. Создание таблицы (в Workbench):</strong><br>
            <small>CREATE TABLE readers (id INT, ticket_number VARCHAR(20), full_name VARCHAR(100), address VARCHAR(255), phone VARCHAR(20));</small>
        </div>
        
        <div>
            <strong>4.2. Запрос на редактирование:</strong><br>
            <small><?php echo $update_sql; ?></small>
        </div>
        
        <h3>4.3. Таблица после редактирования:</h3>
        <?php if($readers && $readers->num_rows > 0): ?>
            <table>
                <tr>
                    <th>№</th>
                    <th>Номер билета</th>
                    <th>ФИО</th>
                    <th>Адрес</th>
                    <th>Телефон</th>
                </tr>
                <?php $i=1; while($row = $readers->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo $row['ticket_number']; ?></td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td><?php echo $row['address']; ?></td>
                    <td><?php echo $row['phone']; ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>Таблица пуста</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>