SET search_path TO bookings;
SET client_encoding TO 'UTF8';

CREATE TABLE IF NOT EXISTS bookings.lost_luggage_claims (
    claim_id SERIAL PRIMARY KEY,
    
    flight_id INTEGER NOT NULL,
    
    ticket_no VARCHAR(13),
    
    passenger_surname VARCHAR(100) NOT NULL,
    passenger_name VARCHAR(100) NOT NULL,
    passenger_patronymic VARCHAR(100),
    
    baggage_tag_no VARCHAR(50) NOT NULL, 
    baggage_weight DECIMAL(5,2) NOT NULL CHECK (baggage_weight > 0), 
    baggage_description TEXT NOT NULL,  
    
    claim_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    status VARCHAR(50) NOT NULL DEFAULT 'заявка принята' 
        CHECK (status IN ('заявка принята', 'багаж найден')),
    
    CONSTRAINT fk_lost_luggage_claims_flight 
        FOREIGN KEY (flight_id) 
        REFERENCES bookings.flights(flight_id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_lost_luggage_claims_ticket 
        FOREIGN KEY (ticket_no) 
        REFERENCES bookings.tickets(ticket_no) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS bookings.unclaimed_luggage (
    luggage_id SERIAL PRIMARY KEY,
    
    claim_id INTEGER,
    
    weight DECIMAL(5,2) NOT NULL CHECK (weight > 0), 
    description TEXT NOT NULL, 
    
    receipt_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_unclaimed_luggage_claim 
        FOREIGN KEY (claim_id) 
        REFERENCES bookings.lost_luggage_claims(claim_id) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE,
    CONSTRAINT unclaimed_luggage_claim_id_nn CHECK (claim_id IS NULL OR claim_id > 0)
);

INSERT INTO bookings.lost_luggage_claims (
    flight_id,
    ticket_no,
    passenger_surname,
    passenger_name,
    passenger_patronymic,
    baggage_tag_no,
    baggage_weight,
    baggage_description,
    claim_date,
    status
)
SELECT * FROM (
    VALUES
    (
        (SELECT flight_id FROM bookings.flights ORDER BY scheduled_departure LIMIT 1 OFFSET 0),
        (SELECT ticket_no FROM bookings.tickets LIMIT 1 OFFSET 0),
        'Иванов',
        'Иван',
        'Иванович',
        '001',
        23.50,
        'Черный чемодан с колесиками, размер 70x50x30 см, наклейка с именем',
        CURRENT_TIMESTAMP - INTERVAL '5 days',
        'заявка принята'
    ),
    (
        (SELECT flight_id FROM bookings.flights ORDER BY scheduled_departure LIMIT 1 OFFSET 1),
        (SELECT ticket_no FROM bookings.tickets LIMIT 1 OFFSET 1),
        'Петрова',
        'Мария',
        'Сергеевна',
        '002',
        18.20,
        'Красная спортивная сумка, бренд Nike, содержит одежду и обувь',
        CURRENT_TIMESTAMP - INTERVAL '3 days',
        'багаж найден'
    ),
    (
        (SELECT flight_id FROM bookings.flights ORDER BY scheduled_departure LIMIT 1 OFFSET 2),
        NULL,
        'Сидоров',
        'Петр',
        NULL,
        '003',
        31.75,
        'Большой черный рюкзак, содержит ноутбук и документы',
        CURRENT_TIMESTAMP - INTERVAL '2 days',
        'заявка принята'
    ),
    (
        (SELECT flight_id FROM bookings.flights ORDER BY scheduled_departure LIMIT 1 OFFSET 3),
        (SELECT ticket_no FROM bookings.tickets LIMIT 1 OFFSET 2),
        'Козлова',
        'Анна',
        'Владимировна',
        '004',
        15.80,
        'Синий чемодан среднего размера, наклейка с адресом',
        CURRENT_TIMESTAMP - INTERVAL '7 days',
        'багаж найден'
    ),
    (
        (SELECT flight_id FROM bookings.flights ORDER BY scheduled_departure LIMIT 1 OFFSET 4),
        (SELECT ticket_no FROM bookings.tickets LIMIT 1 OFFSET 3),
        'Смирнов',
        'Алексей',
        'Дмитриевич',
        '005',
        27.30,
        'Коричневая кожаная сумка, содержит подарки и сувениры',
        CURRENT_TIMESTAMP - INTERVAL '1 day',
        'заявка принята'
    )
);

INSERT INTO bookings.unclaimed_luggage (
    claim_id,
    weight,
    description,
    receipt_date
)
SELECT * FROM (
    VALUES
    (
        (SELECT claim_id FROM bookings.lost_luggage_claims WHERE baggage_tag_no = '002' LIMIT 1),
        18.20,
        'Красная спортивная сумка, бренд Nike, содержит одежду и обувь',
        CURRENT_TIMESTAMP - INTERVAL '2 days'
    ),
    (
        (SELECT claim_id FROM bookings.lost_luggage_claims WHERE baggage_tag_no = '004' LIMIT 1),
        15.80,
        'Синий чемодан среднего размера, наклейка с адресом',
        CURRENT_TIMESTAMP - INTERVAL '6 days'
    ),
    (
        NULL,
        22.50,
        'Черный рюкзак с логотипом Adidas, содержит книги и тетради',
        CURRENT_TIMESTAMP - INTERVAL '4 days'
    ),
    (
        NULL,
        12.30,
        'Серая дорожная сумка, содержит косметику и туалетные принадлежности',
        CURRENT_TIMESTAMP - INTERVAL '3 days'
    ),
    (
        NULL,
        19.75,
        'Зеленый чемодан с наклейками путешествий, содержит одежду',
        CURRENT_TIMESTAMP - INTERVAL '1 day'
    )
);

