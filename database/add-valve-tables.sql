USE calibration_management;

CREATE TABLE IF NOT EXISTS valve_certificates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    certificate_no VARCHAR(100) NOT NULL UNIQUE,
    client VARCHAR(255) NOT NULL,
    nuprc VARCHAR(255) NULL,
    test_location VARCHAR(255) NULL,
    tested_item VARCHAR(255) NULL,
    valve_class VARCHAR(255) NULL,
    serial_no VARCHAR(255) NULL,
    matp VARCHAR(255) NULL,
    test_date DATE NULL,
    body_test_date DATE NULL,
    seat_test_date DATE NULL,
    body_total_time VARCHAR(100) NULL,
    seat_total_time VARCHAR(100) NULL,
    body_medium VARCHAR(100) NULL,
    seat_medium VARCHAR(100) NULL,
    body_final_pressure VARCHAR(100) NULL,
    seat_final_pressure VARCHAR(100) NULL,
    tested_by VARCHAR(255) NULL,
    tested_name VARCHAR(255) NULL,
    witnessed_by VARCHAR(255) NULL,
    tested_signature VARCHAR(255) NULL,
    witnessed_signature VARCHAR(255) NULL,
    stamp_image VARCHAR(255) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS valve_pressure_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    valve_id INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    cavity_body_test VARCHAR(255) NULL,
    seat_test VARCHAR(255) NULL,
    remarks VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(valve_id),
    CONSTRAINT fk_valve_pressure_log_valve FOREIGN KEY(valve_id) REFERENCES valve_certificates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS valve_physical_checks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    valve_id INT UNSIGNED NOT NULL,
    check_left VARCHAR(255) NULL,
    result_left VARCHAR(100) NULL,
    check_right VARCHAR(255) NULL,
    result_right VARCHAR(100) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(valve_id),
    CONSTRAINT fk_valve_physical_valve FOREIGN KEY(valve_id) REFERENCES valve_certificates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
