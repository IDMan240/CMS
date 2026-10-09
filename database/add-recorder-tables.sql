USE calibration_management;
CREATE TABLE IF NOT EXISTS recorder_calibrations (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, certificate_no VARCHAR(100) NOT NULL UNIQUE, client VARCHAR(255) NOT NULL,
 nuprc VARCHAR(255) NULL, test_item VARCHAR(255) NULL, manufacturer VARCHAR(255) NULL, serial_no VARCHAR(255) NULL,
 pressure_range DECIMAL(15,4) NOT NULL, pressure_unit VARCHAR(20) NOT NULL DEFAULT 'BAR', temperature_range DECIMAL(15,4) NOT NULL, temperature_unit VARCHAR(20) NOT NULL DEFAULT 'F',
 calibration_date DATE NULL, due_date DATE NULL,
 pressure_equipment_name VARCHAR(255) NULL, pressure_equipment_range VARCHAR(255) NULL, pressure_equipment_calibration_date DATE NULL, pressure_equipment_due_date DATE NULL, pressure_equipment_certificate_no VARCHAR(255) NULL, pressure_equipment_serial VARCHAR(255) NULL,
 temperature_equipment_name VARCHAR(255) NULL, temperature_equipment_range VARCHAR(255) NULL, temperature_equipment_calibration_date DATE NULL, temperature_equipment_due_date DATE NULL, temperature_equipment_certificate_no VARCHAR(255) NULL, temperature_equipment_serial VARCHAR(255) NULL,
 tested_by VARCHAR(255) NULL, witnessed_by VARCHAR(255) NULL, tested_date DATE NULL, witnessed_date DATE NULL, tested_signature VARCHAR(255) NULL, witnessed_signature VARCHAR(255) NULL, stamp_image VARCHAR(255) NULL, created_by INT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS recorder_calibration_readings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, calibration_id INT UNSIGNED NOT NULL, reading_type ENUM('rising','falling') NOT NULL, percentage_range DECIMAL(7,2) NOT NULL,
 actual_value DECIMAL(15,4) NOT NULL DEFAULT 0, dead_weight_reading DECIMAL(15,4) NOT NULL DEFAULT 0, recorder_reading DECIMAL(15,4) NOT NULL DEFAULT 0, temperature_cal DECIMAL(15,4) NOT NULL DEFAULT 0, temperature_recorder DECIMAL(15,4) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(calibration_id), CONSTRAINT fk_recorder_readings_calibration FOREIGN KEY(calibration_id) REFERENCES recorder_calibrations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
