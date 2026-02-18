<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Задание №2.4</title>

    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px;
            background-color: #ffcbeb;
        }
        h1 { 
            color: #a300cc;
            text-align: center;
        }
        .fio-display {
            text-align: center;
            margin: 40px 0;
            padding: 30px;
        }
    </style>
</head>
<body>
    <a href="index.html">BACK</a>
        <h1>Вывод ФИО разработчика с заданными параметрами</h1>
        <div class="fio-display">
          <?php
          $color = "#d13aff"; 
          $size = 28; 
          $fio = "Шандроха Полина Викторовна";
          // текст с применением заданных переменных
          echo "<font color='$color' size='$size'>$fio</font>";
?>
    </div>
        

</body>
</html>