-- Recalculate stored Bleep Test categories from VO2max, gender, and age group.
UPDATE bleep_tests
SET category = CASE
    WHEN gender = 'P' THEN CASE
        WHEN TIMESTAMPDIFF(YEAR, COALESCE(birth_date, DATE_SUB(test_date, INTERVAL 20 YEAR)), test_date) BETWEEN 26 AND 35 THEN CASE
            WHEN vo2max > 52 THEN 'Excellent'
            WHEN vo2max >= 45 THEN 'Good'
            WHEN vo2max >= 39 THEN 'Above Average'
            WHEN vo2max >= 35 THEN 'Average'
            WHEN vo2max >= 31 THEN 'Below Average'
            WHEN vo2max >= 26 THEN 'Poor'
            ELSE 'Very Poor'
        END
        WHEN TIMESTAMPDIFF(YEAR, COALESCE(birth_date, DATE_SUB(test_date, INTERVAL 20 YEAR)), test_date) BETWEEN 36 AND 45 THEN CASE
            WHEN vo2max > 45 THEN 'Excellent'
            WHEN vo2max >= 38 THEN 'Good'
            WHEN vo2max >= 34 THEN 'Above Average'
            WHEN vo2max >= 31 THEN 'Average'
            WHEN vo2max >= 27 THEN 'Below Average'
            WHEN vo2max >= 22 THEN 'Poor'
            ELSE 'Very Poor'
        END
        WHEN TIMESTAMPDIFF(YEAR, COALESCE(birth_date, DATE_SUB(test_date, INTERVAL 20 YEAR)), test_date) BETWEEN 46 AND 55 THEN CASE
            WHEN vo2max > 40 THEN 'Excellent'
            WHEN vo2max >= 34 THEN 'Good'
            WHEN vo2max >= 31 THEN 'Above Average'
            WHEN vo2max >= 28 THEN 'Average'
            WHEN vo2max >= 25 THEN 'Below Average'
            WHEN vo2max >= 20 THEN 'Poor'
            ELSE 'Very Poor'
        END
        WHEN TIMESTAMPDIFF(YEAR, COALESCE(birth_date, DATE_SUB(test_date, INTERVAL 20 YEAR)), test_date) BETWEEN 56 AND 65 THEN CASE
            WHEN vo2max > 37 THEN 'Excellent'
            WHEN vo2max >= 32 THEN 'Good'
            WHEN vo2max >= 28 THEN 'Above Average'
            WHEN vo2max >= 25 THEN 'Average'
            WHEN vo2max >= 22 THEN 'Below Average'
            WHEN vo2max >= 18 THEN 'Poor'
            ELSE 'Very Poor'
        END
        WHEN TIMESTAMPDIFF(YEAR, COALESCE(birth_date, DATE_SUB(test_date, INTERVAL 20 YEAR)), test_date) >= 66 THEN CASE
            WHEN vo2max > 32 THEN 'Excellent'
            WHEN vo2max >= 28 THEN 'Good'
            WHEN vo2max >= 25 THEN 'Above Average'
            WHEN vo2max >= 22 THEN 'Average'
            WHEN vo2max >= 19 THEN 'Below Average'
            WHEN vo2max >= 17 THEN 'Poor'
            ELSE 'Very Poor'
        END
        ELSE CASE
            WHEN vo2max > 56 THEN 'Excellent'
            WHEN vo2max >= 47 THEN 'Good'
            WHEN vo2max >= 42 THEN 'Above Average'
            WHEN vo2max >= 38 THEN 'Average'
            WHEN vo2max >= 33 THEN 'Below Average'
            WHEN vo2max >= 28 THEN 'Poor'
            ELSE 'Very Poor'
        END
    END
    ELSE CASE
        WHEN TIMESTAMPDIFF(YEAR, COALESCE(birth_date, DATE_SUB(test_date, INTERVAL 20 YEAR)), test_date) BETWEEN 26 AND 35 THEN CASE
            WHEN vo2max > 56 THEN 'Excellent'
            WHEN vo2max >= 49 THEN 'Good'
            WHEN vo2max >= 43 THEN 'Above Average'
            WHEN vo2max >= 40 THEN 'Average'
            WHEN vo2max >= 35 THEN 'Below Average'
            WHEN vo2max >= 30 THEN 'Poor'
            ELSE 'Very Poor'
        END
        WHEN TIMESTAMPDIFF(YEAR, COALESCE(birth_date, DATE_SUB(test_date, INTERVAL 20 YEAR)), test_date) BETWEEN 36 AND 45 THEN CASE
            WHEN vo2max > 51 THEN 'Excellent'
            WHEN vo2max >= 43 THEN 'Good'
            WHEN vo2max >= 39 THEN 'Above Average'
            WHEN vo2max >= 35 THEN 'Average'
            WHEN vo2max >= 31 THEN 'Below Average'
            WHEN vo2max >= 26 THEN 'Poor'
            ELSE 'Very Poor'
        END
        WHEN TIMESTAMPDIFF(YEAR, COALESCE(birth_date, DATE_SUB(test_date, INTERVAL 20 YEAR)), test_date) BETWEEN 46 AND 55 THEN CASE
            WHEN vo2max > 45 THEN 'Excellent'
            WHEN vo2max >= 39 THEN 'Good'
            WHEN vo2max >= 36 THEN 'Above Average'
            WHEN vo2max >= 32 THEN 'Average'
            WHEN vo2max >= 29 THEN 'Below Average'
            WHEN vo2max >= 25 THEN 'Poor'
            ELSE 'Very Poor'
        END
        WHEN TIMESTAMPDIFF(YEAR, COALESCE(birth_date, DATE_SUB(test_date, INTERVAL 20 YEAR)), test_date) BETWEEN 56 AND 65 THEN CASE
            WHEN vo2max > 41 THEN 'Excellent'
            WHEN vo2max >= 36 THEN 'Good'
            WHEN vo2max >= 32 THEN 'Above Average'
            WHEN vo2max >= 30 THEN 'Average'
            WHEN vo2max >= 26 THEN 'Below Average'
            WHEN vo2max >= 22 THEN 'Poor'
            ELSE 'Very Poor'
        END
        WHEN TIMESTAMPDIFF(YEAR, COALESCE(birth_date, DATE_SUB(test_date, INTERVAL 20 YEAR)), test_date) >= 66 THEN CASE
            WHEN vo2max > 37 THEN 'Excellent'
            WHEN vo2max >= 33 THEN 'Good'
            WHEN vo2max >= 29 THEN 'Above Average'
            WHEN vo2max >= 26 THEN 'Average'
            WHEN vo2max >= 22 THEN 'Below Average'
            WHEN vo2max >= 20 THEN 'Poor'
            ELSE 'Very Poor'
        END
        ELSE CASE
            WHEN vo2max > 60 THEN 'Excellent'
            WHEN vo2max >= 52 THEN 'Good'
            WHEN vo2max >= 47 THEN 'Above Average'
            WHEN vo2max >= 42 THEN 'Average'
            WHEN vo2max >= 37 THEN 'Below Average'
            WHEN vo2max >= 30 THEN 'Poor'
            ELSE 'Very Poor'
        END
    END
END;
