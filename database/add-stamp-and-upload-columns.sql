USE calibration_management;

ALTER TABLE gauge_calibrations
    ADD COLUMN IF NOT EXISTS tested_signature VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS witnessed_signature VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS stamp_image VARCHAR(255) NULL;
