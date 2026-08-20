CREATE TABLE IF NOT EXISTS test_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    athlete_test_id BIGINT UNSIGNED NOT NULL,
    test_code VARCHAR(40) NOT NULL,
    result_value DECIMAL(8,2) NULL,
    unit VARCHAR(20) NOT NULL,
    category VARCHAR(50) NULL,
    examiner_notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_results_athlete_test FOREIGN KEY (athlete_test_id) REFERENCES athlete_tests(id) ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY uq_athlete_test_code (athlete_test_id, test_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
