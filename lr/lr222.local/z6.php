<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z6</title>
</head>
<body>
    <?php
echo "<h2>Задание 6: Пользовательская функция</h2>";
function calculateZ($x, $y) {
    $n1 = abs(pow($x, 2) - 0.1);
    $n2 = pow($x, 2) + 0.1;
    $first = sqrt($n1 / $n2);
    $log = log(1 + pow($x, 2), 8);
    $second = ($x + pow($y, 3)) / $log;
    $z = $first + $second;
    return $z;
}
$x = 2;
$y = 1;
$result = calculateZ($x, $y);
echo "Результат расчета Z для x = {$x} и y = {$y}: " . number_format($result, 6, '.', '') . "<br>";
?>
</body>
</html>