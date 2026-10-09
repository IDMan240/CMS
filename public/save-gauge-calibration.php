<?php

/* =========================================================
   CALIBRATION MANAGEMENT SYSTEM
   FILE 11 — SAVE GAUGE CALIBRATION
========================================================= */

session_start();


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {

    header("Location: ../auth/login.php");

    exit;
}


/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . "/../config/database.php";


/* =========================================================
   ONLY ACCEPT POST
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: gauge.php");

    exit;
}


/* =========================================================
   HELPER
========================================================= */

function postValue($name)
{
    if (!isset($_POST[$name])) {
        return "";
    }

    if (is_array($_POST[$name])) {
        return "";
    }

    return trim((string) $_POST[$name]);
}


/* =========================================================
   BASIC INFORMATION
========================================================= */

$certificateNo =
    postValue("certificate_no");

$client =
    postValue("client");

$nuprc =
    postValue("nuprc");

$testItem =
    postValue("test_item");

$manufacturer =
    postValue("manufacturer");

$serialNo =
    postValue("serial_no");

$gaugeConnection =
    postValue("gauge_connection");

$instrumentRange =
    postValue("instrument_range");

$rangeUnit =
    postValue("range_unit");

$calibrationDate =
    postValue("calibration_date");

$dueDate =
    postValue("due_date");


/* =========================================================
   EQUIPMENT INFORMATION
========================================================= */

/*
 * Accept the normal field used by gauge.js.
 * The fallbacks also make this endpoint tolerant of an older
 * frontend that may send one of the alternate names.
 */
$equipmentName = postValue("equipment_name");

if ($equipmentName === "") {
    $equipmentName = postValue("calibration_equipment_name");
}

if ($equipmentName === "") {
    $equipmentName = postValue("calibration_equipment");
}

if ($equipmentName === "") {
    $equipmentName = postValue("equipment");
}

$equipmentSerial =
    postValue("equipment_serial");

$equipmentRange =
    postValue("equipment_range");

$equipmentQty =
    postValue("equipment_qty");

$equipmentCalDate =
    postValue("equipment_calibration_date");

$equipmentDueDate =
    postValue("equipment_due_date");

$equipmentCertNo =
    postValue("equipment_certification_no");

// Always trust the administrator-controlled equipment values, not client POST data.
$equipmentName = $adminGaugeEquipment['equipment_name'] ?? $equipmentName;
$equipmentSerial = $adminGaugeEquipment['equipment_serial'] ?? $equipmentSerial;
$equipmentRange = $adminGaugeEquipment['equipment_range'] ?? $equipmentRange;
$equipmentQty = $adminGaugeEquipment['equipment_qty'] ?? $equipmentQty;
$equipmentCalDate = $adminGaugeEquipment['equipment_calibration_date'] ?? $equipmentCalDate;
$equipmentDueDate = $adminGaugeEquipment['equipment_due_date'] ?? $equipmentDueDate;
$equipmentCertNo = $adminGaugeEquipment['equipment_certification_no'] ?? $equipmentCertNo;



/* =========================================================
   TESTER / WITNESS
========================================================= */

$testedBy =
    postValue("tested_by");

$witnessedBy =
    postValue("witnessed_by");

$signatureDate =
    postValue("signature_date");


/* =========================================================
   SIGNATURE UPLOADS
========================================================= */
function saveSignatureUpload($fieldName, $prefix)
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]["error"] === UPLOAD_ERR_NO_FILE) return null;
    $file=$_FILES[$fieldName];
    if ($file["error"] !== UPLOAD_ERR_OK) throw new RuntimeException("Unable to upload the {$prefix} signature.");
    if ($file["size"] <= 0 || $file["size"] > 2*1024*1024) throw new RuntimeException("{$prefix} signature must not exceed 2 MB.");
    $finfo=new finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($file["tmp_name"]);
    $allowed=["image/png"=>"png","image/jpeg"=>"jpg","image/webp"=>"webp"];
    if (!isset($allowed[$mime]) || @getimagesize($file["tmp_name"])===false) throw new RuntimeException("{$prefix} signature must be a valid PNG, JPG or WebP image.");
    $dir=__DIR__."/uploads/signatures";
    if(!is_dir($dir) && !mkdir($dir,0755,true)) throw new RuntimeException("Unable to create the signature upload directory.");
    $filename=$prefix."_".bin2hex(random_bytes(16)).".".$allowed[$mime];
    if(!move_uploaded_file($file["tmp_name"],$dir."/".$filename)) throw new RuntimeException("Unable to store the {$prefix} signature.");
    return "uploads/signatures/".$filename;
}

$testedSignaturePath = null;
$witnessedSignaturePath = null;
$stampImagePath = null;


/* =========================================================
   ENSURE UPLOAD COLUMNS EXIST
========================================================= */

function ensureUploadColumns(PDO $pdo)
{
    $columns = [
        "tested_signature" => "VARCHAR(255) NULL",
        "witnessed_signature" => "VARCHAR(255) NULL",
        "stamp_image" => "VARCHAR(255) NULL"
    ];

    foreach ($columns as $column => $definition) {

        $check = $pdo->query(
            "SHOW COLUMNS FROM gauge_calibrations LIKE " .
            $pdo->quote($column)
        );

        if (!$check->fetch(PDO::FETCH_ASSOC)) {

            $pdo->exec(
                "ALTER TABLE gauge_calibrations ADD COLUMN `" .
                $column .
                "` " .
                $definition
            );
        }
    }
}


/* =========================================================
   STAMP UPLOAD
========================================================= */

function saveStampUpload($fieldName)
{
    if (
        !isset($_FILES[$fieldName]) ||
        $_FILES[$fieldName]["error"] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    $file = $_FILES[$fieldName];

    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            "Unable to upload the calibration stamp."
        );
    }

    if (
        $file["size"] <= 0 ||
        $file["size"] > 2 * 1024 * 1024
    ) {
        throw new RuntimeException(
            "Calibration stamp must not exceed 2 MB."
        );
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);

    $allowed = [
        "image/png"  => "png",
        "image/jpeg" => "jpg",
        "image/webp" => "webp"
    ];

    if (
        !isset($allowed[$mime]) ||
        @getimagesize($file["tmp_name"]) === false
    ) {
        throw new RuntimeException(
            "Calibration stamp must be a valid PNG, JPG or WebP image."
        );
    }

    $dir = __DIR__ . "/uploads/stamps";

    if (
        !is_dir($dir) &&
        !mkdir($dir, 0755, true)
    ) {
        throw new RuntimeException(
            "Unable to create the stamp upload directory."
        );
    }

    $filename =
        "stamp_" .
        bin2hex(random_bytes(16)) .
        "." .
        $allowed[$mime];

    if (
        !move_uploaded_file(
            $file["tmp_name"],
            $dir . "/" . $filename
        )
    ) {
        throw new RuntimeException(
            "Unable to store the calibration stamp."
        );
    }

    return "uploads/stamps/" . $filename;
}


/* =========================================================
   VALIDATION
========================================================= */

$missingFields = [];


if ($certificateNo === "") {

    $missingFields[] =
        "Certificate No.";

}


if ($client === "") {

    $missingFields[] =
        "Client";

}


if ($instrumentRange === "") {

    $missingFields[] =
        "Instrument Range";

}


if ($equipmentName === "") {

    $missingFields[] =
        "Calibration Equipment Name";

}


if (!empty($missingFields)) {

    die(
        "<!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport'
                  content='width=device-width, initial-scale=1.0'>

            <title>CMS | Save Error</title>

            <style>

                * {
                    box-sizing: border-box;
                }

                body {
                    margin: 0;
                    min-height: 100vh;

                    display: flex;
                    align-items: center;
                    justify-content: center;

                    padding: 20px;

                    font-family:
                        Arial,
                        Helvetica,
                        sans-serif;

                    background: #f5f5f5;
                    color: #111;
                }

                .error-box {

                    width: 100%;
                    max-width: 500px;

                    padding: 30px;

                    background: #fff;

                    border-top:
                        5px solid #c90000;

                    box-shadow:
                        0 5px 20px
                        rgba(0,0,0,.08);
                }

                h1 {

                    margin: 0 0 15px;

                    color: #c90000;

                    font-size: 22px;
                }

                p {

                    color: #555;

                    line-height: 1.6;

                    font-size: 14px;
                }

                ul {

                    padding-left: 20px;

                    line-height: 1.8;

                    color: #111;
                }

                a {

                    display: inline-block;

                    margin-top: 15px;

                    padding: 12px 18px;

                    background: #c90000;

                    color: #fff;

                    text-decoration: none;

                    font-weight: bold;

                    font-size: 12px;
                }

            </style>
        </head>

        <body>

            <div class='error-box'>

                <h1>
                    Required Information Missing
                </h1>

                <p>
                    Please provide the following information:
                </p>

                <ul>"
                .
                implode(
                    "",
                    array_map(
                        function ($field) {

                            return
                                "<li>"
                                .
                                htmlspecialchars($field)
                                .
                                "</li>";

                        },
                        $missingFields
                    )
                )
                .
                "</ul>

                <a href='gauge.php'>
                    ← Return to Gauge Calibration
                </a>

            </div>

        </body>
        </html>"
    );

    exit;
}


/* =========================================================
   VALIDATE RANGE
========================================================= */

if (!is_numeric($instrumentRange)) {

    die(
        "Instrument range must be a valid number."
    );

}


$instrumentRange =
    (float) $instrumentRange;


if ($instrumentRange <= 0) {

    die(
        "Instrument range must be greater than zero."
    );

}


/* =========================================================
   READ CALIBRATION TABLES
========================================================= */

$rising =
    isset($_POST["rising"])
    && is_array($_POST["rising"])
        ? $_POST["rising"]
        : [];


$falling =
    isset($_POST["falling"])
    && is_array($_POST["falling"])
        ? $_POST["falling"]
        : [];


/* =========================================================
   VALIDATE READING
========================================================= */

function readingNumber($value)
{
    if (
        $value === null ||
        $value === ""
    ) {
        return 0;
    }

    return is_numeric($value)
        ? (float) $value
        : 0;
}


/* =========================================================
   DUPLICATE CERTIFICATE NUMBER PROTECTION
========================================================= */

if ($certificateNo === "") {

    die(
        "<!DOCTYPE html>" .
        "<html><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"><title>CMS | Certificate Number Required</title>" .
        "<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:#f5f7fb;font-family:Arial,sans-serif}.box{max-width:620px;width:100%;background:#fff;border:1px solid #ddd;border-radius:14px;padding:30px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,.08)}h1{color:#b42318;font-size:24px;margin:0 0 12px}.msg{font-size:16px;color:#333;line-height:1.6}a{display:inline-block;margin-top:20px;padding:12px 20px;background:#111;color:#fff;text-decoration:none;border-radius:8px}</style></head><body><div class=\"box\"><h1>CERTIFICATE NUMBER REQUIRED</h1><div class=\"msg\">Please enter a certificate number before saving the calibration certificate.</div><a href=\"gauge.php\">BACK TO GAUGE</a></div></body></html>"
    );
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS gauge_equipment_settings (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, equipment_name VARCHAR(255) NOT NULL DEFAULT '', equipment_range VARCHAR(255) NOT NULL DEFAULT '', equipment_serial VARCHAR(255) NOT NULL DEFAULT '', equipment_calibration_date DATE NULL, equipment_due_date DATE NULL, equipment_qty VARCHAR(100) NOT NULL DEFAULT '', equipment_certification_no VARCHAR(255) NOT NULL DEFAULT '', updated_by INT UNSIGNED NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("INSERT INTO gauge_equipment_settings (id,equipment_name,equipment_range) VALUES (1,'DEADWEIGHT TESTER','20-10000 PSI') ON DUPLICATE KEY UPDATE id=id");
    $adminGaugeEquipment=$pdo->query("SELECT * FROM gauge_equipment_settings WHERE id=1")->fetch(PDO::FETCH_ASSOC) ?: [];
    $pdo->exec("CREATE TABLE IF NOT EXISTS nuprc_settings (id INT UNSIGNED PRIMARY KEY, nuprc VARCHAR(255) NOT NULL DEFAULT '', updated_by INT UNSIGNED NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("INSERT INTO nuprc_settings (id,nuprc) VALUES (1,'') ON DUPLICATE KEY UPDATE id=id");
    $adminNuprc=(string)($pdo->query("SELECT nuprc FROM nuprc_settings WHERE id=1")->fetchColumn() ?: "");
    $nuprc=$adminNuprc;


    /* Check before uploads and before INSERT so a duplicate certificate
       cannot create unnecessary signature/stamp files. */
    $duplicateCheck = $pdo->prepare("
        SELECT id
        FROM gauge_calibrations
        WHERE certificate_no = :certificate_no
        LIMIT 1
    ");

    $duplicateCheck->execute([
        ":certificate_no" => $certificateNo
    ]);

    $existingCertificateId = $duplicateCheck->fetchColumn();

    // Certificate numbers are shared by Gauge and Recorder modules.
    // If the Recorder table exists, prevent a Gauge certificate from reusing its number.
    if ($existingCertificateId === false) {
        try {
            $recorderTableExists = $pdo->query("SHOW TABLES LIKE 'recorder_calibrations'")->fetchColumn();
            if ($recorderTableExists) {
                $recorderDuplicate = $pdo->prepare("SELECT id FROM recorder_calibrations WHERE certificate_no = :certificate_no LIMIT 1");
                $recorderDuplicate->execute([":certificate_no" => $certificateNo]);
                $existingCertificateId = $recorderDuplicate->fetchColumn();
            }
        } catch (Throwable $ignore) {
            // Gauge saving remains available if the optional Recorder table is not ready.
        }
    }

    if ($existingCertificateId !== false) {

        $safeCertificateNo = htmlspecialchars($certificateNo, ENT_QUOTES, 'UTF-8');

        die(
            "<!DOCTYPE html>" .
            "<html><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"><title>CMS | Certificate Number Already Used</title>" .
            "<script>window.addEventListener('load',function(){alert('CERTIFICATE NUMBER ALREADY USED\n\nCertificate No: " . addslashes($safeCertificateNo) . "\n\nThis certificate number is already registered. The certificate was NOT saved.\n\nPlease use a different certificate number.');window.location.href='gauge.php';});</script>" .
            "<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:#f5f7fb;font-family:Arial,sans-serif}.box{max-width:520px;width:100%;background:#fff;border:1px solid #ddd;border-radius:14px;padding:28px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,.08)}h1{color:#b42318;font-size:22px;margin:0 0 10px}.number{font-weight:700;word-break:break-word}</style></head><body><div class=\"box\"><h1>Certificate Number Already Used</h1><p>The certificate number <span class=\"number\">" . $safeCertificateNo . "</span> is already registered.</p><p>The certificate was not saved.</p></div></body></html>"
        );
    }

    ensureUploadColumns($pdo);

    $testedSignaturePath =
        saveSignatureUpload(
            "tested_signature",
            "tested"
        );

    $witnessedSignaturePath =
        saveSignatureUpload(
            "witnessed_signature",
            "witnessed"
        );

    $stampImagePath =
        saveStampUpload("stamp_image");

    $pdo->beginTransaction();


    /* =====================================================
       SAVE CALIBRATION HEADER
    ===================================================== */

    $sql = "

        INSERT INTO gauge_calibrations (

            certificate_no,

            client,

            nuprc,

            test_item,

            manufacturer,

            serial_no,

            gauge_connection,

            instrument_range,

            range_unit,

            calibration_date,

            due_date,

            equipment_name,

            equipment_serial,

            equipment_range,

            equipment_qty,

            equipment_calibration_date,

            equipment_due_date,

            equipment_certification_no,

            tested_by,

            witnessed_by,

            tested_signature,

            witnessed_signature,

            stamp_image,

            signature_date,

            created_by

        )

        VALUES (

            :certificate_no,

            :client,

            :nuprc,

            :test_item,

            :manufacturer,

            :serial_no,

            :gauge_connection,

            :instrument_range,

            :range_unit,

            :calibration_date,

            :due_date,

            :equipment_name,

            :equipment_serial,

            :equipment_range,

            :equipment_qty,

            :equipment_calibration_date,

            :equipment_due_date,

            :equipment_certification_no,

            :tested_by,

            :witnessed_by,

            :tested_signature,

            :witnessed_signature,

            :stamp_image,

            :signature_date,

            :created_by

        )

    ";


    $stmt =
        $pdo->prepare($sql);


    $stmt->execute([

        ":certificate_no" =>
            $certificateNo,

        ":client" =>
            $client,

        ":nuprc" =>
            $nuprc,

        ":test_item" =>
            $testItem,

        ":manufacturer" =>
            $manufacturer,

        ":serial_no" =>
            $serialNo,

        ":gauge_connection" =>
            $gaugeConnection,

        ":instrument_range" =>
            $instrumentRange,

        ":range_unit" =>
            $rangeUnit,

        ":calibration_date" =>
            $calibrationDate !== ""
                ? $calibrationDate
                : null,

        ":due_date" =>
            $dueDate !== ""
                ? $dueDate
                : null,

        ":equipment_name" =>
            $equipmentName,

        ":equipment_serial" =>
            $equipmentSerial,

        ":equipment_range" =>
            $equipmentRange,

        ":equipment_qty" =>
            $equipmentQty,

        ":equipment_calibration_date" =>
            $equipmentCalDate !== ""
                ? $equipmentCalDate
                : null,

        ":equipment_due_date" =>
            $equipmentDueDate !== ""
                ? $equipmentDueDate
                : null,

        ":equipment_certification_no" =>
            $equipmentCertNo,

        ":tested_by" =>
            $testedBy,

        ":witnessed_by" =>
            $witnessedBy,

        ":tested_signature" =>
            $testedSignaturePath,

        ":witnessed_signature" =>
            $witnessedSignaturePath,

        ":stamp_image" =>
            $stampImagePath,

        ":signature_date" =>
            $signatureDate !== ""
                ? $signatureDate
                : null,

        ":created_by" =>
            $_SESSION["user_id"]

    ]);


    $calibrationId = (int)$pdo->lastInsertId();

    if ($calibrationId <= 0) {

        $lookup = $pdo->prepare("
            SELECT id
            FROM gauge_calibrations
            WHERE certificate_no = :certificate_no
              AND created_by = :created_by
            ORDER BY id DESC
            LIMIT 1
        ");

        $lookup->execute([
            ":certificate_no" => $certificateNo,
            ":created_by" => $_SESSION["user_id"]
        ]);

        $calibrationId =
            (int)$lookup->fetchColumn();
    }

    if ($calibrationId <= 0) {
        throw new RuntimeException(
            "The certificate was saved, but its ID could not be resolved."
        );
    }


    /* =====================================================
       SAVE READING STATEMENT
    ===================================================== */

    $readingSQL = "

        INSERT INTO
        gauge_calibration_readings (

            calibration_id,

            reading_type,

            percentage_range,

            actual_value,

            dead_weight_reading,

            gauge_reading,

            error_percent

        )

        VALUES (

            :calibration_id,

            :reading_type,

            :percentage_range,

            :actual_value,

            :dead_weight_reading,

            :gauge_reading,

            :error_percent

        )

    ";


    $readingStmt =
        $pdo->prepare($readingSQL);


    /* =====================================================
       SAVE RISING READINGS
    ===================================================== */

    foreach ($rising as $row) {

        if (!is_array($row)) {
            continue;
        }


        $percentage =
            readingNumber(
                $row["percentage"] ?? 0
            );


        $actual =
            readingNumber(
                $row["actual"] ?? 0
            );


        $deadWeight =
            readingNumber(
                $row["dead_weight"] ?? 0
            );


        $gauge =
            readingNumber(
                $row["gauge"] ?? 0
            );


        $error =
            readingNumber(
                $row["error"] ?? 0
            );


        $readingStmt->execute([

            ":calibration_id" =>
                $calibrationId,

            ":reading_type" =>
                "rising",

            ":percentage_range" =>
                $percentage,

            ":actual_value" =>
                $actual,

            ":dead_weight_reading" =>
                $deadWeight,

            ":gauge_reading" =>
                $gauge,

            ":error_percent" =>
                $error

        ]);

    }


    /* =====================================================
       SAVE FALLING READINGS
    ===================================================== */

    foreach ($falling as $row) {

        if (!is_array($row)) {
            continue;
        }


        $percentage =
            readingNumber(
                $row["percentage"] ?? 0
            );


        $actual =
            readingNumber(
                $row["actual"] ?? 0
            );


        $deadWeight =
            readingNumber(
                $row["dead_weight"] ?? 0
            );


        $gauge =
            readingNumber(
                $row["gauge"] ?? 0
            );


        $error =
            readingNumber(
                $row["error"] ?? 0
            );


        $readingStmt->execute([

            ":calibration_id" =>
                $calibrationId,

            ":reading_type" =>
                "falling",

            ":percentage_range" =>
                $percentage,

            ":actual_value" =>
                $actual,

            ":dead_weight_reading" =>
                $deadWeight,

            ":gauge_reading" =>
                $gauge,

            ":error_percent" =>
                $error

        ]);

    }


    /* =====================================================
       COMMIT
    ===================================================== */

    $pdo->commit();


    /* =====================================================
       SUCCESS
    ===================================================== */

  header(
    "Location: ../certificate.php?id="
    . urlencode($calibrationId)
);

exit;


} catch (Throwable $e) {


    /* =====================================================
       ROLLBACK
    ===================================================== */

    if (
        isset($pdo)
        && $pdo instanceof PDO
        && $pdo->inTransaction()
    ) {

        $pdo->rollBack();

    }

    foreach (
        [
            $testedSignaturePath,
            $witnessedSignaturePath,
            $stampImagePath
        ] as $relativePath
    ) {
        if ($relativePath) { $absolutePath=__DIR__."/".$relativePath; if(is_file($absolutePath)) @unlink($absolutePath); }
    }


    /* =====================================================
       DUPLICATE CERTIFICATE NUMBER (DATABASE SAFETY NET)
    ===================================================== */

    if ($e instanceof PDOException && $e->getCode() === "23000") {

        $safeCertificateNo = htmlspecialchars($certificateNo, ENT_QUOTES, 'UTF-8');

        die(
            "<!DOCTYPE html>" .
            "<html><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"><title>CMS | Certificate Number Already Used</title>" .
            "<script>window.addEventListener('load',function(){alert('CERTIFICATE NUMBER ALREADY USED\n\nCertificate No: " . addslashes($safeCertificateNo) . "\n\nThe database prevented a duplicate certificate from being created.');window.location.href='gauge.php';});</script>" .
            "</head><body></body></html>"
        );
    }


    /* =====================================================
       DATABASE ERROR
    ===================================================== */

    die(

        "<!DOCTYPE html>

        <html>

        <head>

            <meta charset='UTF-8'>

            <meta name='viewport'
                  content='width=device-width, initial-scale=1.0'>

            <title>
                CMS | Database Error
            </title>

            <style>

                * {
                    box-sizing: border-box;
                }

                body {

                    margin: 0;

                    min-height: 100vh;

                    display: flex;

                    align-items: center;

                    justify-content: center;

                    padding: 20px;

                    font-family:
                        Arial,
                        Helvetica,
                        sans-serif;

                    background: #f5f5f5;

                }

                .error-box {

                    width: 100%;

                    max-width: 650px;

                    padding: 30px;

                    background: #fff;

                    border-top:
                        5px solid #c90000;

                    box-shadow:
                        0 5px 20px
                        rgba(0,0,0,.08);

                }

                h1 {

                    margin-top: 0;

                    color: #c90000;

                    font-size: 22px;

                }

                .message {

                    padding: 15px;

                    background: #f8f8f8;

                    border-left:
                        4px solid #111;

                    font-family:
                        monospace;

                    font-size: 12px;

                    line-height: 1.6;

                    overflow-x: auto;

                }

                a {

                    display: inline-block;

                    margin-top: 20px;

                    padding: 12px 18px;

                    background: #c90000;

                    color: #fff;

                    text-decoration: none;

                    font-weight: bold;

                    font-size: 12px;

                }

            </style>

        </head>

        <body>

            <div class='error-box'>

                <h1>
                    Calibration Could Not Be Saved
                </h1>

                <p>
                    The database returned the following error:
                </p>

                <div class='message'>"

                .
                htmlspecialchars(
                    $e->getMessage()
                )

                .
                "</div>

                <a href='gauge.php'>
                    ← Return to Gauge Calibration
                </a>

            </div>

        </body>

        </html>"

    );

}