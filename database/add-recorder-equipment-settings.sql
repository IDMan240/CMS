USE calibration_management;

CREATE TABLE IF NOT EXISTS recorder_equipment_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_type ENUM('pressure','temperature') NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL DEFAULT '',
    equipment_range VARCHAR(255) NOT NULL DEFAULT '',
    calibration_date DATE NULL,
    due_date DATE NULL,
    certificate_no VARCHAR(255) NOT NULL DEFAULT '',
    serial_no VARCHAR(255) NOT NULL DEFAULT '',
    updated_by INT UNSIGNED NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO recorder_equipment_settings
    (equipment_type, name, equipment_range, calibration_date, due_date, certificate_no, serial_no)
VALUES
    ('pressure', 'DEADWEIGHT TESTER', '20-10000 PSI', NULL, NULL, '', ''),
    ('temperature', 'TEMPERATURE CALIBRATOR', '0-300C', NULL, NULL, '', '')
ON DUPLICATE KEY UPDATE equipment_type = VALUES(equipment_type);
