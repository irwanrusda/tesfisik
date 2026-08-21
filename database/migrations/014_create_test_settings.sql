CREATE TABLE IF NOT EXISTS test_settings (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_test_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO test_settings (setting_key, setting_value) VALUES
    ('sit_up_duration_seconds', '60'),
    ('push_up_duration_seconds', '60'),
    ('female_pull_up_mode', 'repetitions')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);
