ALTER TABLE test_photos
    ADD COLUMN test_code VARCHAR(40) NULL AFTER athlete_test_id,
    ADD INDEX idx_test_photos_station (athlete_test_id, test_code);
