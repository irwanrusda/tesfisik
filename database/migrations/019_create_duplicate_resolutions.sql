CREATE TABLE IF NOT EXISTS duplicate_resolutions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fingerprint CHAR(64) NOT NULL UNIQUE,
    athlete_key CHAR(64) NOT NULL,
    decision ENUM('separate', 'merged') NOT NULL,
    record_ids TEXT NOT NULL,
    resolved_by BIGINT UNSIGNED NULL,
    resolved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_duplicate_resolutions_user FOREIGN KEY (resolved_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_duplicate_resolutions_athlete (athlete_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
