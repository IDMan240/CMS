<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$userName = $_SESSION["full_name"]
    ?? $_SESSION["username"]
    ?? "User";

$userRole = $_SESSION["role"]
    ?? "technician";

require_once __DIR__ . "/../config/database.php";
try {
 $pdo->exec("CREATE TABLE IF NOT EXISTS gauge_equipment_settings (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, equipment_name VARCHAR(255) NOT NULL DEFAULT '', equipment_range VARCHAR(255) NOT NULL DEFAULT '', equipment_serial VARCHAR(255) NOT NULL DEFAULT '', equipment_calibration_date DATE NULL, equipment_due_date DATE NULL, equipment_qty VARCHAR(100) NOT NULL DEFAULT '', equipment_certification_no VARCHAR(255) NOT NULL DEFAULT '', updated_by INT UNSIGNED NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $pdo->exec("INSERT INTO gauge_equipment_settings (id,equipment_name,equipment_range) VALUES (1,'DEADWEIGHT TESTER','20-10000 PSI') ON DUPLICATE KEY UPDATE id=id");
 $gaugeEquipment=$pdo->query("SELECT * FROM gauge_equipment_settings WHERE id=1")->fetch(PDO::FETCH_ASSOC) ?: [];
 $pdo->exec("CREATE TABLE IF NOT EXISTS nuprc_settings (id INT UNSIGNED PRIMARY KEY, nuprc VARCHAR(255) NOT NULL DEFAULT '', updated_by INT UNSIGNED NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $pdo->exec("INSERT INTO nuprc_settings (id,nuprc) VALUES (1,'') ON DUPLICATE KEY UPDATE id=id");
 $nuprcSetting=$pdo->query("SELECT nuprc FROM nuprc_settings WHERE id=1")->fetchColumn() ?: "";
} catch (Throwable $e) { $gaugeEquipment=[]; }

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>CMS | Gauge Calibration</title>

    <link rel="stylesheet"
          href="css/gauge.css">

</head>

<body>

<header class="topbar">

    <a href="dashboard.php"
       class="back-button">
        ←
    </a>

    <div class="brand">

        <div class="logo">
            CAL
        </div>

        <div>
            <strong>CMS</strong>

            <small>
                CALIBRATION MANAGEMENT SYSTEM
            </small>
        </div>

    </div>

    <div class="user-area">

        <div class="user-info">

            <strong>
                <?= htmlspecialchars($userName) ?>
            </strong>

            <small>
                <?= htmlspecialchars($userRole) ?>
            </small>

        </div>

        <a href="../auth/logout.php"
           class="logout">
            LOGOUT
        </a>

    </div>

</header>


<main class="page-container">

    <div class="page-heading">

        <span>
            CALIBRATION MODULE
        </span>

        <h1>
            Gauge Calibration
        </h1>

        <p>
            Enter instrument information and calibration
            readings below.
        </p>

    </div>


    <!-- =========================================
         01 INSTRUMENT INFORMATION
    ========================================== -->

    <section class="section-card">

        <div class="section-title">

            <div class="section-number">
                01
            </div>

            <div>
                <h2>Instrument Information</h2>

                <p>
                    Basic details of the gauge being calibrated
                </p>
            </div>

        </div>


        <div class="form-grid">

            <div class="field">
                <label>Client</label>
                <input id="client"
                       type="text"
                       placeholder="Client name">
            </div>

            <div class="field">
                <label>Certificate No.</label>
                <input id="certificateNo"
                       type="text"
                       placeholder="Certificate number">
            </div>

            <div class="field">
                <label>NUPRC</label>
                <input id="nuprc"
                       name="nuprc"
                       value="<?= htmlspecialchars($nuprcSetting) ?>"
                       readonly
                       type="text"
                       placeholder="NUPRC">
            </div>

            <div class="field">
                <label>Test Item</label>
                <input id="testItem"
                       type="text"
                       value="Pressure Gauge">
            </div>

            <div class="field">
                <label>Manufacturer</label>
                <input id="manufacturer"
                       type="text"
                       placeholder="Manufacturer">
            </div>

            <div class="field">
                <label>Serial No.</label>
                <input id="serialNo"
                       type="text"
                       placeholder="Serial number">
            </div>

            <div class="field">
                <label>Gauge Connection</label>
                <input id="gaugeConnection"
                       type="text"
                       placeholder='1/2" NPT'>
            </div>

            <div class="field">
                <label>Instrument Range</label>
                <input id="instrumentRange"
                       type="number"
                       step="any"
                       placeholder="250">
            </div>

            <div class="field">
                <label>Range Unit</label>

                <select id="rangeUnit">
                    <option value="BAR">BAR</option>
                    <option value="PSI">PSI</option>
                    <option value="kPa">kPa</option>
                    <option value="MPa">MPa</option>
                </select>

            </div>

            <div class="field">
                <label>Date of Calibration</label>

                <input id="calibrationDate"
                       type="date">
            </div>

            <div class="field">
                <label>Due Date</label>

                <input id="dueDate"
                       type="date">
            </div>

        </div>

    </section>


    <!-- =========================================
         02 CALIBRATION EQUIPMENT
    ========================================== -->

    <section class="section-card">

        <div class="section-title">

            <div class="section-number">
                02
            </div>

            <div>

                <h2>
                    Calibration Equipment Used
                </h2>

                <p>
                    Details of the instrument used for calibration
                </p>

            </div>

        </div>


        <div style="padding:9px 12px;margin-bottom:12px;background:#fff8d9;border-left:4px solid #d6b400;font-size:9px;color:#555"><strong>ADMINISTRATOR-CONTROLLED EQUIPMENT:</strong> These details are managed by the administrator and locked here.</div><div class="form-grid equipment-grid">

            <div class="field">
                <label>Name</label>

                <input id="equipmentName"
                       name="equipment_name"
                       type="text"
                       value="DEAD WEIGHT TESTER">
            </div>

            <div class="field">
                <label>Equipment Range</label>

                <input id="equipmentRange"
                       name="equipment_range"
                       type="text"
                       value="<?= htmlspecialchars($gaugeEquipment["equipment_range"] ?? "20-10000 PSI") ?>" readonly>
            </div>

            <div class="field">
                <label>Serial No.</label>

                <input id="equipmentSerial"
                       name="equipment_serial"
                       type="text"
                       value="<?= htmlspecialchars($gaugeEquipment["equipment_serial"] ?? "") ?>" readonly>
            </div>

            <div class="field">
                <label>Calibration Date</label>

                <input id="equipmentCalibrationDate"
                       name="equipment_calibration_date"
                       value="<?= htmlspecialchars($gaugeEquipment["equipment_calibration_date"] ?? "") ?>"
                       readonly
                       type="date">
            </div>

            <div class="field">
                <label>Next Due Date</label>

                <input id="equipmentDueDate"
                       name="equipment_due_date"
                       value="<?= htmlspecialchars($gaugeEquipment["equipment_due_date"] ?? "") ?>"
                       readonly
                       type="date">
            </div>

            <div class="field">
                <label>Quantity</label>

                <input id="equipmentQuantity"
                       name="equipment_qty"
                       type="text"
                       value="<?= htmlspecialchars($gaugeEquipment["equipment_qty"] ?? "") ?>" readonly>
            </div>

            <div class="field">
                <label>Certification No.</label>

                <input id="equipmentCertification"
                       name="equipment_certification_no"
                       type="text"
                       value="<?= htmlspecialchars($gaugeEquipment["equipment_certification_no"] ?? "") ?>" readonly>
            </div>

        </div>

    </section>


    <!-- =========================================
         03 RISING CALIBRATION
    ========================================== -->

    <section class="section-card">

        <div class="section-title">

            <div class="section-number">
                03
            </div>

            <div>

                <h2>
                    Calibration Test Data (Rising Readings)
                </h2>

                <p>
                    Enter the dead weight and gauge readings
                    (Increasing Pressure)
                </p>

            </div>

        </div>


        <div class="table-wrapper">

            <table class="calibration-table">

                <thead>

                    <tr>

                        <th>% RANGE</th>

                        <th>
                            ACTUAL VALUE
                            <span class="unit-label" data-range-unit>(BAR)</span>
                        </th>

                        <th>
                            DEAD WEIGHT READING
                            <span class="unit-label" data-range-unit>(BAR)</span>
                        </th>

                        <th>
                            GAUGE READING
                            <span class="unit-label" data-range-unit>(BAR)</span>
                        </th>

                        <th>
                            ERROR (%)
                        </th>

                    </tr>

                </thead>

                <tbody id="risingTable"></tbody>

            </table>

        </div>

    </section>


    <!-- =========================================
         04 FALLING CALIBRATION
    ========================================== -->

    <section class="section-card">

        <div class="section-title">

            <div class="section-number">
                04
            </div>

            <div>

                <h2>
                    Calibration Test Data (Falling Readings)
                </h2>

                <p>
                    Enter the dead weight and gauge readings
                    (Decreasing Pressure)
                </p>

            </div>

        </div>


        <div class="table-wrapper">

            <table class="calibration-table">

                <thead>

                    <tr>

                        <th>% RANGE</th>

                        <th>
                            ACTUAL VALUE
                            <span class="unit-label" data-range-unit>(BAR)</span>
                        </th>

                        <th>
                            DEAD WEIGHT READING
                            <span class="unit-label" data-range-unit>(BAR)</span>
                        </th>

                        <th>
                            GAUGE READING
                            <span class="unit-label" data-range-unit>(BAR)</span>
                        </th>

                        <th>
                            ERROR (%)
                        </th>

                    </tr>

                </thead>

                <tbody id="fallingTable"></tbody>

            </table>

        </div>

    </section>


    <!-- =========================================
         05 / 06 TESTED AND WITNESSED
    ========================================== -->

    <div class="signature-grid">


        <section class="section-card">

            <div class="section-title">

                <div class="section-number">
                    05
                </div>

                <div>
                    <h2>Tested By</h2>
                </div>

            </div>


            <div class="signature-fields">

                <div class="field">

                    <label>Name</label>

                    <input id="testedBy"
                           type="text"
                           value="<?= htmlspecialchars($userName) ?>">

                </div>

                <div class="field">

                    <label>Date</label>

                    <input id="testedDate"
                           type="date">

                </div>

                <div class="field full signature-upload-field">

                    <label>Upload Signature</label>

                    <input id="testedSignature" name="tested_signature" type="file" accept="image/png,image/jpeg,image/webp">
                    <small class="upload-help">PNG, JPG or WebP • Max 2 MB</small>

                    <div class="signature-preview-wrap" id="testedSignaturePreviewWrap" hidden>
                        <img id="testedSignaturePreview" class="signature-preview" alt="Tested By signature preview">
                        <button type="button" class="remove-signature" id="removeTestedSignature">Remove</button>
                    </div>

                </div>

            </div>

        </section>


        <section class="section-card">

            <div class="section-title">

                <div class="section-number">
                    06
                </div>

                <div>
                    <h2>Witnessed By</h2>
                </div>

            </div>


            <div class="signature-fields">

                <div class="field">

                    <label>Name</label>

                    <input id="witnessedBy"
                           type="text"
                           placeholder="Witness name">

                </div>

                <div class="field">

                    <label>Date</label>

                    <input id="witnessedDate"
                           type="date">

                </div>

                <div class="field full signature-upload-field">

                    <label>Upload Signature</label>

                    <input id="witnessedSignature" name="witnessed_signature" type="file" accept="image/png,image/jpeg,image/webp">
                    <small class="upload-help">PNG, JPG or WebP • Max 2 MB</small>

                    <div class="signature-preview-wrap" id="witnessedSignaturePreviewWrap" hidden>
                        <img id="witnessedSignaturePreview" class="signature-preview" alt="Witnessed By signature preview">
                        <button type="button" class="remove-signature" id="removeWitnessedSignature">Remove</button>
                    </div>

                </div>

            </div>

        </section>

    </div>



    <!-- =========================================
         07 COMPANY / CALIBRATION STAMP
    ========================================== -->

    <section class="section-card stamp-upload-card">

        <div class="section-title">

            <div class="section-number">
                07
            </div>

            <div>
                <h2>Company / Calibration Stamp</h2>
                <p class="section-subtitle">
                    Upload the official stamp to appear on the certificate.
                </p>
            </div>

        </div>

        <div class="stamp-upload-field">

            <label for="stampImage">
                Upload Stamp
            </label>

            <input
                id="stampImage"
                name="stamp_image"
                type="file"
                accept="image/png,image/jpeg,image/webp"
            >

            <small class="upload-help">
                PNG, JPG or WebP • Max 2 MB • PNG recommended for transparent stamps
            </small>

            <div
                class="stamp-preview-wrap"
                id="stampPreviewWrap"
                hidden
            >
                <img
                    id="stampPreview"
                    class="stamp-preview"
                    alt="Calibration stamp preview"
                >

                <button
                    type="button"
                    class="remove-signature"
                    id="removeStamp"
                >
                    Remove Stamp
                </button>
            </div>

        </div>

    </section>


    <!-- =========================================
         ACTIONS
    ========================================== -->

    <div class="actions">

        <button id="calculateButton"
                class="btn calculate">
            <span>▣</span>
            CALCULATE
        </button>


        <button id="clearButton"
                class="btn clear">
            <span>↻</span>
            CLEAR
        </button>


        <button id="saveButton"
                class="btn save">
            <span>▣</span>
            SAVE CERTIFICATE
        </button>

    </div>


    <div id="calculationStatus"
         class="status ready">
        READY
    </div>

</main>


<script src="js/gauge.js?v=20260820"></script>

</body>
</html>