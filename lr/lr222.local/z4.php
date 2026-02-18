<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z4</title>
</head>
<body>
    <?php
echo "<h2>Задание 4: Работа с массивами</h2>";
$products = [
    "Хлеб" => 5000,
    "Молоко" => 800,
    "Сметана" => 7000
];
echo "Стоимость Молока: " . $products["Молоко"] . "<br>";
arsort($products);
echo "Массив после сортировки по убыванию стоимости:<br>";
foreach ($products as $key => $value) {
    echo "{$key}: {$value}<br>";
}
echo "<br>";
?>
</body>
</html>