ALTER TABLE users
    MODIFY role ENUM('superadmin', 'petugas', 'input', 'panitia') NOT NULL DEFAULT 'input';

UPDATE users SET role = 'input' WHERE role = 'petugas';

ALTER TABLE users
    MODIFY role ENUM('superadmin', 'input', 'panitia') NOT NULL DEFAULT 'input';
