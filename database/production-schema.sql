-- Tes Fisik KONI Sumbar 2026
-- Database target: konisumbaror_tesfisikdb
-- Import file ini melalui phpMyAdmin pada database yang sudah dibuat.

SET NAMES utf8mb4;
SET time_zone = '+07:00';
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS test_results;
DROP TABLE IF EXISTS test_photos;
DROP TABLE IF EXISTS bleep_tests;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS athlete_tests;
DROP TABLE IF EXISTS master_people;
DROP TABLE IF EXISTS sports;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS migrations;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('superadmin', 'input', 'panitia') NOT NULL DEFAULT 'input',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE master_people (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_key CHAR(64) NOT NULL UNIQUE,
    source VARCHAR(20) NOT NULL DEFAULT 'spreadsheet',
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
    CONSTRAINT fk_master_people_sport
        FOREIGN KEY (sport_id) REFERENCES sports(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_master_people_type (person_type),
    INDEX idx_master_people_name (name),
    INDEX idx_master_people_sport (sport_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE athlete_tests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    test_number VARCHAR(30) NOT NULL UNIQUE,
    master_person_id BIGINT UNSIGNED NULL,
    athlete_name VARCHAR(120) NOT NULL,
    birth_place VARCHAR(100) NULL,
    birth_date DATE NULL,
    sport VARCHAR(100) NOT NULL,
    gender ENUM('L', 'P') NOT NULL,
    height_cm DECIMAL(6,2) NULL,
    weight_kg DECIMAL(6,2) NULL,
    bmi DECIMAL(5,2) NULL,
    test_date DATE NOT NULL,
    test_place VARCHAR(100) NOT NULL DEFAULT 'Padang',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_athlete_tests_master_person
        FOREIGN KEY (master_person_id) REFERENCES master_people(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_athlete_tests_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_athlete_tests_master_person (master_person_id),
    INDEX idx_athlete_name (athlete_name),
    INDEX idx_sport (sport),
    INDEX idx_test_date (test_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE test_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    athlete_test_id BIGINT UNSIGNED NOT NULL,
    test_code VARCHAR(40) NOT NULL,
    result_value DECIMAL(8,2) NULL,
    unit VARCHAR(20) NOT NULL,
    category VARCHAR(50) NULL,
    examiner_notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_results_athlete_test
        FOREIGN KEY (athlete_test_id) REFERENCES athlete_tests(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY uq_athlete_test_code (athlete_test_id, test_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE test_photos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    athlete_test_id BIGINT UNSIGNED NOT NULL,
    test_code VARCHAR(40) NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(50) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_test_photos_athlete_test FOREIGN KEY (athlete_test_id) REFERENCES athlete_tests(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_test_photos_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_test_photos_athlete_test (athlete_test_id),
    INDEX idx_test_photos_station (athlete_test_id, test_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bleep_tests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    test_number VARCHAR(30) NOT NULL UNIQUE,
    master_person_id BIGINT UNSIGNED NOT NULL,
    athlete_name VARCHAR(150) NOT NULL,
    sport VARCHAR(100) NOT NULL,
    gender ENUM('L', 'P') NOT NULL,
    birth_date DATE NULL,
    test_date DATE NOT NULL,
    test_place VARCHAR(100) NOT NULL DEFAULT 'Padang',
    level TINYINT UNSIGNED NOT NULL,
    shuttle SMALLINT UNSIGNED NOT NULL,
    completed_shuttles SMALLINT UNSIGNED NOT NULL,
    distance_m SMALLINT UNSIGNED NOT NULL,
    speed_kmh DECIMAL(5,2) NOT NULL,
    vo2max DECIMAL(5,2) NOT NULL,
    category VARCHAR(50) NULL,
    notes VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bleep_tests_master_person FOREIGN KEY (master_person_id) REFERENCES master_people(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_bleep_tests_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_bleep_tests_athlete (master_person_id),
    INDEX idx_bleep_tests_date (test_date),
    INDEX idx_bleep_tests_sport (sport)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    user_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL,
    user_role VARCHAR(30) NOT NULL,
    action ENUM('create', 'update', 'delete') NOT NULL,
    module ENUM('tes_fisik', 'tes_fisik_pos', 'bleep_test', 'master_data') NOT NULL,
    record_id BIGINT UNSIGNED NULL,
    record_number VARCHAR(30) NULL,
    athlete_name VARCHAR(150) NOT NULL,
    sport VARCHAR(100) NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_audit_logs_user (user_id),
    INDEX idx_audit_logs_module_action (module, action),
    INDEX idx_audit_logs_record (module, record_id),
    INDEX idx_audit_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE test_settings (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_test_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Akun awal produksi. Ganti password setelah login pertama.
INSERT INTO users (name, username, password, role, is_active) VALUES
    ('Super Administrator', 'admin', '$2y$10$Envgriby85gEt6/EFUhAQutxzKf1uUJKet0Z4TmMEBVenyrgmM.xC', 'superadmin', 1),
    ('Akun Input', 'input', '$2y$10$lKH.dZyrToE1HWkYvVv/dewcKG8voTrB0SF9s7ECy/NiNkaHWjAku', 'input', 1),
    ('Panitia Tes', 'panitia', '$2y$10$933e6ZEj5KyZhvETzaBx7eWOOekJOgE3eicqLrjAO3Do6kaCKBTp6', 'panitia', 1);

-- Tandai seluruh migrasi saat ini sebagai sudah dijalankan agar migrate.php
-- hanya mengeksekusi migrasi baru pada deployment berikutnya.
INSERT INTO migrations (migration) VALUES
    ('001_create_users.sql'),
    ('002_create_athlete_tests.sql'),
    ('003_create_test_results.sql'),
    ('004_create_master_people.sql'),
    ('005_add_input_panitia_roles.sql'),
    ('006_create_test_photos.sql'),
    ('007_create_bleep_tests.sql'),
    ('008_allow_null_bleep_birth_date.sql'),
    ('009_create_audit_logs.sql'),
    ('010_recalculate_bleep_vo2max_table.sql'),
    ('011_recalculate_bleep_categories.sql'),
    ('012_add_test_code_to_photos.sql'),
    ('013_add_station_audit_module.sql'),
    ('014_create_test_settings.sql'),
    ('015_add_master_people_source.sql'),
    ('016_add_master_data_audit_module.sql'),
    ('017_make_athlete_test_details_optional.sql');

SET FOREIGN_KEY_CHECKS = 1;
