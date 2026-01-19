<?php

require_once 'config.php';

/**
 * Получить первые 10 аэропортов из базы данных
 */
function getAirports($pdo, $limit = 10) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                airport_code,
                airport_name->>'ru' as airport_name,
                city->>'ru' as city,
                coordinates,
                timezone
            FROM airports_data
            ORDER BY airport_code
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

