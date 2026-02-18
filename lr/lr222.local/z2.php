<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z2</title>
</head>
<body>
    <?php
echo "<h2>Задание 2: Работа с переменными</h2>";

$p = "Программа";
$b = "работает";
$result = $p . " " . $b;
echo "Результат: " . $result . "<br>";
$result .= " хорошо";
echo "Итоговая строка: " . $result . "<br>";
?>
</body>
</html>