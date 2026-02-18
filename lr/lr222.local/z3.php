<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z3</title>
</head>
<body>
<?php
echo "<h2>Задание 3: Операторы циклов (вывод ФИО n+5 раз)</h2>";
$n = 18;
$count = $n + 5;
$surname = "Шандроха";
$name = "Полина";
$fullName = $surname . " " . $name;
echo "Вывод имени и фамилии {$count} раз (n={$n}): <br>";
// Используем цикл for
for ($i = 1; $i <= $count; $i++) {
    echo $i . ". " . $fullName . "<br>";
}

?>
</body>
</html>