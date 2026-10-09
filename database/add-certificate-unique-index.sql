USE calibration_management;

-- IMPORTANT: run this only after confirming there are no existing duplicate
-- certificate numbers in gauge_calibrations. The PHP save endpoint already
-- checks duplicates; this UNIQUE index is the database-level final protection.
ALTER TABLE gauge_calibrations
    ADD UNIQUE INDEX uq_gauge_calibrations_certificate_no (certificate_no);
