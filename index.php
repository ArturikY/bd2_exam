<?php

require_once 'config.php';
require_once 'functions.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'link_luggage') {
        $result = linkLuggageToClaim($pdo, $_POST['luggage_id'], $_POST['claim_id']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    }
}

$sort_by = $_GET['sort_by'] ?? 'date';
$flight_id = isset($_GET['flight_id']) && $_GET['flight_id'] !== '' ? (int)$_GET['flight_id'] : null;
$status = $_GET['status'] ?? null;
$search = $_GET['search'] ?? null;

$claims = getLostLuggageClaims($pdo, $sort_by, $flight_id, $status);
$flights = getFlightsForFilter($pdo);
$unclaimed_luggage = getUnclaimedLuggage($pdo, $search);
$claims_without_luggage = getClaimsWithoutLuggage($pdo);
$statistics = getClaimsStatistics($pdo);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система управления багажом</title>
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
            max-width: 1400px;
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
        
        h2 {
            color: #555;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .filters form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .form-group label {
            font-weight: bold;
            font-size: 14px;
            color: #555;
        }
        
        .form-group select,
        .form-group input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn {
            padding: 8px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
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
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-accepted {
            background: #ffc107;
            color: #000;
        }
        
        .status-found {
            background: #28a745;
            color: white;
        }
        
        .link-form {
            display: inline-block;
        }
        
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            flex: 1;
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .tab {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #666;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
        }
        
        .tab.active {
            color: #007bff;
            border-bottom-color: #007bff;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Система управления багажом</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <?php foreach ($statistics as $stat): ?>
                <div class="stat-card">
                    <h3><?php echo htmlspecialchars($stat['status']); ?></h3>
                    <div class="value"><?php echo $stat['count']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="tabs">
            <button class="tab active">Заявки на поиск</button>
            <button class="tab">Бесхозный багаж</button>
        </div>
        
        <div id="claims" class="tab-content active">
            <h2>Заявки на поиск пропавшего багажа</h2>
            
            <div class="filters">
                <form method="GET" action="">
                    <input type="hidden" name="tab" value="claims">
                    <div class="form-group">
                        <label>Сортировка:</label>
                        <select name="sort_by">
                            <option value="date" <?php echo $sort_by === 'date' ? 'selected' : ''; ?>>По дате</option>
                            <option value="flight" <?php echo $sort_by === 'flight' ? 'selected' : ''; ?>>По рейсу</option>
                            <option value="status" <?php echo $sort_by === 'status' ? 'selected' : ''; ?>>По статусу</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Рейс:</label>
                        <select name="flight_id">
                            <option value="">Все рейсы</option>
                            <?php foreach ($flights as $flight): ?>
                                <option value="<?php echo $flight['flight_id']; ?>" 
                                    <?php echo $flight_id == $flight['flight_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($flight['route_no'] . ' - ' . $flight['departure_airport'] . ' → ' . $flight['arrival_airport']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Статус:</label>
                        <select name="status">
                            <option value="">Все статусы</option>
                            <option value="заявка принята" <?php echo $status === 'заявка принята' ? 'selected' : ''; ?>>Заявка принята</option>
                            <option value="багаж найден" <?php echo $status === 'багаж найден' ? 'selected' : ''; ?>>Багаж найден</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">Применить фильтры</button>
                    <a href="index.php" class="btn">Сбросить</a>
                </form>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Рейс</th>
                        <th>Пассажир</th>
                        <th>Бирка</th>
                        <th>Вес (кг)</th>
                        <th>Описание</th>
                        <th>Дата заявки</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($claims)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px;">
                                Заявки не найдены
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($claims as $claim): ?>
                            <tr>
                                <td><?php echo $claim['claim_id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($claim['route_no'] ?? 'N/A'); ?><br>
                                    <small><?php echo htmlspecialchars($claim['departure_airport'] . ' → ' . $claim['arrival_airport']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $full_name = $claim['passenger_surname'] . ' ' . $claim['passenger_name'];
                                    if ($claim['passenger_patronymic']) {
                                        $full_name .= ' ' . $claim['passenger_patronymic'];
                                    }
                                    echo htmlspecialchars($full_name);
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($claim['baggage_tag_no']); ?></td>
                                <td><?php echo $claim['baggage_weight']; ?></td>
                                <td><?php echo htmlspecialchars($claim['baggage_description']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($claim['claim_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $claim['status'] === 'заявка принята' ? 'accepted' : 'found'; ?>">
                                        <?php echo htmlspecialchars($claim['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div id="luggage" class="tab-content">
            <h2>Бесхозный багаж</h2>
            
            <div class="filters">
                <form method="GET" action="">
                    <input type="hidden" name="tab" value="luggage">
                    <div class="form-group" style="flex: 1;">
                        <label>Поиск по описанию:</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                               placeholder="Введите ключевые слова...">
                    </div>
                    <button type="submit" class="btn">Поиск</button>
                    <a href="index.php?tab=luggage" class="btn">Сбросить</a>
                </form>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Вес (кг)</th>
                        <th>Описание</th>
                        <th>Дата поступления</th>
                        <th>Связанная заявка</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($unclaimed_luggage)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">
                                Бесхозный багаж не найден
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($unclaimed_luggage as $luggage): ?>
                            <tr>
                                <td><?php echo $luggage['luggage_id']; ?></td>
                                <td><?php echo $luggage['weight']; ?></td>
                                <td><?php echo htmlspecialchars($luggage['description']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($luggage['receipt_date'])); ?></td>
                                <td>
                                    <?php if ($luggage['claim_id']): ?>
                                        <strong>Заявка #<?php echo $luggage['claim_id']; ?></strong><br>
                                        <small>Бирка: <?php echo htmlspecialchars($luggage['baggage_tag_no']); ?></small><br>
                                        <small>Пассажир: <?php echo htmlspecialchars($luggage['passenger_name']); ?></small>
                                    <?php else: ?>
                                        <span style="color: #999;">Не связан</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$luggage['claim_id'] && !empty($claims_without_luggage)): ?>
                                        <form method="POST" class="link-form">
                                            <input type="hidden" name="action" value="link_luggage">
                                            <input type="hidden" name="luggage_id" value="<?php echo $luggage['luggage_id']; ?>">
                                            <select name="claim_id" required style="padding: 5px; margin-right: 5px;">
                                                <option value="">Выберите заявку</option>
                                                <?php foreach ($claims_without_luggage as $claim): ?>
                                                    <option value="<?php echo $claim['claim_id']; ?>">
                                                        #<?php echo $claim['claim_id']; ?> - 
                                                        <?php echo htmlspecialchars($claim['passenger_name']); ?> 
                                                        (<?php echo htmlspecialchars($claim['baggage_tag_no']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-success">Связать</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function showTab(tabName, element) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.getElementById(tabName).classList.add('active');
            if (element) {
                element.classList.add('active');
            }
        }
        
        document.querySelectorAll('.tab').forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.textContent.includes('Заявки') ? 'claims' : 'luggage';
                showTab(tabName, this);
            });
        });
        
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab === 'luggage') {
            showTab('luggage', document.querySelectorAll('.tab')[1]);
        }
    </script>
</body>
</html>
