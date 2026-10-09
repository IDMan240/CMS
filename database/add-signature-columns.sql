USE calibration_management;

ALTER TABLE gauge_calibrations
    ADD COLUMN tested_signature VARCHAR(255) NULL AFTER witnessed_by,
    ADD COLUMN witnessed_signature VARCHAR(255) NULL AFTER tested_signature;
