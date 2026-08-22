ALTER TABLE master_people
    ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT 'spreadsheet' AFTER source_key;
