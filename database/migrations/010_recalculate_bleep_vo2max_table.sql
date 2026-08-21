-- Recalculate stored Bleep Test VO2max values to match the standard
-- Tabel VO2max Lari Multi Tahap / Bleep Test level-shuttle conversion.
UPDATE bleep_tests
SET vo2max = ROUND((3.46 * (level + (shuttle / ((level * 0.4325) + 7.0048)))) + 12.19, 1);
