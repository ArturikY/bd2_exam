<?php

require_once 'config.php';
require_once 'functions.php';

$airports = getAirports($pdo, 10);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Экзамен - Базы данных</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        
        .status {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .airport-code {
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Аэропорты</h1>
        
        <div class="status">
            ✅ Подключение к базе данных установлено
        </div>
        
        <?php if (!empty($airports)): ?>
            <h2>Первые 10 аэропортов</h2>
            <table>
                <thead>
                    <tr>
                        <th>Код</th>
                        <th>Название</th>
                        <th>Город</th>
                        <th>Координаты</th>
                        <th>Часовой пояс</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($airports as $airport): ?>
                        <tr>
                            <td class="airport-code"><?php echo htmlspecialchars($airport['airport_code'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($airport['airport_name'] ?? 'Нет данных'); ?></td>
                            <td><?php echo htmlspecialchars($airport['city'] ?? 'Нет данных'); ?></td>
                            <td><?php echo htmlspecialchars($airport['coordinates'] ?? 'Нет данных'); ?></td>
                            <td><?php echo htmlspecialchars($airport['timezone'] ?? 'Нет данных'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Аэропорты не найдены или произошла ошибка при получении данных.</p>
        <?php endif; ?>
    </div>
</body>
</html>


