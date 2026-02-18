<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Задание №2.5</title>

    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px;
            background-color: #f8d3ff;
        }
        h1, h2 { 
            color: #38004f;
        }

        table {
            width: 50%;
            border: 2px solid #9900ff;
            margin: 20px 0;
            border-collapse: collapse;
            justify-self: center;
        }
        th, td {
            border: 2px solid #9900ff;
            padding: 12px;
        }
        th {
            background-color: #a000cc;
            color: white;
        }

    </style>
</head>
<body>
    <?php
    // Создание константы
    define("NUM_E", 2.71828);
    $num_e1 = NUM_E;
?>
    <a href="index.html">BACK</a>

        <h1>Работа с константами в PHP</h1>
        <ul>
            <li>
                <?php
            // Вывод значения константы
            echo "<p><strong>Число e равно:</strong> " . NUM_E . "</p>";
            ?>
            </li>
            <li>Тип переменной $num_e1: <?php echo gettype($num_e1); ?></li>
    </ul>
            <p> Преобразование типов переменной $num_e1</p>
            <table>
                <tr>
                    <th>Тип</th>
                    <th>Результат</th>
                    <th>Тип после преобразования</th>
                </tr>
                <?php
                //строковый тип
                $num_e1 = (string)$num_e1;
                ?>
                <tr>
                    <td>Строковый</td>
                    <td><?php echo $num_e1 ?></td>
                    <td><?php echo gettype($num_e1) ?></td>
                </tr>
                
                <?php
                //целый тип
                $num_e1 = (int)$num_e1;
                ?>
                <tr>
                    <td>Целый</td>
                    <td><?php echo $num_e1; ?></td>
                    <td><?php echo gettype($num_e1); ?></td>
                </tr>
                
                <?php
                //булевский тип
                $num_e1 = (bool)$num_e1;
                ?>
                <tr>
                    <td>Булевский</td>
                    <td><?php echo $num_e1 ? 'true' : 'false'; ?></td>
                    <td><?php echo gettype($num_e1); ?></td>
                </tr>
            </table>
       
</body>
</html>