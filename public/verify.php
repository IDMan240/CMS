<?php
/* =========================================================
   CMS CALIBRATION MANAGEMENT SYSTEM
   FILE: verify.php

   PURPOSE:
   PUBLIC CERTIFICATE VERIFICATION

   FEATURES:
   - Verify certificate by certificate number
   - Display complete A4 certificate
   - Display company logo
   - Display tested signature
   - Display witnessed signature
   - Display calibration stamp
   - Display QR verification code
   - Display rising/falling readings
   - Print / Save PDF
========================================================= */

session_start();

require_once __DIR__ . "/../config/database.php";


/* =========================================================
   HELPERS
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   DATE FORMAT
========================================================= */

function formatDate($date)
{
    if (!$date) {
        return "";
    }

    $timestamp = strtotime($date);

    if (!$timestamp) {
        return $date;
    }

    return date("d/m/Y", $timestamp);
}


/* =========================================================
   NUMBER FORMAT
========================================================= */

function formatNumberValue($value)
{
    if ($value === null || $value === '') {
        return "0.00";
    }

    return number_format(
        (float)$value,
        2,
        '.',
        ','
    );
}


/* =========================================================
   PERCENTAGE FORMAT
========================================================= */

function formatPercentage($value)
{
    if ($value === null || $value === '') {
        return "0%";
    }

    $number = (float)$value;

    if (floor($number) == $number) {
        return number_format($number, 0) . "%";
    }

    return number_format($number, 2) . "%";
}


/* =========================================================
   UPLOAD URL
========================================================= */

function verifyUploadUrl($path)
{
    $path = trim((string)$path);

    if ($path === "") {
        return "";
    }

    /*
     * External image
     */
    if (
        strpos($path, "http://") === 0 ||
        strpos($path, "https://") === 0
    ) {
        return htmlspecialchars(
            $path,
            ENT_QUOTES,
            "UTF-8"
        );
    }

    /*
     * Normalize Windows slashes
     */
    $path = str_replace(
        "\\",
        "/",
        $path
    );

    /*
     * Remove leading slash
     */
    $path = ltrim(
        $path,
        "/"
    );

    /*
     * Remove duplicate public/
     */
    if (
        strpos($path, "public/") === 0
    ) {
        $path = substr(
            $path,
            7
        );
    }

    /*
     * Build URL from public folder
     */
    $url = "/public/" . $path;

    return htmlspecialchars(
        $url,
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   GET FIRST AVAILABLE VALUE
========================================================= */

function certificateImageValue(
    array $certificate,
    array $possibleColumns
) {
    foreach (
        $possibleColumns
        as $column
    ) {

        if (
            isset($certificate[$column])
            &&
            trim(
                (string)$certificate[$column]
            ) !== ""
        ) {

            return trim(
                (string)$certificate[$column]
            );
        }
    }

    return "";
}


/* =========================================================
   EXPIRATION STATUS
========================================================= */

function getExpirationStatus($dueDate)
{
    if (!$dueDate) {

        return [
            "type" =>
                "unknown",

            "title" =>
                "EXPIRATION DATE NOT AVAILABLE",

            "message" =>
                "The expiration date is not available.",

            "class" =>
                "status-valid"
        ];
    }


    try {

        $today =
            new DateTimeImmutable(
                date("Y-m-d")
            );


        $expiration =
            new DateTimeImmutable(
                date(
                    "Y-m-d",
                    strtotime($dueDate)
                )
            );


        /*
         * Expiration date itself = expired
         */

        if ($today >= $expiration) {

            $daysExpired =
                (int)$expiration
                    ->diff($today)
                    ->days;


            return [

                "type" =>
                    "expired",

                "title" =>
                    "EXPIRED",

                "message" =>
                    "This certificate expired "
                    .
                    $daysExpired
                    .
                    " day"
                    .
                    (
                        $daysExpired == 1
                            ? ""
                            : "s"
                    )
                    .
                    " ago.",

                "class" =>
                    "status-expired"
            ];
        }


        /*
         * One month before expiration
         */

        $oneMonthBefore =
            $expiration->modify(
                "-1 month"
            );


        if (
            $today >=
            $oneMonthBefore
        ) {

            $daysRemaining =
                (int)$today
                    ->diff($expiration)
                    ->days;


            return [

                "type" =>
                    "soon",

                "title" =>
                    "EXPIRING SOON",

                "message" =>
                    "This certificate will expire "
                    .
                    "in "
                    .
                    $daysRemaining
                    .
                    " day"
                    .
                    (
                        $daysRemaining == 1
                            ? ""
                            : "s"
                    )
                    .
                    ".",

                "class" =>
                    "status-soon"
            ];
        }


        /*
         * Valid
         */

        $daysRemaining =
            (int)$today
                ->diff($expiration)
                ->days;


        return [

            "type" =>
                "valid",

            "title" =>
                "VALID",

            "message" =>
                "This certificate is valid "
                .
                "for another "
                .
                $daysRemaining
                .
                " day"
                .
                (
                    $daysRemaining == 1
                        ? ""
                        : "s"
                )
                .
                ".",

            "class" =>
                "status-valid"
        ];

    } catch (Throwable $e) {

        return [

            "type" =>
                "unknown",

            "title" =>
                "STATUS UNAVAILABLE",

            "message" =>
                "Unable to determine certificate status.",

            "class" =>
                "status-valid"
        ];
    }
}


/* =========================================================
   VARIABLES
========================================================= */

$certificate = null;

$readings = [];

$risingReadings = [];

$fallingReadings = [];

$verified = false;

$error = "";

$certificateNumber =
    isset($_GET["certificate_no"])
        ? trim($_GET["certificate_no"])
        : "";


/* =========================================================
   VERIFY CERTIFICATE
========================================================= */

if (
    $certificateNumber !== ""
) {

    try {

        /* =====================================================
           FIND CERTIFICATE
        ===================================================== */

        $sql = "
            SELECT *
            FROM gauge_calibrations
            WHERE certificate_no =
                :certificate_no
            LIMIT 1
        ";


        $stmt =
            $pdo->prepare($sql);


        $stmt->execute([

            ":certificate_no" =>
                $certificateNumber

        ]);


        $certificate =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        /* =====================================================
           CERTIFICATE FOUND
        ===================================================== */

        if ($certificate) {

            $verified = true;


            /* =================================================
               GET CERTIFICATE IMAGES
            =================================================

            The system checks several possible column names.

            This means the code can work if your database uses:

            company_logo
            logo
            logo_image
            company_logo_path

            and similarly for signatures/stamp.
            ================================================= */

            $companyLogo =
                certificateImageValue(
                    $certificate,
                    [
                        "company_logo",
                        "logo",
                        "logo_image",
                        "company_logo_path",
                        "company_logo_image"
                    ]
                );

            /*
             * The certificate.php template uses the system company logo
             * when no certificate-specific logo was saved.  Verification
             * must therefore use the same logo instead of leaving the
             * header empty.
             */
            if ($companyLogo === "") {
                $companyLogo = "assets/company-logo.png";
            }


            $testedSignature =
                certificateImageValue(
                    $certificate,
                    [
                        "tested_signature",
                        "tested_signature_image",
                        "tested_signature_path",
                        "test_signature",
                        "signature_tested"
                    ]
                );


            $witnessedSignature =
                certificateImageValue(
                    $certificate,
                    [
                        "witnessed_signature",
                        "witnessed_signature_image",
                        "witnessed_signature_path",
                        "witness_signature",
                        "signature_witnessed"
                    ]
                );


            $stampImage =
                certificateImageValue(
                    $certificate,
                    [
                        "stamp_image",
                        "stamp",
                        "calibration_stamp",
                        "stamp_path",
                        "stamp_image_path"
                    ]
                );


            /* =================================================
               GET READINGS
            ================================================= */

            $readingSQL = "
                SELECT *
                FROM gauge_calibration_readings
                WHERE calibration_id =
                    :calibration_id
                ORDER BY

                    CASE

                        WHEN reading_type =
                            'rising'
                        THEN 1

                        WHEN reading_type =
                            'falling'
                        THEN 2

                        ELSE 3

                    END,

                    percentage_range DESC
            ";


            $readingStmt =
                $pdo->prepare(
                    $readingSQL
                );


            $readingStmt->execute([

                ":calibration_id" =>
                    $certificate["id"]

            ]);


            $readings =
                $readingStmt->fetchAll(
                    PDO::FETCH_ASSOC
                );


            /* =================================================
               SEPARATE RISING / FALLING
            ================================================= */

            foreach (
                $readings
                as $reading
            ) {

                $type =
                    strtolower(
                        trim(
                            $reading[
                                "reading_type"
                            ]
                        )
                    );


                if (
                    $type ===
                    "rising"
                ) {

                    $risingReadings[] =
                        $reading;

                }


                elseif (
                    $type ===
                    "falling"
                ) {

                    $fallingReadings[] =
                        $reading;
                }
            }

        }

        else {

            /* =====================================================
               RECORDER CERTIFICATE FALLBACK
               If the number is not a Gauge certificate, check the
               Recorder certificate table and render the Recorder
               certificate through this same public verification page.
            ===================================================== */
            try {
                $recorderStmt = $pdo->prepare("
                    SELECT *
                    FROM recorder_calibrations
                    WHERE certificate_no = :certificate_no
                    LIMIT 1
                ");
                $recorderStmt->execute([
                    ":certificate_no" => $certificateNumber
                ]);
                $recorderCertificate = $recorderStmt->fetch(PDO::FETCH_ASSOC);

                if ($recorderCertificate) {
                    $recorderReadingsStmt = $pdo->prepare("
                        SELECT *
                        FROM recorder_calibration_readings
                        WHERE calibration_id = :calibration_id
                        ORDER BY
                            CASE
                                WHEN reading_type = 'rising' THEN 1
                                WHEN reading_type = 'falling' THEN 2
                                ELSE 3
                            END,
                            id ASC
                    ");
                    $recorderReadingsStmt->execute([
                        ":calibration_id" => $recorderCertificate["id"]
                    ]);
                    $recorderReadings = $recorderReadingsStmt->fetchAll(PDO::FETCH_ASSOC);

                    require __DIR__ . "/verify-recorder-render.php";
                    exit;
                }
            } catch (Throwable $recorderError) {
                /* Recorder tables may not exist on an older installation.
                   In that case, keep the normal not-found response. */
            }

            /* =====================================================
               VALVE CERTIFICATE FALLBACK
               Valve certificates are rendered through the same
               public verification page.
            ===================================================== */
            try {
                $valveStmt = $pdo->prepare("
                    SELECT * FROM valve_certificates
                    WHERE certificate_no = :certificate_no
                    LIMIT 1
                ");
                $valveStmt->execute([":certificate_no" => $certificateNumber]);
                $valveCertificate = $valveStmt->fetch(PDO::FETCH_ASSOC);

                if ($valveCertificate) {
                    $valveLogsStmt = $pdo->prepare("
                        SELECT * FROM valve_pressure_log
                        WHERE valve_id = :valve_id
                        ORDER BY sort_order ASC, id ASC
                    ");
                    $valveLogsStmt->execute([":valve_id" => $valveCertificate["id"]]);
                    $valveLogs = $valveLogsStmt->fetchAll(PDO::FETCH_ASSOC);

                    $valveChecksStmt = $pdo->prepare("
                        SELECT * FROM valve_physical_checks
                        WHERE valve_id = :valve_id
                        ORDER BY sort_order ASC, id ASC
                    ");
                    $valveChecksStmt->execute([":valve_id" => $valveCertificate["id"]]);
                    $valveChecks = $valveChecksStmt->fetchAll(PDO::FETCH_ASSOC);

                    require __DIR__ . "/verify-valve-render.php";
                    exit;
                }
            } catch (Throwable $valveError) {
                /* Valve tables may not exist on an older installation. */
            }

            $error =
                "Certificate number was not found in the Calibration Management System.";
        }

    }

    catch (
        Throwable $e
    ) {

        $error =
            "Unable to verify certificate at this time.";
    }
}


/* =========================================================
   EXPIRATION STATUS
========================================================= */

$expirationStatus = null;


if ($verified) {

    $expirationStatus =
        getExpirationStatus(
            $certificate["due_date"]
        );
}

?>
<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Certificate Verification
</title>


<style>

/* =========================================================
   GLOBAL
========================================================= */

* {

    box-sizing:
        border-box;
}


html,
body {

    margin:
        0;

    padding:
        0;

    background:
        #eeeeee;

    color:
        #111111;

    font-family:
        Tahoma,
        Arial,
        sans-serif;
}


/* =========================================================
   VERIFICATION PAGE
========================================================= */

.verify-page {

    width:
        100%;

    padding:
        20px;
}


/* =========================================================
   VERIFICATION HEADER
========================================================= */

.verify-header {

    background:
        #ffffff;

    border-left:
        5px solid #c90000;

    padding:
        20px;

    margin-bottom:
        15px;
}


.verify-header small {

    display:
        block;

    color:
        #a00000;

    font-size:
        11px;

    font-weight:
        bold;

    letter-spacing:
        1px;

    margin-bottom:
        5px;
}


.verify-header h1 {

    margin:
        0 0 7px;

    font-size:
        25px;
}


.verify-header p {

    margin:
        0;

    color:
        #777777;

    font-size:
        13px;
}


/* =========================================================
   SEARCH FORM
========================================================= */

.verify-form {

    background:
        #ffffff;

    padding:
        20px;

    margin-bottom:
        15px;
}


.verify-form label {

    display:
        block;

    font-size:
        13px;

    font-weight:
        bold;

    margin-bottom:
        8px;
}


.verify-row {

    display:
        flex;

    gap:
        10px;
}


.verify-row input {

    flex:
        1;

    height:
        43px;

    border:
        1px solid #cccccc;

    padding:
        10px;

    font-family:
        Tahoma,
        Arial,
        sans-serif;

    font-size:
        14px;
}


.verify-row button {

    min-width:
        110px;

    border:
        0;

    background:
        #c90000;

    color:
        #ffffff;

    font-family:
        Tahoma,
        Arial,
        sans-serif;

    font-weight:
        bold;

    cursor:
        pointer;
}


/* =========================================================
   ERROR
========================================================= */

.verify-error {

    background:
        #ffe8e8;

    border:
        1px solid #d00000;

    color:
        #a00000;

    padding:
        14px;

    margin-bottom:
        15px;

    font-size:
        13px;

    font-weight:
        bold;
}


/* =========================================================
   VERIFIED MESSAGE
========================================================= */

.verified-message {

    background:
        #e9f8ef;

    border:
        1px solid #58a878;

    padding:
        15px;

    margin-bottom:
        8px;

    color:
        #287b4b;
}


.verified-message strong {

    display:
        block;

    font-size:
        14px;

    margin-bottom:
        4px;
}


.verified-message span {

    font-size:
        12px;
}


/* =========================================================
   EXPIRATION
========================================================= */

.expiration-status {

    padding:
        13px 15px;

    margin-bottom:
        12px;

    border-left:
        5px solid;

    background:
        #ffffff;
}


.expiration-status strong {

    display:
        block;

    font-size:
        14px;

    margin-bottom:
        4px;
}


.expiration-status span {

    font-size:
        12px;
}


.status-valid {

    border-color:
        #218c55;

    color:
        #187341;

    background:
        #eaf8ef;
}


.status-soon {

    border-color:
        #e09b00;

    color:
        #986800;

    background:
        #fff7dc;
}


.status-expired {

    border-color:
        #c90000;

    color:
        #a00000;

    background:
        #ffeaea;
}


/* =========================================================
   PRINT BUTTON
========================================================= */

.certificate-actions {

    width:
        210mm;

    max-width:
        100%;

    margin:
        10px auto;

    display:
        flex;

    justify-content:
        flex-end;
}


.certificate-actions button {

    border:
        0;

    background:
        #c90000;

    color:
        #ffffff;

    padding:
        10px 16px;

    font-family:
        Tahoma,
        Arial,
        sans-serif;

    font-size:
        12px;

    font-weight:
        bold;

    cursor:
        pointer;
}


/* =========================================================
   VERIFICATION OVERLAY
========================================================= */

.verification-overlay {

    position:
        fixed;

    inset:
        0;

    z-index:
        99999;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        rgba(0,0,0,0.72);

    padding:
        20px;
}


.verification-dialog {

    width:
        330px;

    max-width:
        95%;

    background:
        #ffffff;

    border-radius:
        5px;

    padding:
        30px 22px;

    text-align:
        center;

    box-shadow:
        0 10px 35px
        rgba(0,0,0,0.35);

    border-top:
        5px solid #c90000;
}


.verification-dialog h2 {

    margin:
        15px 0 8px;

    font-size:
        20px;

    color:
        #222222;
}


.verification-dialog p {

    margin:
        0;

    color:
        #777777;

    font-size:
        13px;

    line-height:
        1.5;
}


/* =========================================================
   SPINNER
========================================================= */

.verification-spinner {

    width:
        50px;

    height:
        50px;

    margin:
        0 auto;

    border:
        5px solid #eeeeee;

    border-top:
        5px solid #c90000;

    border-radius:
        50%;

    animation:
        verificationSpin
        0.8s linear infinite;
}


@keyframes verificationSpin {

    from {

        transform:
            rotate(0deg);
    }

    to {

        transform:
            rotate(360deg);
    }
}


/* =========================================================
   A4 CERTIFICATE
========================================================= */

.certificate {

    width:
        210mm;

    height:
        297mm;

    margin:
        10mm auto;

    padding:
        5mm;

    background:
        #ffffff;

    border:
        1.5px solid #111111;

    overflow:
        hidden;

    font-family:
        Tahoma,
        Arial,
        sans-serif;

    font-size:
        13px;

    position:
        relative;
}


/* =========================================================
   COMPANY HEADER
========================================================= */

.company-header {

    border:
        1.5px solid #111111;
}


.company-name {

    height:
        19mm;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    text-align:
        center;

    padding:
        2mm 5mm;

    overflow:
        hidden;
}


/* =========================================================
   COMPANY LOGO
========================================================= */

.company-logo {

    display:
        block;

    max-width:
        175mm;

    max-height:
        17mm;

    width:
        auto;

    height:
        auto;

    object-fit:
        contain;

    margin:
        0 auto;
}


/* =========================================================
   COMPANY CONTACT
========================================================= */

.company-contact {

    min-height:
        7mm;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        3px 6px;

    background:
        #dceeff;

    border-top:
        1.5px solid #111111;

    font-size:
        11px;

    text-align:
        center;
}


/* =========================================================
   SECTION HEADERS
========================================================= */

.section-banner {

    height:
        7mm;

    margin-top:
        1.5mm;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        #fff000;

    border:
        1.5px solid #111111;

    font-size:
        12px;

    font-weight:
        bold;

    text-align:
        center;
}


/* =========================================================
   INFORMATION BOX
========================================================= */

.two-column {

    display:
        grid;

    grid-template-columns:
        1fr 1fr;

    width:
        100%;
}


.info-box {

    border-left:
        1.5px solid #111111;

    border-right:
        1.5px solid #111111;

    border-bottom:
        1.5px solid #111111;

    margin:
        0;

    width:
        100%;
}


.info-column {

    padding:
        5px 8px;

    min-height:
        19mm;

    overflow:
        hidden;

    min-width:
        0;
}


.info-column + .info-column {

    border-left:
        1.5px solid #111111;
}


.info-column p {

    margin:
        0 0 4px 0;

    padding:
        0;

    font-size:
        12px;

    line-height:
        1.25;

    white-space:
        normal;

    overflow-wrap:
        anywhere;

    word-break:
        normal;
}


/* =========================================================
   CERTIFICATE NUMBER
========================================================= */

.certificate-number-line {

    display:
        block;

    width:
        100%;

    margin-top:
        2px !important;

    padding:
        0 !important;

    white-space:
        normal;

    overflow-wrap:
        anywhere;

    word-break:
        break-word;
}


.certificate-number-line strong {

    display:
        inline;

    font-weight:
        bold;
}


.certificate-number-value {

    display:
        inline;

    font-weight:
        normal;

    margin-left:
        2px;
}


/* =========================================================
   CALIBRATION TABLE
========================================================= */

.calibration-table {

    width:
        100%;

    border-collapse:
        collapse;

    table-layout:
        fixed;

    font-size:
        11px;

    margin:
        0;
}


.calibration-table th,
.calibration-table td {

    border:
        1.5px solid #111111;

    padding:
        3px 2px;

    text-align:
        center;

    vertical-align:
        middle;
}


.calibration-table th {

    height:
        10mm;

    background:
        #eeeeee;

    font-size:
        10px;

    font-weight:
        bold;

    line-height:
        1.05;
}


.calibration-table td {

    height:
        6mm;

    font-size:
        11px;
}


/* =========================================================
   CERTIFICATE NOTE
========================================================= */

.certificate-note {

    margin-top:
        2mm;

    padding:
        5px 7px;

    border:
        1.5px solid #111111;

    font-size:
        10px;

    line-height:
        1.25;
}


/* =========================================================
   SIGNATURE SECTION
========================================================= */

.signature-section {

    margin-top:
        2mm;

    display:
        grid;

    grid-template-columns:
        1fr 1fr;

    min-height:
        30mm;

    border:
        1.5px solid #111111;
}


.signature-column {

    padding:
        6px 8px;

    overflow:
        hidden;
}


.signature-column + .signature-column {

    border-left:
        1.5px solid #111111;
}


.signature-column h3 {

    margin:
        0 0 7px;

    font-size:
        12px;

    font-weight:
        bold;
}


.signature-column p {

    margin:
        5px 0;

    font-size:
        10px;

    line-height:
        1.15;
}


/* =========================================================
   SIGNATURE IMAGE
========================================================= */

.verify-signature-row {

    display:
        flex;

    align-items:
        center;

    gap:
        5px;

    min-height:
        10mm;
}


.verify-uploaded-signature {

    display:
        inline-block;

    width:
        auto;

    height:
        10mm;

    max-width:
        45mm;

    object-fit:
        contain;

    vertical-align:
        middle;
}


/* =========================================================
   BOTTOM AREA
========================================================= */

.qr-bottom {

    position:
        relative;

    height:
        29mm;

    border-left:
        1.5px solid #111111;

    border-right:
        1.5px solid #111111;

    border-bottom:
        1.5px solid #111111;

    overflow:
        hidden;

    background:
        #ffffff;
}


/* =========================================================
   STAMP
========================================================= */

.verify-stamp {

    position:
        absolute;

    left:
        50%;

    bottom:
        1mm;

    transform:
        translateX(-50%);

    width:
        55mm;

    height:
        25mm;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    overflow:
        hidden;
}


.verify-uploaded-stamp {

    display:
        block;

    max-width:
        55mm;

    max-height:
        25mm;

    width:
        auto;

    height:
        auto;

    object-fit:
        contain;
}


.verify-stamp span {

    font-size:
        10px;

    color:
        #777777;

    border:
        1px dashed #999999;

    padding:
        8px;
}


/* =========================================================
   QR CODE
========================================================= */

.qr-code {

    position:
        absolute;

    right:
        3mm;

    bottom:
        2mm;

    width:
        25mm;

    height:
        25mm;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    overflow:
        hidden;

    background:
        #ffffff;
}


.qr-code img,
.qr-code canvas {

    width:
        25mm !important;

    height:
        25mm !important;

    max-width:
        100%;

    max-height:
        100%;

    display:
        block;
}


/* =========================================================
   HIDDEN CERTIFICATE
========================================================= */

.certificate.verification-hidden {

    display:
        none;
}


/* =========================================================
   MOBILE
========================================================= */

@media screen and (max-width: 800px) {

    .verify-page {

        padding:
            10px;
    }


    .verify-header {

        padding:
            16px;
    }


    .verify-header h1 {

        font-size:
            22px;
    }


    .verify-form {

        padding:
            15px;
    }


    .verify-row {

        flex-direction:
            column;
    }


    .verify-row button {

        height:
            43px;

        width:
            100%;
    }


    /*
     * Keep the certificate A4.
     */

    .certificate {

        zoom:
            1.0;

        width:
            210mm;

        height:
            297mm;

        margin-left:
            auto;

        margin-right:
            auto;

        transform:
            none;

        transform-origin:
            top center;

        overflow:
            hidden;
    }


    .company-logo {

        max-width:
            175mm;

        max-height:
            17mm;
    }


    .qr-bottom {

        height:
            29mm;

        overflow:
            hidden;
    }


    .qr-code {

        right:
            3mm;

        bottom:
            2mm;

        width:
            24mm;

        height:
            24mm;
    }


    .qr-code img,
    .qr-code canvas {

        width:
            24mm !important;

        height:
            24mm !important;
    }


    .verify-uploaded-stamp {

        max-width:
            52mm;

        max-height:
            24mm;
    }


    .verify-uploaded-signature {

        max-width:
            42mm;

        height:
            9mm;
    }
}


/* =========================================================
   VERY SMALL PHONES
========================================================= */

@media screen and (max-width: 420px) {

    .verify-page {

        padding:
            7px;
    }


    .certificate {

        zoom:
            0.95;
    }


    .verification-dialog {

        width:
            300px;

        padding:
            26px 18px;
    }
}


/* =========================================================
   PRINT
========================================================= */

@media print {

    @page {

        size:
            A4 portrait;

        margin:
            0;
    }


    html,
    body {

        width:
            210mm;

        height:
            297mm;

        margin:
            0;

        padding:
            0;

        background:
            #ffffff;
    }


    .verify-page {

        padding:
            0;
    }


    .verify-header,
    .verify-form,
    .verified-message,
    .expiration-status,
    .certificate-actions,
    .verify-error,
    .verification-overlay {

        display:
            none !important;
    }


    .certificate {

        display:
            block !important;

        zoom:
            1 !important;

        width:
            210mm;

        height:
            297mm;

        margin:
            0;

        padding:
            5mm;

        border:
            0;

        overflow:
            hidden;

        box-shadow:
            none;
    }


    .company-logo {

        max-width:
            175mm;

        max-height:
            17mm;
    }


    .section-banner {

        background:
            #fff000 !important;

        -webkit-print-color-adjust:
            exact;

        print-color-adjust:
            exact;
    }


    .company-contact {

        background:
            #dceeff !important;

        -webkit-print-color-adjust:
            exact;

        print-color-adjust:
            exact;
    }


    .calibration-table th {

        background:
            #eeeeee !important;

        -webkit-print-color-adjust:
            exact;

        print-color-adjust:
            exact;
    }


    .qr-code {

        right:
            3mm;

        bottom:
            2mm;
    }


    .verify-stamp {

        bottom:
            1mm;
    }


    * {

        break-inside:
            avoid;
    }
}

</style>

</head>


<body>


<div class="verify-page">


<!-- =====================================================
     VERIFICATION HEADER
===================================================== -->

<div class="verify-header">

    <small>
        CALIBRATION MANAGEMENT SYSTEM
    </small>

    <h1>
        Certificate Verification
    </h1>

    <p>
        Enter the certificate number to verify its authenticity.
    </p>

</div>


<!-- =====================================================
     SEARCH FORM
===================================================== -->

<form
    method="GET"
    action="verify.php"
    class="verify-form"
>

    <label>
        Certificate Number
    </label>


    <div class="verify-row">

        <input
            type="text"
            name="certificate_no"
            value="<?= e($certificateNumber) ?>"
            placeholder="Enter certificate number"
            required
        >


        <button
            type="submit"
        >
            VERIFY
        </button>

    </div>

</form>


<!-- =====================================================
     ERROR
===================================================== -->

<?php if ($error): ?>

<div class="verify-error">

    <?= e($error) ?>

</div>

<?php endif; ?>


<?php if ($verified): ?>


<!-- =====================================================
     VERIFIED MESSAGE
===================================================== -->

<div
    class="verified-message"
    id="verifiedMessage"
>

    <strong>
        ✓ CERTIFICATE VERIFIED
    </strong>


    <span>
        This certificate exists in the
        Calibration Management System.
    </span>

</div>


<!-- =====================================================
     EXPIRATION STATUS
===================================================== -->

<?php if ($expirationStatus): ?>

<div
    class="
        expiration-status
        <?= e(
            $expirationStatus["class"]
        ) ?>
    "
>

    <strong>

        <?= e(
            $expirationStatus["title"]
        ) ?>

    </strong>


    <span>

        <?= e(
            $expirationStatus["message"]
        ) ?>

    </span>

</div>

<?php endif; ?>


<!-- =====================================================
     PRINT BUTTON
===================================================== -->

<div class="certificate-actions">

    <button
        type="button"
        onclick="printCertificate()"
    >
        PRINT / SAVE PDF
    </button>

</div>


<!-- =====================================================
     A4 CERTIFICATE
===================================================== -->

<div
    class="certificate verification-hidden"
    id="verificationCertificate"
>


<!-- =====================================================
     COMPANY HEADER
===================================================== -->

<div class="company-header">


    <div class="company-name">


        <?php if ($companyLogo !== ""): ?>


            <img
                src="<?= verifyUploadUrl($companyLogo) ?>"
                class="company-logo"
                alt="Company Logo"
            >


        <?php else: ?>


            COMPANY NAME / LOGO


        <?php endif; ?>


    </div>


    <div class="company-contact">

        <strong>
            Address:
        </strong>

        &nbsp;

        1234 Industrial Area,
        Port Harcourt,
        Rivers State,
        Nigeria.


        <span style="margin:0 10px;">
            |
        </span>


        <strong>
            Phone:
        </strong>

        &nbsp;

        +234 800 123 4567,
        +234 900 987 6543

    </div>

</div>


<!-- =====================================================
     CALIBRATION CERTIFICATE
===================================================== -->

<div class="section-banner">

    CALIBRATION CERTIFICATE

</div>


<div class="info-box two-column">


    <div class="info-column">

        <p>

            <strong>
                CLIENT:
            </strong>

            <?= e(
                $certificate["client"] ?? ""
            ) ?>

        </p>

    </div>


    <div class="info-column">


        <p>

            <strong>
                NUPRC:
            </strong>

            <?= e(
                $certificate["nuprc"] ?? ""
            ) ?>

        </p>


        <p class="certificate-number-line">

            <strong>
                CERTIFICATE NO:
            </strong>

            <span
                class="certificate-number-value"
            >

                <?= e(
                    $certificate[
                        "certificate_no"
                    ] ?? ""
                ) ?>

            </span>

        </p>

    </div>

</div>


<!-- =====================================================
     CALIBRATION INSTRUMENT DATA
===================================================== -->

<div class="section-banner">

    CALIBRATION INSTRUMENT DATA

</div>


<div class="info-box two-column">


    <div class="info-column">


        <p>

            <strong>
                TEST ITEM:
            </strong>

            <?= e(
                $certificate[
                    "test_item"
                ] ?? ""
            ) ?>

        </p>


        <p>

            <strong>
                MANUFACTURER:
            </strong>

            <?= e(
                $certificate[
                    "manufacturer"
                ] ?? ""
            ) ?>

        </p>


        <p>

            <strong>
                SERIAL NO.:
            </strong>

            <?= e(
                $certificate[
                    "serial_no"
                ] ?? ""
            ) ?>

        </p>


        <p>

            <strong>
                GAUGE CONNECTION:
            </strong>

            <?= e(
                $certificate[
                    "gauge_connection"
                ] ?? ""
            ) ?>

        </p>

    </div>


    <div class="info-column">


        <p>

            <strong>
                INSTRUMENT RANGE:
            </strong>

            <?= e(
                $certificate[
                    "instrument_range"
                ] ?? ""
            ) ?>

            <?= e(
                $certificate[
                    "range_unit"
                ] ?? ""
            ) ?>

        </p>


        <p>

            <strong>
                DATE OF CALIBRATION:
            </strong>

            <?= e(
                formatDate(
                    $certificate[
                        "calibration_date"
                    ] ?? ""
                )
            ) ?>

        </p>


        <p>

            <strong>
                DUE DATE:
            </strong>

            <?= e(
                formatDate(
                    $certificate[
                        "due_date"
                    ] ?? ""
                )
            ) ?>

        </p>

    </div>

</div>


<!-- =====================================================
     CALIBRATION EQUIPMENT
===================================================== -->

<div class="section-banner">

    CALIBRATION EQUIPMENT USED

</div>


<div class="info-box two-column">


    <div class="info-column">


        <p>

            <strong>
                NAME:
            </strong>

            <?= e(
                $certificate[
                    "equipment_name"
                ] ?? ""
            ) ?>

        </p>


        <p>

            <strong>
                EQUIPMENT RANGE:
            </strong>

            <?= e(
                $certificate[
                    "equipment_range"
                ] ?? ""
            ) ?>

        </p>


        <p>

            <strong>
                CALIBRATION DATE:
            </strong>

            <?= e(
                formatDate(
                    $certificate[
                        "equipment_calibration_date"
                    ] ?? ""
                )
            ) ?>

        </p>


        <p>

            <strong>
                NEXT DUE DATE:
            </strong>

            <?= e(
                formatDate(
                    $certificate[
                        "equipment_due_date"
                    ] ?? ""
                )
            ) ?>

        </p>

    </div>


    <div class="info-column">


        <p>

            <strong>
                SERIAL NO.:
            </strong>

            <?= e(
                $certificate[
                    "equipment_serial"
                ] ?? ""
            ) ?>

        </p>


        <p>

            <strong>
                QTY:
            </strong>

            <?= e(
                $certificate[
                    "equipment_qty"
                ] ?? ""
            ) ?>

        </p>


        <p>

            <strong>
                CERTIFICATION NO.:
            </strong>

            <?= e(
                $certificate[
                    "equipment_certification_no"
                ] ?? ""
            ) ?>

        </p>

    </div>

</div>


<!-- =====================================================
     RISING READINGS
===================================================== -->

<div class="section-banner">

    CALIBRATION TEST DATA (RISING READINGS)

</div>


<table class="calibration-table">


<thead>

<tr>

    <th>
        % RANGE
    </th>


    <th>
        ACTUAL VALUE<br>
        (BAR)
    </th>


    <th>
        DEAD WEIGHT<br>
        READING (BAR)
    </th>


    <th>
        GAUGE READING<br>
        (BAR)
    </th>


    <th>
        ERROR %
    </th>

</tr>

</thead>


<tbody>


<?php foreach (
    $risingReadings
    as $row
): ?>


<tr>


    <td>

        <?= e(
            formatPercentage(
                $row[
                    "percentage_range"
                ] ?? 0
            )
        ) ?>

    </td>


    <td>

        <?= e(
            formatNumberValue(
                $row[
                    "actual_value"
                ] ?? 0
            )
        ) ?>

    </td>


    <td>

        <?= e(
            formatNumberValue(
                $row[
                    "dead_weight_reading"
                ] ?? 0
            )
        ) ?>

    </td>


    <td>

        <?= e(
            formatNumberValue(
                $row[
                    "gauge_reading"
                ] ?? 0
            )
        ) ?>

    </td>


    <td>

        <?= e(
            formatNumberValue(
                $row[
                    "error_percent"
                ] ?? 0
            )
        ) ?>%

    </td>


</tr>


<?php endforeach; ?>


</tbody>

</table>


<!-- =====================================================
     FALLING READINGS
===================================================== -->

<div class="section-banner">

    CALIBRATION TEST DATA (FALLING READINGS)

</div>


<table class="calibration-table">


<thead>

<tr>

    <th>
        % RANGE
    </th>


    <th>
        ACTUAL VALUE<br>
        (BAR)
    </th>


    <th>
        DEAD WEIGHT<br>
        READING (BAR)
    </th>


    <th>
        GAUGE READING<br>
        (BAR)
    </th>


    <th>
        ERROR %
    </th>

</tr>

</thead>


<tbody>


<?php foreach (
    $fallingReadings
    as $row
): ?>


<tr>


    <td>

        <?= e(
            formatPercentage(
                $row[
                    "percentage_range"
                ] ?? 0
            )
        ) ?>

    </td>


    <td>

        <?= e(
            formatNumberValue(
                $row[
                    "actual_value"
                ] ?? 0
            )
        ) ?>

    </td>


    <td>

        <?= e(
            formatNumberValue(
                $row[
                    "dead_weight_reading"
                ] ?? 0
            )
        ) ?>

    </td>


    <td>

        <?= e(
            formatNumberValue(
                $row[
                    "gauge_reading"
                ] ?? 0
            )
        ) ?>

    </td>


    <td>

        <?= e(
            formatNumberValue(
                $row[
                    "error_percent"
                ] ?? 0
            )
        ) ?>%

    </td>


</tr>


<?php endforeach; ?>


</tbody>

</table>


<!-- =====================================================
     CERTIFICATE NOTE
===================================================== -->

<div class="certificate-note">

    This certifies that the above

    <strong>
        <?= e(
            $certificate[
                "test_item"
            ] ?? ""
        ) ?>
    </strong>

    has been tested and that this test is
    certified as per test equipment used.

    The readings obtained are as shown above.

    We therefore recommend that the equipment
    can be used for its purpose within the
    calibrated range.

    <strong>
        CAUTION:
    </strong>

    All necessary instruction from the manual
    should be applied during usage and at the usage.

</div>


<!-- =====================================================
     TESTED / WITNESSED
===================================================== -->

<div class="signature-section">


    <!-- =================================================
         TESTED BY
    ================================================= -->

    <div class="signature-column">


        <h3>
            TESTED BY:
        </h3>


        <p>

            <strong>
                Name:
            </strong>

            <?= e(
                $certificate[
                    "tested_by"
                ] ?? ""
            ) ?>

        </p>


        <p>

            <strong>
                Date:
            </strong>

            <?= e(
                formatDate(
                    $certificate[
                        "signature_date"
                    ] ?? ""
                )
            ) ?>

        </p>


        <p class="verify-signature-row">

            <strong>
                Signature:
            </strong>


            <?php if (
                $testedSignature !== ""
            ): ?>


                <img
                    src="<?= verifyUploadUrl(
                        $testedSignature
                    ) ?>"
                    class="
                        verify-uploaded-signature
                    "
                    alt="Tested By Signature"
                >


            <?php endif; ?>


        </p>

    </div>


    <!-- =================================================
         WITNESSED BY
    ================================================= -->

    <div class="signature-column">


        <h3>
            WITNESSED BY:
        </h3>


        <p>

            <strong>
                Name:
            </strong>

            <?= e(
                $certificate[
                    "witnessed_by"
                ] ?? ""
            ) ?>

        </p>


        <p>

            <strong>
                Date:
            </strong>

            <?= e(
                formatDate(
                    $certificate[
                        "signature_date"
                    ] ?? ""
                )
            ) ?>

        </p>


        <p class="verify-signature-row">

            <strong>
                Signature:
            </strong>


            <?php if (
                $witnessedSignature !== ""
            ): ?>


                <img
                    src="<?= verifyUploadUrl(
                        $witnessedSignature
                    ) ?>"
                    class="
                        verify-uploaded-signature
                    "
                    alt="Witnessed By Signature"
                >


            <?php endif; ?>


        </p>

    </div>


</div>


<!-- =====================================================
     BOTTOM STAMP + QR
===================================================== -->

<div class="qr-bottom">


    <!-- =================================================
         CALIBRATION STAMP
    ================================================= -->

    <div class="verify-stamp">


        <?php if (
            $stampImage !== ""
        ): ?>


            <img
                src="<?= verifyUploadUrl(
                    $stampImage
                ) ?>"
                class="
                    verify-uploaded-stamp
                "
                alt="Calibration Stamp"
            >


        <?php else: ?>


            <span>
                CALIBRATION STAMP
            </span>


        <?php endif; ?>


    </div>


    <!-- =================================================
         QR CODE
    ================================================= -->

    <div
        class="qr-code"
        id="verificationQR"
    ></div>


</div>


</div>
<!-- END CERTIFICATE -->


<?php endif; ?>


</div>
<!-- END VERIFY PAGE -->


<?php if ($verified): ?>


<!-- =====================================================
     QR CODE LIBRARY
===================================================== -->

<script
    src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"
></script>


<script>

/* =========================================================
   GENERATE QR CODE
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const qr =
            document.getElementById(
                "verificationQR"
            );


        if (!qr) {

            return;
        }


        if (
            typeof QRCode ===
            "undefined"
        ) {

            return;
        }


        const certificateNo =
            <?= json_encode(
                $certificate[
                    "certificate_no"
                ] ?? ""
            ) ?>;


        const verificationURL =
            window.location.origin
            +
            window.location.pathname
            +
            "?certificate_no="
            +
            encodeURIComponent(
                certificateNo
            );


        qr.innerHTML =
            "";


        new QRCode(
            qr,
            {

                text:
                    verificationURL,

                width:
                    95,

                height:
                    95,

                correctLevel:
                    QRCode.CorrectLevel.M
            }
        );

    }
);


/* =========================================================
   2 SECOND VERIFICATION
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const overlay =
            document.getElementById(
                "verificationOverlay"
            );


        const certificate =
            document.getElementById(
                "verificationCertificate"
            );


        const verifiedMessage =
            document.getElementById(
                "verifiedMessage"
            );


        if (
            !overlay ||
            !certificate
        ) {

            return;
        }


        /*
         * Hide certificate initially.
         */

        certificate.classList.add(
            "verification-hidden"
        );


        /*
         * Hide verified message.
         */

        if (verifiedMessage) {

            verifiedMessage.style.display =
                "none";
        }


        /*
         * Show verification popup.
         */

        overlay.style.display =
            "flex";


        /*
         * Wait exactly 2 seconds.
         */

        setTimeout(
            function () {

                /*
                 * Hide popup
                 */

                overlay.style.display =
                    "none";


                /*
                 * Show verified message
                 */

                if (verifiedMessage) {

                    verifiedMessage.style.display =
                        "block";
                }


                /*
                 * Show certificate
                 */

                certificate.classList.remove(
                    "verification-hidden"
                );

            },
            2000
        );

    }
);


/* =========================================================
   PRINT / SAVE PDF
========================================================= */

function printCertificate()
{
    window.print();
}

</script>

<?php endif; ?>


<!-- =====================================================
     VERIFICATION POPUP
===================================================== -->

<?php if ($verified): ?>


<div
    id="verificationOverlay"
    class="verification-overlay"
>


    <div class="verification-dialog">


        <div
            class="verification-spinner"
        ></div>


        <h2>
            Verifying Certificate
        </h2>


        <p>

            Please wait while we verify
            the authenticity of this
            calibration certificate.

        </p>


    </div>


</div>


<?php endif; ?>


</body>

</html>