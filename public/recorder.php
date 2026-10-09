<?php
session_start();
if (!isset($_SESSION["user_id"])) { header("Location: ../auth/login.php"); exit; }
$userName = $_SESSION["full_name"] ?? $_SESSION["username"] ?? "User";
$userRole = $_SESSION["role"] ?? "technician";

require_once __DIR__ . "/../config/database.php";

/* Load the current administrator-controlled Recorder equipment. */
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS recorder_equipment_settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        equipment_type ENUM('pressure','temperature') NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL DEFAULT '',
        equipment_range VARCHAR(255) NOT NULL DEFAULT '',
        calibration_date DATE NULL,
        due_date DATE NULL,
        certificate_no VARCHAR(255) NOT NULL DEFAULT '',
        serial_no VARCHAR(255) NOT NULL DEFAULT '',
        updated_by INT UNSIGNED NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    foreach ([
        'pressure' => ['DEADWEIGHT TESTER', '20-10000 PSI'],
        'temperature' => ['TEMPERATURE CALIBRATOR', '0-300C']
    ] as $type => $vals) {
        $s = $pdo->prepare("INSERT IGNORE INTO recorder_equipment_settings
            (equipment_type, name, equipment_range) VALUES (?, ?, ?)");
        $s->execute([$type, $vals[0], $vals[1]]);
    }

    $equipmentRows = $pdo->query(
        "SELECT * FROM recorder_equipment_settings ORDER BY equipment_type"
    )->fetchAll(PDO::FETCH_ASSOC);

    $equipmentSettings = ['pressure' => [], 'temperature' => []];
    foreach ($equipmentRows as $erow) {
        $equipmentSettings[$erow['equipment_type']] = $erow;
    }
} catch (Throwable $e) {
    $equipmentSettings = ['pressure' => [], 'temperature' => []];
}
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS nuprc_settings (id INT UNSIGNED PRIMARY KEY, nuprc VARCHAR(255) NOT NULL DEFAULT '', updated_by INT UNSIGNED NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("INSERT INTO nuprc_settings (id,nuprc) VALUES (1,'') ON DUPLICATE KEY UPDATE id=id");
    $nuprcSetting=(string)($pdo->query("SELECT nuprc FROM nuprc_settings WHERE id=1")->fetchColumn() ?: "");
} catch (Throwable $e) { $nuprcSetting=""; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMS | Recorder Calibration</title>
<link rel="stylesheet" href="css/recorder.css">
</head>
<body>
<header class="topbar">
<a href="dashboard.php" class="back-button">←</a>
<div class="brand"><div class="logo">CAL</div><div><strong>CMS</strong><small>CALIBRATION MANAGEMENT SYSTEM</small></div></div>
<div class="user-area"><div class="user-info"><strong><?= htmlspecialchars($userName) ?></strong><small><?= htmlspecialchars($userRole) ?></small></div><a href="../auth/logout.php" class="logout">LOGOUT</a></div>
</header>
<main class="page-container">
<div class="page-heading"><span>CALIBRATION MODULE</span><h1>Recorder Calibration</h1><p>Temperature / pressure recorder calibration certificate.</p></div>
<form id="recorderForm" enctype="multipart/form-data" novalidate>
<section class="section-card"><div class="section-title"><div class="section-number">01</div><div><h2>Instrument Information</h2><p>Details of the recorder being calibrated.</p></div></div>
<div class="form-grid">
<div class="field"><label>Client</label><input id="client" name="client" type="text" placeholder="Client name"></div>
<div class="field"><label>NUPRC / OGISP <span style="color:#c90000;font-size:8px">(ADMIN CONTROLLED)</span></label><input id="nuprc" name="nuprc" type="text" value="<?= htmlspecialchars($nuprcSetting) ?>" readonly><small style="display:block;margin-top:5px;color:#777;font-size:8px">Only an administrator can update this value.</small></div>
<div class="field"><label>Certificate No.</label><input id="certificateNo" name="certificate_no" type="text" placeholder="Certificate number"></div>
<div class="field"><label>Test Item</label><input id="testItem" name="test_item" type="text" value="TEMP./PRESSURE RECORDER"></div>
<div class="field"><label>Manufacturer</label><input id="manufacturer" name="manufacturer" type="text" placeholder="Manufacturer"></div>
<div class="field"><label>Serial No.</label><input id="serialNo" name="serial_no" type="text" placeholder="Serial number"></div>
<div class="field"><label>Instrument Pressure Range</label><input id="pressureRange" name="pressure_range" type="number" min="0" step="any" placeholder="250"></div>
<div class="field"><label>Pressure Range Unit</label><select id="pressureUnit" name="pressure_unit"><option value="BAR">BAR</option><option value="PSI">PSI</option><option value="kPa">kPa</option><option value="MPa">MPa</option></select></div>
<div class="field"><label>Temperature Range</label><input id="temperatureRange" name="temperature_range" type="number" min="0" step="any" placeholder="160"></div>
<div class="field"><label>Temperature Unit</label><select id="temperatureUnit" name="temperature_unit"><option value="F">F</option><option value="C">C</option></select></div>
<div class="field"><label>Date of Calibration</label><input id="calibrationDate" name="calibration_date" type="date"></div>
<div class="field"><label>Due Date</label><input id="dueDate" name="due_date" type="date"></div>
</div></section>
<section class="section-card"><div class="section-title"><div class="section-number">02</div><div><h2>Calibration Equipment Used</h2><p>Pressure and temperature equipment used for the test.</p></div></div>
<div style="padding:10px 12px;margin-bottom:15px;background:#fff8d9;border-left:4px solid #d6b400;font-size:9px;color:#555;"><strong>ADMINISTRATOR-CONTROLLED EQUIPMENT:</strong> These details are managed by the administrator and are locked on this page.</div><div class="equipment-grid">
<div class="equipment-panel"><h3>PRESSURE EQUIPMENT</h3>
<div class="field"><label>Name</label><input name="pressure_equipment_name" id="pressureEquipmentName" type="text" value="<?= htmlspecialchars($equipmentSettings["pressure"]["name"] ?? "DEADWEIGHT TESTER") ?>" readonly></div>
<div class="field"><label>Equipment Range</label><input name="pressure_equipment_range" id="pressureEquipmentRange" type="text" value="<?= htmlspecialchars($equipmentSettings["pressure"]["equipment_range"] ?? "20-10000 PSI") ?>" readonly></div>
<div class="field"><label>Calibration Date</label><input name="pressure_equipment_calibration_date" id="pressureEquipmentCalibrationDate" type="date" value="<?= htmlspecialchars($equipmentSettings["pressure"]["calibration_date"] ?? "") ?>" readonly></div>
<div class="field"><label>Next Due Date</label><input name="pressure_equipment_due_date" id="pressureEquipmentDueDate" type="date" value="<?= htmlspecialchars($equipmentSettings["pressure"]["due_date"] ?? "") ?>" readonly></div>
<div class="field"><label>Certificate No.</label><input name="pressure_equipment_certificate_no" id="pressureEquipmentCertificateNo" type="text" value="<?= htmlspecialchars($equipmentSettings["pressure"]["certificate_no"] ?? "") ?>" readonly></div>
<div class="field"><label>Serial No.</label><input name="pressure_equipment_serial" id="pressureEquipmentSerial" type="text" value="<?= htmlspecialchars($equipmentSettings["pressure"]["serial_no"] ?? "") ?>" readonly></div>
</div>
<div class="equipment-panel"><h3>TEMPERATURE EQUIPMENT</h3>
<div class="field"><label>Name</label><input name="temperature_equipment_name" id="temperatureEquipmentName" type="text" value="<?= htmlspecialchars($equipmentSettings["temperature"]["name"] ?? "TEMPERATURE CALIBRATOR") ?>" readonly></div>
<div class="field"><label>Equipment Range</label><input name="temperature_equipment_range" id="temperatureEquipmentRange" type="text" value="<?= htmlspecialchars($equipmentSettings["temperature"]["equipment_range"] ?? "0-300C") ?>" readonly></div>
<div class="field"><label>Calibration Date</label><input name="temperature_equipment_calibration_date" id="temperatureEquipmentCalibrationDate" type="date" value="<?= htmlspecialchars($equipmentSettings["temperature"]["calibration_date"] ?? "") ?>" readonly></div>
<div class="field"><label>Next Due Date</label><input name="temperature_equipment_due_date" id="temperatureEquipmentDueDate" type="date" value="<?= htmlspecialchars($equipmentSettings["temperature"]["due_date"] ?? "") ?>" readonly></div>
<div class="field"><label>Certificate No.</label><input name="temperature_equipment_certificate_no" id="temperatureEquipmentCertificateNo" type="text" value="<?= htmlspecialchars($equipmentSettings["temperature"]["certificate_no"] ?? "") ?>" readonly></div>
<div class="field"><label>Serial No.</label><input name="temperature_equipment_serial" id="temperatureEquipmentSerial" type="text" value="<?= htmlspecialchars($equipmentSettings["temperature"]["serial_no"] ?? "") ?>" readonly></div>
</div></div></section>
<section class="section-card"><div class="section-title"><div class="section-number">03</div><div><h2>Calibration Test Data (Rising Readings)</h2><p>Pressure and temperature recorder readings.</p></div></div><div class="table-wrapper"><table class="calibration-table"><thead><tr><th>%<br>RANGE</th><th>ACTUAL<br>VALUE<br><span data-pressure-unit>(BAR)</span></th><th>DEAD WEIGHT<br>READINGS<br><span data-pressure-unit>(BAR)</span></th><th>RECORDER<br>READINGS<br><span data-pressure-unit>(BAR)</span></th><th>TEMP<br>CAL. <span data-temperature-unit>(F)</span></th><th>RECORDER<br>READINGS<br><span data-temperature-unit>(F)</span></th></tr></thead><tbody id="risingTable"></tbody></table></div></section>
<section class="section-card"><div class="section-title"><div class="section-number">04</div><div><h2>Calibration Test Data (Falling Readings)</h2><p>Pressure and temperature recorder readings.</p></div></div><div class="table-wrapper"><table class="calibration-table"><thead><tr><th>%<br>RANGE</th><th>ACTUAL<br>VALUE<br><span data-pressure-unit>(BAR)</span></th><th>DEAD WEIGHT<br>READINGS<br><span data-pressure-unit>(BAR)</span></th><th>RECORDER<br>READINGS<br><span data-pressure-unit>(BAR)</span></th><th>TEMP<br>CAL. <span data-temperature-unit>(F)</span></th><th>RECORDER<br>READINGS<br><span data-temperature-unit>(F)</span></th></tr></thead><tbody id="fallingTable"></tbody></table></div></section>
<div class="signature-grid">
<section class="section-card"><div class="section-title"><div class="section-number">05</div><div><h2>Tested By</h2></div></div><div class="signature-fields"><div class="field"><label>Name</label><input id="testedBy" name="tested_by" type="text" value="<?= htmlspecialchars($userName) ?>"></div><div class="field"><label>Date</label><input id="testedDate" name="tested_date" type="date"></div><div class="field full signature-upload-field"><label for="testedSignature">Upload Signature</label><input id="testedSignature" name="tested_signature" type="file" accept="image/png,image/jpeg,image/webp"><small class="upload-help">PNG, JPG or WebP • Max 2 MB</small><div class="signature-preview-wrap" id="testedSignaturePreviewWrap" hidden><img id="testedSignaturePreview" class="signature-preview" alt="Tested By signature preview"><button type="button" class="remove-signature" id="removeTestedSignature">Remove</button></div></div></div></section>
<section class="section-card"><div class="section-title"><div class="section-number">06</div><div><h2>Witnessed By</h2></div></div><div class="signature-fields"><div class="field"><label>Name</label><input id="witnessedBy" name="witnessed_by" type="text" placeholder="Witness name"></div><div class="field"><label>Date</label><input id="witnessedDate" name="witnessed_date" type="date"></div><div class="field full signature-upload-field"><label for="witnessedSignature">Upload Signature</label><input id="witnessedSignature" name="witnessed_signature" type="file" accept="image/png,image/jpeg,image/webp"><small class="upload-help">PNG, JPG or WebP • Max 2 MB</small><div class="signature-preview-wrap" id="witnessedSignaturePreviewWrap" hidden><img id="witnessedSignaturePreview" class="signature-preview" alt="Witnessed By signature preview"><button type="button" class="remove-signature" id="removeWitnessedSignature">Remove</button></div></div></div></section>
</div>
<section class="section-card"><div class="section-title"><div class="section-number">07</div><div><h2>Company / Calibration Stamp</h2><p>Upload the official stamp to appear on the certificate.</p></div></div><div class="stamp-upload-field"><label for="stampImage">Upload Stamp</label><input id="stampImage" name="stamp_image" type="file" accept="image/png,image/jpeg,image/webp"><small class="upload-help">PNG, JPG or WebP • Max 2 MB • PNG recommended for transparent stamps</small><div class="stamp-preview-wrap" id="stampPreviewWrap" hidden><img id="stampPreview" class="stamp-preview" alt="Calibration stamp preview"><button type="button" class="remove-signature" id="removeStamp">Remove Stamp</button></div></div></section>
<div class="actions"><button type="button" id="calculateButton" class="btn calculate">▣ CALCULATE</button><button type="button" id="clearButton" class="btn clear">↺ CLEAR</button><button type="submit" id="saveButton" class="btn save">✓ SAVE RECORDER CERTIFICATE</button></div>
<div id="calculationStatus" class="status success">READY</div>
</form>
</main>
<script src="js/recorder.js?v=13.0.0"></script>
</body></html>
