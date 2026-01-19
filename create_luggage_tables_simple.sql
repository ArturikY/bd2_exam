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
    status VARCHAR(50) NOT NULL DEFAULT E'\u0437\u0430\u044f\u0432\u043a\u0430 \u043f\u0440\u0438\u043d\u044f\u0442\u0430' 
        CHECK (status IN (E'\u0437\u0430\u044f\u0432\u043a\u0430 \u043f\u0440\u0438\u043d\u044f\u0442\u0430', E'\u0431\u0430\u0433\u0430\u0436 \u043d\u0430\u0439\u0434\u0435\u043d')),
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

CREATE INDEX idx_lost_luggage_claims_flight_id ON bookings.lost_luggage_claims(flight_id);
CREATE INDEX idx_lost_luggage_claims_ticket_no ON bookings.lost_luggage_claims(ticket_no);
CREATE INDEX idx_lost_luggage_claims_status ON bookings.lost_luggage_claims(status);
CREATE INDEX idx_lost_luggage_claims_claim_date ON bookings.lost_luggage_claims(claim_date);
CREATE INDEX idx_lost_luggage_claims_baggage_description ON bookings.lost_luggage_claims USING gin(to_tsvector('russian', baggage_description));

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

CREATE INDEX idx_unclaimed_luggage_claim_id ON bookings.unclaimed_luggage(claim_id);
CREATE INDEX idx_unclaimed_luggage_receipt_date ON bookings.unclaimed_luggage(receipt_date);
CREATE INDEX idx_unclaimed_luggage_description ON bookings.unclaimed_luggage USING gin(to_tsvector('russian', description));

