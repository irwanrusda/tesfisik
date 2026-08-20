CREATE TABLE IF NOT EXISTS test_photos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    athlete_test_id BIGINT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(50) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_test_photos_athlete_test FOREIGN KEY (athlete_test_id) REFERENCES athlete_tests(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_test_photos_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_test_photos_athlete_test (athlete_test_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
