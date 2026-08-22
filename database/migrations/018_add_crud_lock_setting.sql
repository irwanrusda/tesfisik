INSERT INTO test_settings (setting_key, setting_value)
VALUES ('crud_locked', '0')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);
