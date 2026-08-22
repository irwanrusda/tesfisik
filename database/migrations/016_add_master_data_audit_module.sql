ALTER TABLE audit_logs
    MODIFY module ENUM('tes_fisik', 'tes_fisik_pos', 'bleep_test', 'master_data') NOT NULL;
