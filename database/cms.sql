CREATE DATABASE IF NOT EXISTS calibration_management
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE calibration_management;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    username VARCHAR(100) NOT NULL UNIQUE,

    password VARCHAR(255) NOT NULL,

    full_name VARCHAR(150) NOT NULL,

    role ENUM('admin', 'technician') NOT NULL DEFAULT 'technician',

    status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);