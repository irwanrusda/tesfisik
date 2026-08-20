CREATE TABLE IF NOT EXISTS sports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS master_people (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_key CHAR(64) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    person_type ENUM('Atlet', 'Pelatih') NOT NULL,
    gender ENUM('L', 'P') NOT NULL,
    sport_id BIGINT UNSIGNED NOT NULL,
    achievement VARCHAR(150) NULL,
    development_status VARCHAR(50) NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    synced_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_master_people_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_master_people_type (person_type),
    INDEX idx_master_people_name (name),
    INDEX idx_master_people_sport (sport_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE athlete_tests
    ADD COLUMN master_person_id BIGINT UNSIGNED NULL AFTER test_number,
    ADD CONSTRAINT fk_athlete_tests_master_person FOREIGN KEY (master_person_id) REFERENCES master_people(id) ON UPDATE CASCADE ON DELETE SET NULL,
    ADD INDEX idx_athlete_tests_master_person (master_person_id);
