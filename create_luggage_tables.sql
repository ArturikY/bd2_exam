-- ============================================================
-- МОДИФИКАЦИЯ БАЗЫ ДАННЫХ "Air_Trans"
-- Добавление таблиц для работы с пропавшим и бесхозным багажом
-- ============================================================

SET search_path TO bookings;

-- ============================================================
-- 1. ТАБЛИЦА: Заявки на поиск пропавшего багажа
-- ============================================================

CREATE TABLE IF NOT EXISTS bookings.lost_luggage_claims (
    claim_id SERIAL PRIMARY KEY,
    
    -- Связь с рейсом (обязательная)
    flight_id INTEGER NOT NULL,
    
    -- Связь с билетом (опциональная, для связи с пассажиром)
    ticket_no VARCHAR(13),
    
    -- ФИО пассажира (обязательно по заданию)
    passenger_surname VARCHAR(100) NOT NULL,
    passenger_name VARCHAR(100) NOT NULL,
    passenger_patronymic VARCHAR(100),
    
    -- Информация о багаже
    baggage_tag_no VARCHAR(50) NOT NULL,  -- Номер багажной бирки
    baggage_weight DECIMAL(5,2) NOT NULL CHECK (baggage_weight > 0),  -- Вес пропавшего багажа
    baggage_description TEXT NOT NULL,  -- Краткое описание пропавшего багажа
    
    -- Дата подачи заявки
    claim_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Статус заявки
    status VARCHAR(50) NOT NULL DEFAULT 'заявка принята' 
        CHECK (status IN ('заявка принята', 'багаж найден')),
    
    -- Внешние ключи
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

-- Индексы для производительности
CREATE INDEX idx_lost_luggage_claims_flight_id ON bookings.lost_luggage_claims(flight_id);
CREATE INDEX idx_lost_luggage_claims_ticket_no ON bookings.lost_luggage_claims(ticket_no);
CREATE INDEX idx_lost_luggage_claims_status ON bookings.lost_luggage_claims(status);
CREATE INDEX idx_lost_luggage_claims_claim_date ON bookings.lost_luggage_claims(claim_date);
CREATE INDEX idx_lost_luggage_claims_baggage_description ON bookings.lost_luggage_claims USING gin(to_tsvector('russian', baggage_description));

-- Комментарии к таблице и полям
COMMENT ON TABLE bookings.lost_luggage_claims IS 'Заявки на поиск пропавшего багажа';
COMMENT ON COLUMN bookings.lost_luggage_claims.claim_id IS 'Уникальный идентификатор заявки';
COMMENT ON COLUMN bookings.lost_luggage_claims.flight_id IS 'Рейс, на котором пропал багаж';
COMMENT ON COLUMN bookings.lost_luggage_claims.ticket_no IS 'Номер билета пассажира (опционально)';
COMMENT ON COLUMN bookings.lost_luggage_claims.passenger_surname IS 'Фамилия пассажира';
COMMENT ON COLUMN bookings.lost_luggage_claims.passenger_name IS 'Имя пассажира';
COMMENT ON COLUMN bookings.lost_luggage_claims.passenger_patronymic IS 'Отчество пассажира (опционально)';
COMMENT ON COLUMN bookings.lost_luggage_claims.baggage_tag_no IS 'Номер багажной бирки';
COMMENT ON COLUMN bookings.lost_luggage_claims.baggage_weight IS 'Вес пропавшего багажа в килограммах';
COMMENT ON COLUMN bookings.lost_luggage_claims.baggage_description IS 'Краткое описание пропавшего багажа';
COMMENT ON COLUMN bookings.lost_luggage_claims.status IS 'Статус заявки: заявка принята или багаж найден';

-- ============================================================
-- 2. ТАБЛИЦА: Бесхозный багаж
-- ============================================================

CREATE TABLE IF NOT EXISTS bookings.unclaimed_luggage (
    luggage_id SERIAL PRIMARY KEY,
    
    -- Связь с заявкой (когда багаж найден и связан с заявкой)
    claim_id INTEGER,
    
    -- Информация о багаже
    weight DECIMAL(5,2) NOT NULL CHECK (weight > 0),  -- Вес в килограммах
    description TEXT NOT NULL,  -- Краткое описание
    
    -- Дата поступления
    receipt_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Внешние ключи
    CONSTRAINT fk_unclaimed_luggage_claim 
        FOREIGN KEY (claim_id) 
        REFERENCES bookings.lost_luggage_claims(claim_id) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE,
    CONSTRAINT unclaimed_luggage_claim_id_nn CHECK (claim_id IS NULL OR claim_id > 0)
);

-- Индексы для производительности
CREATE INDEX idx_unclaimed_luggage_claim_id ON bookings.unclaimed_luggage(claim_id);
CREATE INDEX idx_unclaimed_luggage_receipt_date ON bookings.unclaimed_luggage(receipt_date);
CREATE INDEX idx_unclaimed_luggage_description ON bookings.unclaimed_luggage USING gin(to_tsvector('russian', description));

-- Комментарии к таблице и полям
COMMENT ON TABLE bookings.unclaimed_luggage IS 'Бесхозный багаж, найденный в аэропортах';
COMMENT ON COLUMN bookings.unclaimed_luggage.luggage_id IS 'Уникальный идентификатор багажа';
COMMENT ON COLUMN bookings.unclaimed_luggage.claim_id IS 'Связь с заявкой на поиск (если багаж связан с заявкой)';
COMMENT ON COLUMN bookings.unclaimed_luggage.weight IS 'Вес багажа в килограммах';
COMMENT ON COLUMN bookings.unclaimed_luggage.description IS 'Краткое описание багажа';

-- ============================================================
-- 3. ТЕСТОВЫЕ ДАННЫЕ
-- ============================================================

-- Вставка тестовых заявок на поиск пропавшего багажа
-- Используем реальные flight_id из базы данных

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
    -- Заявка 1: Багаж не найден
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
    -- Заявка 2: Багаж найден
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
    -- Заявка 3: Багаж не найден (билет не указан)
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
    -- Заявка 4: Багаж найден
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
    -- Заявка 5: Багаж не найден
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
) AS v(flight_id, ticket_no, passenger_surname, passenger_name, passenger_patronymic, baggage_tag_no, baggage_weight, baggage_description, claim_date, status)
WHERE NOT EXISTS (
    SELECT 1
    FROM bookings.lost_luggage_claims c
    WHERE c.baggage_tag_no = v.baggage_tag_no
);

-- Вставка тестового бесхозного багажа
INSERT INTO bookings.unclaimed_luggage (
    claim_id,
    weight,
    description,
    receipt_date
)
SELECT * FROM (
    VALUES
    -- Бесхозный багаж 1: связан с заявкой 002
    (
        (SELECT claim_id FROM bookings.lost_luggage_claims WHERE baggage_tag_no = '002' LIMIT 1),
        18.20,
        'Красная спортивная сумка, бренд Nike, содержит одежду и обувь',
        CURRENT_TIMESTAMP - INTERVAL '2 days'
    ),
    -- Бесхозный багаж 2: связан с заявкой 004
    (
        (SELECT claim_id FROM bookings.lost_luggage_claims WHERE baggage_tag_no = '004' LIMIT 1),
        15.80,
        'Синий чемодан среднего размера, наклейка с адресом',
        CURRENT_TIMESTAMP - INTERVAL '6 days'
    ),
    -- Бесхозный багаж 3: не связан с заявкой
    (
        NULL,
        22.50,
        'Черный рюкзак с логотипом Adidas, содержит книги и тетради',
        CURRENT_TIMESTAMP - INTERVAL '4 days'
    ),
    -- Бесхозный багаж 4: не связан с заявкой
    (
        NULL,
        12.30,
        'Серая дорожная сумка, содержит косметику и туалетные принадлежности',
        CURRENT_TIMESTAMP - INTERVAL '3 days'
    ),
    -- Бесхозный багаж 5: не связан с заявкой
    (
        NULL,
        19.75,
        'Зеленый чемодан с наклейками путешествий, содержит одежду',
        CURRENT_TIMESTAMP - INTERVAL '1 day'
    )
) AS v(claim_id, weight, description, receipt_date)
WHERE NOT EXISTS (
    SELECT 1
    FROM bookings.unclaimed_luggage u
    WHERE u.description = v.description AND u.weight = v.weight
);

-- ============================================================
-- 4. ПРОВЕРКА СОЗДАННЫХ ТАБЛИЦ
-- ============================================================

-- Проверка структуры таблиц
SELECT 
    table_name,
    column_name,
    data_type,
    is_nullable
FROM information_schema.columns
WHERE table_schema = 'bookings' 
  AND table_name IN ('lost_luggage_claims', 'unclaimed_luggage')
ORDER BY table_name, ordinal_position;

-- Проверка количества записей
SELECT 
    'lost_luggage_claims' as table_name,
    COUNT(*) as row_count
FROM bookings.lost_luggage_claims
UNION ALL
SELECT 
    'unclaimed_luggage',
    COUNT(*)
FROM bookings.unclaimed_luggage;

-- Просмотр тестовых данных
SELECT 
    claim_id,
    flight_id,
    ticket_no,
    passenger_surname || ' ' || passenger_name || COALESCE(' ' || passenger_patronymic, '') as passenger_full_name,
    baggage_tag_no,
    baggage_weight,
    baggage_description,
    status,
    claim_date
FROM bookings.lost_luggage_claims
ORDER BY claim_date DESC;

SELECT 
    luggage_id,
    claim_id,
    weight,
    description,
    receipt_date
FROM bookings.unclaimed_luggage
ORDER BY receipt_date DESC;

