CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    user_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL,
    user_role VARCHAR(30) NOT NULL,
    action ENUM('create', 'update', 'delete') NOT NULL,
    module ENUM('tes_fisik', 'bleep_test') NOT NULL,
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

INSERT INTO audit_logs (user_id, user_name, username, user_role, action, module, record_id, record_number, athlete_name, sport, details, created_at)
SELECT u.id, u.name, u.username, u.role, 'create', 'tes_fisik', at.id, at.test_number, at.athlete_name, at.sport,
       JSON_OBJECT('sumber', 'migrasi_data_lama', 'tanggal_tes', at.test_date), at.created_at
FROM athlete_tests at
JOIN users u ON u.id = at.created_by;

INSERT INTO audit_logs (user_id, user_name, username, user_role, action, module, record_id, record_number, athlete_name, sport, details, created_at)
SELECT u.id, u.name, u.username, u.role, 'create', 'bleep_test', bt.id, bt.test_number, bt.athlete_name, bt.sport,
       JSON_OBJECT('sumber', 'migrasi_data_lama', 'tanggal_tes', bt.test_date, 'level', bt.level, 'shuttle', bt.shuttle, 'vo2max', bt.vo2max), bt.created_at
FROM bleep_tests bt
JOIN users u ON u.id = bt.created_by;
