<?php
/* =========================================================
   CMS — RECORDER CALIBRATION CERTIFICATE
   V6 — GAUGE-STYLE CERTIFICATE LAYOUT
========================================================= */
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$certificateNo = trim((string)($_GET["certificate_no"] ?? ""));

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM recorder_calibrations WHERE id = :id LIMIT 1");
        $stmt->execute([":id" => $id]);
    } elseif ($certificateNo !== "") {
        $stmt = $pdo->prepare("SELECT * FROM recorder_calibrations WHERE certificate_no = :certificate_no LIMIT 1");
        $stmt->execute([":certificate_no" => $certificateNo]);
    } else {
        die("Invalid recorder certificate ID.");
    }

    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$certificate) {
        die("Recorder certificate not found.");
    }

    $id = (int)$certificate["id"];

    $stmt = $pdo->prepare("
        SELECT *
        FROM recorder_calibration_readings
        WHERE calibration_id = :calibration_id
        ORDER BY reading_type ASC, id ASC
    ");
    $stmt->execute([":calibration_id" => $id]);
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    die("Unable to load recorder certificate.");
}

function h($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function df($date) {
    if (!$date) return "";
    $time = strtotime($date);
    return $time ? date("d/m/Y", $time) : h($date);
}

function nf($value) {
    if ($value === null || $value === "") return "0";
    return rtrim(rtrim(number_format((float)$value, 2, ".", ""), "0"), ".");
}

/*
 * Read an uploaded image from the server and embed it directly as a
 * data URI. This avoids broken local/WebView image paths.
 */
function imageDataUri($path) {
    $path = trim((string)$path);
    if ($path === "") return "";

    $path = str_replace("\\", "/", $path);
    $path = ltrim($path, "/");

    if (strpos($path, "public/") === 0) {
        $path = substr($path, 7);
    }

    $candidates = [
        __DIR__ . "/" . $path,
        __DIR__ . "/uploads/" . basename($path)
    ];

    $full = "";
    foreach ($candidates as $candidate) {
        if (is_file($candidate) && is_readable($candidate)) {
            $full = $candidate;
            break;
        }
    }

    if ($full === "") return "";

    $mime = "";
    if (function_exists("finfo_open")) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $full);
            finfo_close($finfo);
        }
    }

    if (!$mime) {
        $mime = @mime_content_type($full) ?: "";
    }

    if (!$mime) return "";

    $data = @file_get_contents($full);
    if ($data === false) return "";

    return "data:" . $mime . ";base64," . base64_encode($data);
}

function imageTag($path, $class, $alt) {
    $src = imageDataUri($path);
    if ($src === "") return "";
    return '<img src="' . h($src) . '" class="' . h($class) . '" alt="' . h($alt) . '">';
}

$risingReadings = [];
$fallingReadings = [];

foreach ($readings as $row) {
    if (($row["reading_type"] ?? "") === "rising") {
        $risingReadings[] = $row;
    } elseif (($row["reading_type"] ?? "") === "falling") {
        $fallingReadings[] = $row;
    }
}

$pressureUnit = h($certificate["pressure_unit"] ?? "BAR");
$temperatureUnit = h($certificate["temperature_unit"] ?? "F");

$testedSignature = $certificate["tested_signature"] ?? "";
$witnessedSignature = $certificate["witnessed_signature"] ?? "";
$stampImage = $certificate["stamp_image"] ?? "";

$qrData = json_encode([
    "certificate_no"      => $certificate["certificate_no"] ?? "",
    "client"              => $certificate["client"] ?? "",
    "test_item"           => $certificate["test_item"] ?? "TEMP./PRESSURE RECORDER",
    "serial_no"           => $certificate["serial_no"] ?? "",
    "pressure_range"      => ($certificate["pressure_range"] ?? "") . " " . ($certificate["pressure_unit"] ?? ""),
    "temperature_range"   => ($certificate["temperature_range"] ?? "") . " " . ($certificate["temperature_unit"] ?? ""),
    "calibration"         => $certificate["calibration_date"] ?? "",
    "due_date"            => $certificate["due_date"] ?? ""
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calibration Certificate - <?= h($certificate["certificate_no"]) ?></title>
<link rel="stylesheet" href="css/recorder-certificate.css?v=6.0.0">
</head>

<body>

<!-- These controls are OUTSIDE the certificate, exactly like the Gauge certificate. -->
<div class="print-actions">
    <button type="button" onclick="window.print()">PRINT / SAVE PDF</button>
    <a href="recorder.php">NEW RECORDER</a>
</div>

<main class="certificate">

    <!-- COMPANY HEADER -->
    <section class="company-header">
        <div class="company-name">
            <img src="assets/company-logo.png" alt="Company Logo">
        </div>
    </section>

    <div class="blue-bar"></div>

    <!-- CERTIFICATE -->
    <div class="section-banner">CALIBRATION CERTIFICATE</div>

    <section class="two-column info-box certificate-info-box">
        <div class="info-column">
            <p><strong>CLIENT:</strong> <?= h($certificate["client"]) ?></p>
        </div>
        <div class="info-column">
            <p><strong>NUPRC/OGISP:</strong> <?= h($certificate["nuprc"]) ?></p>
            <p><strong>CERTIFICATE NO:</strong> <?= h($certificate["certificate_no"]) ?></p>
        </div>
    </section>

    <!-- INSTRUMENT DATA -->
    <div class="section-banner">CALIBRATION INSTRUMENT DATA</div>

    <section class="two-column info-box instrument-info-box">
        <div class="info-column">
            <p><strong>TEST ITEM:</strong> <?= h($certificate["test_item"]) ?></p>
            <p><strong>MANUFACTURER:</strong> <?= h($certificate["manufacturer"]) ?></p>
            <p><strong>SERIAL NO:</strong> <?= h($certificate["serial_no"]) ?></p>
            <p><strong>INSTRUMENT RANGE:</strong> 0 - <?= nf($certificate["pressure_range"]) ?> <?= $pressureUnit ?></p>
        </div>
        <div class="info-column">
            <p><strong>TEMPERATURE RANGE:</strong> 0–<?= nf($certificate["temperature_range"]) ?> <?= $temperatureUnit ?></p>
            <p><strong>DATE OF CALIBRATION:</strong> <?= df($certificate["calibration_date"]) ?></p>
            <p><strong>DUE DATE:</strong> <?= df($certificate["due_date"]) ?></p>
        </div>
    </section>

    <!-- EQUIPMENT -->
    <div class="section-banner">CALIBRATION EQUIPMENT USED</div>

    <section class="two-column info-box equipment-info-box">
        <div class="info-column">
            <p><strong>NAME:</strong> <?= h($certificate["pressure_equipment_name"]) ?></p>
            <p><strong>EQUIPMENT RANGE:</strong> <?= h($certificate["pressure_equipment_range"]) ?></p>
            <p><strong>CALIBRATION DATE:</strong> <?= df($certificate["pressure_equipment_calibration_date"]) ?></p>
            <p><strong>NEXT DUE DATE:</strong> <?= df($certificate["pressure_equipment_due_date"]) ?></p>
            <p><strong>CERTIFICATE NO:</strong> <?= h($certificate["pressure_equipment_certificate_no"]) ?></p>
            <p><strong>SERIAL NO:</strong> <?= h($certificate["pressure_equipment_serial"]) ?></p>
        </div>
        <div class="info-column">
            <p><strong>NAME:</strong> <?= h($certificate["temperature_equipment_name"]) ?></p>
            <p><strong>EQUIPMENT RANGE:</strong> <?= h($certificate["temperature_equipment_range"]) ?></p>
            <p><strong>CALIBRATION DATE:</strong> <?= df($certificate["temperature_equipment_calibration_date"]) ?></p>
            <p><strong>NEXT DUE DATE:</strong> <?= df($certificate["temperature_equipment_due_date"]) ?></p>
            <p><strong>CERTIFICATE NO:</strong> <?= h($certificate["temperature_equipment_certificate_no"]) ?></p>
            <p><strong>SERIAL NO:</strong> <?= h($certificate["temperature_equipment_serial"]) ?></p>
        </div>
    </section>

    <!-- RISING -->
    <div class="section-banner">CALIBRATION TEST DATA (RISING READINGS)</div>

    <table class="calibration-table">
        <thead>
        <tr>
            <th>%<br>RANGE</th>
            <th>ACTUAL<br>VALUE<br>(<?= $pressureUnit ?>)</th>
            <th>DEAD WEIGHT<br>READINGS<br>(<?= $pressureUnit ?>)</th>
            <th>RECORDER<br>READINGS<br>(<?= $pressureUnit ?>)</th>
            <th>TEMP<br>CAL.<br>(<?= $temperatureUnit ?>)</th>
            <th>RECORDER<br>READINGS<br>(<?= $temperatureUnit ?>)</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($risingReadings as $row): ?>
            <tr>
                <td><?= nf($row["percentage_range"]) ?></td>
                <td><?= nf($row["actual_value"]) ?></td>
                <td><?= nf($row["dead_weight_reading"]) ?></td>
                <td><?= nf($row["recorder_reading"]) ?></td>
                <td><?= nf($row["temperature_cal"]) ?></td>
                <td><?= nf($row["temperature_recorder"]) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- FALLING -->
    <div class="section-banner">CALIBRATION TEST DATA (FALLING READINGS)</div>

    <table class="calibration-table">
        <thead>
        <tr>
            <th>%<br>RANGE</th>
            <th>ACTUAL<br>VALUE<br>(<?= $pressureUnit ?>)</th>
            <th>DEAD WEIGHT<br>READINGS<br>(<?= $pressureUnit ?>)</th>
            <th>RECORDER<br>READINGS<br>(<?= $pressureUnit ?>)</th>
            <th>TEMP<br>CAL.<br>(<?= $temperatureUnit ?>)</th>
            <th>RECORDER<br>READINGS<br>(<?= $temperatureUnit ?>)</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($fallingReadings as $row): ?>
            <tr>
                <td><?= nf($row["percentage_range"]) ?></td>
                <td><?= nf($row["actual_value"]) ?></td>
                <td><?= nf($row["dead_weight_reading"]) ?></td>
                <td><?= nf($row["recorder_reading"]) ?></td>
                <td><?= nf($row["temperature_cal"]) ?></td>
                <td><?= nf($row["temperature_recorder"]) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- STATEMENT -->
    <section class="certificate-note">
        This is to certify that the above instrument has been tested and its test certified as per test equipment used.
        The readings are as shown above. We therefore recommend that the equipment can be used for its purpose within the calibrated range.
    </section>

    <!-- TESTED / WITNESSED -->
    <section class="signature-section">

        <div class="signature-column">
            <h3>TESTED BY:</h3>

            <p><strong>Name:</strong> <?= h($certificate["tested_by"]) ?></p>

            <p><strong>Date:</strong> <?= df($certificate["tested_date"]) ?></p>

            <p class="signature-row">
                <strong>Signature:</strong>
                <?= imageTag($testedSignature, "uploaded-signature", "Tested By signature") ?>
                <?php if (imageDataUri($testedSignature) === ""): ?>
                    <span class="signature-line"></span>
                <?php endif; ?>
            </p>
        </div>

        <div class="signature-column">
            <h3>WITNESSED BY:</h3>

            <p><strong>Name:</strong> <?= h($certificate["witnessed_by"]) ?></p>

            <p><strong>Date:</strong> <?= df($certificate["witnessed_date"]) ?></p>

            <p class="signature-row">
                <strong>Signature:</strong>
                <?= imageTag($witnessedSignature, "uploaded-signature", "Witnessed By signature") ?>
                <?php if (imageDataUri($witnessedSignature) === ""): ?>
                    <span class="signature-line"></span>
                <?php endif; ?>
            </p>
        </div>

    </section>

    <!-- STAMP + QR, BELOW SIGNATURES LIKE GAUGE -->
    <section class="bottom-certification-area">

        <div class="stamp-section">
            <?= imageTag($stampImage, "uploaded-stamp", "Calibration stamp") ?>
        </div>

        <div class="qr-section">
            <div id="certificateQRCode" class="qr-code"></div>
        </div>

    </section>

</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const verificationData = <?= $qrData ?: "{}" ?>;

document.addEventListener("DOMContentLoaded", function () {
    const qr = document.getElementById("certificateQRCode");
    if (!qr || typeof QRCode === "undefined") return;

    qr.innerHTML = "";

    new QRCode(qr, {
        text: JSON.stringify(verificationData),
        width: 90,
        height: 90,
        correctLevel: QRCode.CorrectLevel.M
    });
});
</script>

</body>
</html>
