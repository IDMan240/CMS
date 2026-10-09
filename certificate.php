<?php

/* =========================================================
   CMS — GAUGE CALIBRATION CERTIFICATE
   FILE 13B — CERTIFICATE
========================================================= */

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/config/database.php";

/* =========================================================
   GET CALIBRATION ID
========================================================= */

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id <= 0) {
    die("Invalid calibration certificate ID.");
}

/* =========================================================
   GET CALIBRATION
========================================================= */

try {

    $stmt = $pdo->prepare("
        SELECT *
        FROM gauge_calibrations
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ":id" => $id
    ]);

    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$certificate) {
        die("Calibration certificate not found.");
    }

} catch (Throwable $e) {

    die(
        "Unable to load calibration certificate.<br><br>" .
        htmlspecialchars($e->getMessage())
    );
}

/* =========================================================
   GET READINGS
========================================================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM gauge_calibration_readings
    WHERE calibration_id = :calibration_id
    ORDER BY
        reading_type ASC,
        id ASC
");

$stmt->execute([
    ":calibration_id" => $id
]);

$readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$risingReadings = [];
$fallingReadings = [];

foreach ($readings as $reading) {

    if ($reading["reading_type"] === "rising") {
        $risingReadings[] = $reading;
    }

    if ($reading["reading_type"] === "falling") {
        $fallingReadings[] = $reading;
    }
}

/* =========================================================
   HELPERS
========================================================= */

function value($data, $key, $default = "")
{
    return isset($data[$key]) && $data[$key] !== null
        ? htmlspecialchars((string)$data[$key])
        : $default;
}

function dateFormat($date)
{
    if (!$date) {
        return "";
    }

    $time = strtotime($date);

    if (!$time) {
        return htmlspecialchars($date);
    }

    return date("d/m/Y", $time);
}

function numberFormat($number)
{
    if ($number === null || $number === "") {
        return "0";
    }

    return rtrim(
        rtrim(
            number_format((float)$number, 2, ".", ""),
            "0"
        ),
        "."
    );
}

$rangeUnit = value(
    $certificate,
    "range_unit",
    "BAR"
);

$certificateNo = value(
    $certificate,
    "certificate_no"
);

$client = value(
    $certificate,
    "client"
);

$testItem = value(
    $certificate,
    "test_item",
    "Pressure Gauge"
);

$manufacturer = value(
    $certificate,
    "manufacturer"
);

$serialNo = value(
    $certificate,
    "serial_no"
);

$gaugeConnection = value(
    $certificate,
    "gauge_connection"
);

$instrumentRange = numberFormat(
    $certificate["instrument_range"] ?? 0
);

$equipmentName = value(
    $certificate,
    "equipment_name"
);

$equipmentSerial = value(
    $certificate,
    "equipment_serial"
);

$equipmentRange = value(
    $certificate,
    "equipment_range"
);

$equipmentQty = value(
    $certificate,
    "equipment_qty"
);

$equipmentCertNo = value(
    $certificate,
    "equipment_certification_no"
);

$testedBy = value(
    $certificate,
    "tested_by"
);

$witnessedBy = value(
    $certificate,
    "witnessed_by"
);

$testedSignature = $certificate["tested_signature"] ?? "";
$witnessedSignature = $certificate["witnessed_signature"] ?? "";

/*
 * Uploaded signatures are physically stored inside public/uploads/signatures/.
 * The database stores the relative path "uploads/signatures/<file>".
 * certificate.php lives one level above public/, so the browser URL must
 * include the public/ directory.
 */
function signatureUrl($path)
{
    $path = trim((string)$path);

    if ($path === "") {
        return "";
    }

    $path = str_replace("\\", "/", $path);
    $path = ltrim($path, "/");

    // Keep compatibility if an older record already contains public/.
    if (strpos($path, "public/") !== 0) {
        $path = "public/" . $path;
    }

    return htmlspecialchars($path, ENT_QUOTES, "UTF-8");
}

$stampImage = $certificate["stamp_image"] ?? "";

function uploadImageUrl($path)
{
    $path = trim((string)$path);

    if ($path === "") {
        return "";
    }

    $path = str_replace("\\", "/", $path);
    $path = ltrim($path, "/");

    if (strpos($path, "public/") !== 0) {
        $path = "public/" . $path;
    }

    return htmlspecialchars(
        $path,
        ENT_QUOTES,
        "UTF-8"
    );
}

$qrData = json_encode([
    "certificate_no" => $certificate["certificate_no"] ?? "",
    "client"         => $certificate["client"] ?? "",
    "test_item"      => $certificate["test_item"] ?? "",
    "serial_no"      => $certificate["serial_no"] ?? "",
    "range"          => ($certificate["instrument_range"] ?? "") .
                        " " .
                        ($certificate["range_unit"] ?? ""),
    "calibration"    => $certificate["calibration_date"] ?? "",
    "due_date"       => $certificate["due_date"] ?? ""
]);

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
        Calibration Certificate -
        <?= $certificateNo ?>
    </title>

    <link
        rel="stylesheet"
        href="css/certificates.css"
    >

</head>

<body>

<div class="print-actions">

    <button onclick="window.print()">
        PRINT / SAVE PDF
    </button>

    <a href="gauge.php">
        BACK TO GAUGE
    </a>

</div>


<main class="certificate">


    <!-- =====================================================
         COMPANY HEADER
    ====================================================== -->

    <section class="company-header">

        <div class="company-name">
            <img
                src="public/assets/company-logo.png"
                alt="Top Inlet Control Ventures Ltd."
            >
        </div>

        <div class="company-contact">

            <strong>Address:</strong>
            1234 Industrial Area, Port Harcourt, Rivers State, Nigeria.

            <span class="contact-divider">|</span>

            <strong>Phone:</strong>
            +234 800 123 4567, +234 900 987 6543

        </div>

    </section>


    <!-- =====================================================
         CALIBRATION CERTIFICATE
    ====================================================== -->

    <div class="section-banner">
        CALIBRATION CERTIFICATE
    </div>


    <section class="two-column info-box certificate-info-box">

        <div class="info-column">

            <p>
                <strong>CLIENT:</strong>
                <?= $client ?>
            </p>

        </div>


        <div class="info-column">

            <p>
                <strong>NUPRC:</strong>
                <?= value($certificate, "nuprc") ?>
            </p>

            <p>
                <strong>CERTIFICATE NO:</strong>
                <?= $certificateNo ?>
            </p>

        </div>

    </section>


    <!-- =====================================================
         INSTRUMENT DATA
    ====================================================== -->

    <div class="section-banner">
        CALIBRATION INSTRUMENT DATA
    </div>


    <section class="two-column info-box">

        <div class="info-column">

            <p>
                <strong>TEST ITEM:</strong>
                <?= $testItem ?>
            </p>

            <p>
                <strong>MANUFACTURER:</strong>
                <?= $manufacturer ?>
            </p>

            <p>
                <strong>SERIAL NO.:</strong>
                <?= $serialNo ?>
            </p>

            <p>
                <strong>GAUGE CONNECTION:</strong>
                <?= $gaugeConnection ?>
            </p>

        </div>


        <div class="info-column">

            <p>
                <strong>INSTRUMENT RANGE:</strong>
                <?= $instrumentRange ?>
                <?= $rangeUnit ?>
            </p>

            <p>
                <strong>DATE OF CALIBRATION:</strong>
                <?= dateFormat($certificate["calibration_date"] ?? "") ?>
            </p>

            <p>
                <strong>DUE DATE:</strong>
                <?= dateFormat($certificate["due_date"] ?? "") ?>
            </p>

        </div>

    </section>


    <!-- =====================================================
         EQUIPMENT
    ====================================================== -->

    <div class="section-banner">
        CALIBRATION EQUIPMENT USED
    </div>


    <section class="two-column info-box">

        <div class="info-column">

            <p>
                <strong>NAME:</strong>
                <?= $equipmentName ?>
            </p>

            <p>
                <strong>EQUIPMENT RANGE:</strong>
                <?= $equipmentRange ?>
            </p>

            <p>
                <strong>CALIBRATION DATE:</strong>
                <?= dateFormat(
                    $certificate["equipment_calibration_date"] ?? ""
                ) ?>
            </p>

            <p>
                <strong>NEXT DUE DATE:</strong>
                <?= dateFormat(
                    $certificate["equipment_due_date"] ?? ""
                ) ?>
            </p>

        </div>


        <div class="info-column">

            <p>
                <strong>SERIAL NO.:</strong>
                <?= $equipmentSerial ?>
            </p>

            <p>
                <strong>QTY:</strong>
                <?= $equipmentQty ?>
            </p>

            <p>
                <strong>CERTIFICATION NO.:</strong>
                <?= $equipmentCertNo ?>
            </p>

        </div>

    </section>


    <!-- =====================================================
         RISING
    ====================================================== -->

    <div class="section-banner">
        CALIBRATION TEST DATA (RISING READINGS)
    </div>


    <table class="calibration-table">

        <thead>

        <tr>

            <th>% RANGE</th>

            <th>
                ACTUAL VALUE<br>
                (<?= $rangeUnit ?>)
            </th>

            <th>
                DEAD WEIGHT<br>
                READING (<?= $rangeUnit ?>)
            </th>

            <th>
                GAUGE READING<br>
                (<?= $rangeUnit ?>)
            </th>

            <th>
                ERROR %
            </th>

        </tr>

        </thead>


        <tbody>

        <?php foreach ($risingReadings as $row): ?>

            <tr>

                <td>
                    <?= numberFormat($row["percentage_range"]) ?>%
                </td>

                <td>
                    <?= numberFormat($row["actual_value"]) ?>
                </td>

                <td>
                    <?= numberFormat($row["dead_weight_reading"]) ?>
                </td>

                <td>
                    <?= numberFormat($row["gauge_reading"]) ?>
                </td>

                <td>
                    <?= numberFormat($row["error_percent"]) ?>%
                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>


    <!-- =====================================================
         FALLING
    ====================================================== -->

    <div class="section-banner">
        CALIBRATION TEST DATA (FALLING READINGS)
    </div>


    <table class="calibration-table">

        <thead>

        <tr>

            <th>% RANGE</th>

            <th>
                ACTUAL VALUE<br>
                (<?= $rangeUnit ?>)
            </th>

            <th>
                DEAD WEIGHT<br>
                READING (<?= $rangeUnit ?>)
            </th>

            <th>
                GAUGE READING<br>
                (<?= $rangeUnit ?>)
            </th>

            <th>
                ERROR %
            </th>

        </tr>

        </thead>


        <tbody>

        <?php foreach ($fallingReadings as $row): ?>

            <tr>

                <td>
                    <?= numberFormat($row["percentage_range"]) ?>%
                </td>

                <td>
                    <?= numberFormat($row["actual_value"]) ?>
                </td>

                <td>
                    <?= numberFormat($row["dead_weight_reading"]) ?>
                </td>

                <td>
                    <?= numberFormat($row["gauge_reading"]) ?>
                </td>

                <td>
                    <?= numberFormat($row["error_percent"]) ?>%
                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>


    <!-- =====================================================
         CERTIFICATION STATEMENT
    ====================================================== -->

    <section class="certificate-note">

        This certifies that the above

        <strong>
            <?= $testItem ?>
        </strong>

        has been tested and that its test is certified
        as per test equipment used.

        The readings obtained are as shown above.

        We therefore recommend that the equipment can be
        used for its purpose within the calibrated range.

        <strong>CAUTION:</strong>

        All necessary instruction from the manual should
        be applied during usage and at the usage.

    </section>


    <!-- =====================================================
         TESTED / WITNESSED
    ====================================================== -->

    <section class="signature-section">

        <div class="signature-column">

            <h3>
                TESTED BY:
            </h3>

            <p>
                <strong>Name:</strong>
                <?= $testedBy ?>
            </p>

            <p>
                <strong>Date:</strong>
                <?= dateFormat(
                    $certificate["signature_date"] ?? ""
                ) ?>
            </p>

            <p class="signature-row">
                <strong>Signature:</strong>
                <?php if ($testedSignature !== ""): ?>
                    <img src="<?= signatureUrl($testedSignature) ?>" class="uploaded-signature" alt="Tested By signature">
                <?php else: ?>
                    <span class="signature-line"></span>
                <?php endif; ?>
            </p>

        </div>


        <div class="signature-column">

            <h3>
                WITNESSED BY:
            </h3>

            <p>
                <strong>Name:</strong>
                <?= $witnessedBy ?>
            </p>

            <p>
                <strong>Date:</strong>
                <?= dateFormat(
                    $certificate["signature_date"] ?? ""
                ) ?>
            </p>

            <p class="signature-row">
                <strong>Signature:</strong>
                <?php if ($witnessedSignature !== ""): ?>
                    <img src="<?= signatureUrl($witnessedSignature) ?>" class="uploaded-signature" alt="Witnessed By signature">
                <?php else: ?>
                    <span class="signature-line"></span>
                <?php endif; ?>
            </p>

        </div>

    </section>


    <!-- =====================================================
         STAMP + QR CODE
    ====================================================== -->

    <div class="bottom-certification-area">

        <?php if ($stampImage !== ""): ?>

            <div class="stamp-section">

                <img
                    src="<?= uploadImageUrl($stampImage) ?>"
                    class="uploaded-stamp"
                    alt="Calibration stamp"
                >

            </div>

        <?php endif; ?>

        <div class="qr-section">

            <div
                id="certificateQRCode"
                class="qr-code"
            ></div>

        </div>

    </div>


</main>


<script
    src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js">
</script>


<script>

const verificationData =
<?= $qrData ?>;


document.addEventListener(
    "DOMContentLoaded",
    function () {

        const qrContainer =
            document.getElementById(
                "certificateQRCode"
            );

        if (
            !qrContainer ||
            typeof QRCode === "undefined"
        ) {
            return;
        }

        qrContainer.innerHTML = "";

        new QRCode(
            qrContainer,
            {
                text:
                    JSON.stringify(
                        verificationData
                    ),

                width: 90,

                height: 90,

                correctLevel:
                    QRCode.CorrectLevel.M
            }
        );

    }
);

</script>

</body>
</html>