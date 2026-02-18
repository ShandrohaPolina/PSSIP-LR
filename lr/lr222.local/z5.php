<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z5</title>
</head>
<body>
    <?php
echo "<h2>Задание 5: Работа со строками</h2>";
$s1 = "ШАНДРОХА";
$s2 = "АДРЕС";
echo "1) Длина строки S1 ('{$s1}'): " . strlen($s1) . " символов.<br>";
$hoba = $s1 . " " . $s2;
echo "2) Сцепление строк: " . $hoba . "<br>";
$s2_up = strtoupper($s2);
echo "3) Строка S2 в верхнем регистре: " . $s2_up . "<br><br>";

?>
</body>
</html>