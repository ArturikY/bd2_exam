<?php

require_once 'config.php';

function getLostLuggageClaims($pdo, $sort_by = 'date', $flight_id = null, $status = null) {
    $sql = "
        SELECT 
            c.claim_id,
            c.flight_id,
            c.ticket_no,
            c.passenger_surname,
            c.passenger_name,
            c.passenger_patronymic,
            c.baggage_tag_no,
            c.baggage_weight,
            c.baggage_description,
            c.claim_date,
            c.status,
            r.route_no,
            f.scheduled_departure,
            dep.airport_name->>'ru' as departure_airport,
            arr.airport_name->>'ru' as arrival_airport,
            t.passenger_name as ticket_passenger_name
        FROM bookings.lost_luggage_claims c
        JOIN bookings.flights f ON c.flight_id = f.flight_id
        JOIN bookings.routes r ON f.route_no = r.route_no
        JOIN bookings.airports_data dep ON r.departure_airport = dep.airport_code
        JOIN bookings.airports_data arr ON r.arrival_airport = arr.airport_code
        LEFT JOIN bookings.tickets t ON c.ticket_no = t.ticket_no
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($flight_id !== null) {
        $sql .= " AND c.flight_id = :flight_id";
        $params['flight_id'] = $flight_id;
    }
    
    if ($status !== null && $status !== '') {
        $sql .= " AND c.status = :status";
        $params['status'] = $status;
    }
    
    switch ($sort_by) {
        case 'flight':
            $sql .= " ORDER BY r.route_no, c.claim_date DESC";
            break;
        case 'status':
            $sql .= " ORDER BY c.status, c.claim_date DESC";
            break;
        case 'date':
        default:
            $sql .= " ORDER BY c.claim_date DESC";
            break;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getFlightsForFilter($pdo) {
    $stmt = $pdo->query("
        SELECT DISTINCT
            f.flight_id,
            r.route_no,
            f.scheduled_departure,
            dep.airport_name->>'ru' as departure_airport,
            arr.airport_name->>'ru' as arrival_airport
        FROM bookings.flights f
        JOIN bookings.lost_luggage_claims c ON f.flight_id = c.flight_id
        JOIN bookings.routes r ON f.route_no = r.route_no
        JOIN bookings.airports_data dep ON r.departure_airport = dep.airport_code
        JOIN bookings.airports_data arr ON r.arrival_airport = arr.airport_code
        ORDER BY f.scheduled_departure DESC
    ");
    return $stmt->fetchAll();
}

function getUnclaimedLuggage($pdo, $search = null) {
    $sql = "
        SELECT 
            u.luggage_id,
            u.claim_id,
            u.weight,
            u.description,
            u.receipt_date,
            c.baggage_tag_no,
            c.passenger_surname || ' ' || c.passenger_name || COALESCE(' ' || c.passenger_patronymic, '') as passenger_name
        FROM bookings.unclaimed_luggage u
        LEFT JOIN bookings.lost_luggage_claims c ON u.claim_id = c.claim_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($search !== null && $search !== '') {
        $sql .= " AND to_tsvector('russian', u.description) @@ plainto_tsquery('russian', :search)";
        $params['search'] = $search;
    }
    
    $sql .= " ORDER BY u.receipt_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getClaimsWithoutLuggage($pdo) {
    $stmt = $pdo->query("
        SELECT 
            c.claim_id,
            c.baggage_tag_no,
            c.passenger_surname || ' ' || c.passenger_name || COALESCE(' ' || c.passenger_patronymic, '') as passenger_name,
            c.baggage_description,
            c.status
        FROM bookings.lost_luggage_claims c
        LEFT JOIN bookings.unclaimed_luggage u ON c.claim_id = u.claim_id
        WHERE u.claim_id IS NULL
          AND c.status = 'заявка принята'
        ORDER BY c.claim_date DESC
    ");
    return $stmt->fetchAll();
}

function linkLuggageToClaim($pdo, $luggage_id, $claim_id) {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT claim_id FROM bookings.lost_luggage_claims WHERE claim_id = :claim_id");
        $stmt->execute(['claim_id' => $claim_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Заявка не найдена");
        }
        
        $stmt = $pdo->prepare("
            UPDATE bookings.unclaimed_luggage 
            SET claim_id = :claim_id 
            WHERE luggage_id = :luggage_id
        ");
        $stmt->execute([
            'luggage_id' => $luggage_id,
            'claim_id' => $claim_id
        ]);
        
        $stmt = $pdo->prepare("
            UPDATE bookings.lost_luggage_claims 
            SET status = 'багаж найден' 
            WHERE claim_id = :claim_id
        ");
        $stmt->execute(['claim_id' => $claim_id]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Багаж успешно связан с заявкой. Статус заявки обновлен.'
        ];
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [
            'success' => false,
            'message' => 'Ошибка: ' . $e->getMessage()
        ];
    }
}

function getClaimsStatistics($pdo) {
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM bookings.lost_luggage_claims
        GROUP BY status
    ");
    return $stmt->fetchAll();
}
