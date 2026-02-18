<?php
$name = "Шандроха Полина Викторовна";
$group = "ПЗТ-40";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Задание №2.3</title>

    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px; 
            background-color: #edceff;
        }

        h2 { 
            color: #a300cc;
        }
        .message {
            font-size: 3em;
            color: #5b0089;
            text-align: center;
        }
        .developer-info {
            padding: 20px;
            margin: 20px 0;
        }

         .info-i {
            margin: 20px 0;
            font-size: 18px;
            color:  rgb(46, 0, 27);
        }
    </style>
</head>
<body>
    <a href="index.html">BACK</a>

        <div class="message">
            <?php
            echo "Привет всем!!!";
            ?>
        </div>
        <div class="developer-info">
            <h2>Информация о разработчике:</h2>
            <div class="info-i"><strong>ФИО:</strong> <?php echo $name; ?></div>
            <div class="info-i"><strong>Группа:</strong> <?php echo $group; ?></div>
            
        </div>
</body>
</html>