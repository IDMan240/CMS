<?php
/* =========================================================
   CMS — PUBLIC RECORDER CERTIFICATE VERIFICATION RENDERER
   Loaded by verify.php when the certificate number belongs
   to a Recorder calibration.
========================================================= */

function recorderVerifyH($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function recorderVerifyDate($date) {
    if (!$date) return '';
    $time = strtotime($date);
    return $time ? date('d/m/Y', $time) : recorderVerifyH($date);
}

function recorderVerifyNumber($value) {
    if ($value === null || $value === '') return '0';
    return rtrim(rtrim(number_format((float)$value, 2, '.', ''), '0'), '.');
}

function recorderVerifyImageDataUri($path) {
    $path = trim((string)$path);
    if ($path === '') return '';

    $path = str_replace('\\', '/', $path);
    $path = ltrim($path, '/');

    if (strpos($path, 'public/') === 0) {
        $path = substr($path, 7);
    }

    $candidates = [
        __DIR__ . '/' . $path,
        dirname(__DIR__) . '/' . $path,
        __DIR__ . '/uploads/' . $path,
        __DIR__ . '/uploads/' . basename($path),
        __DIR__ . '/assets/' . basename($path),
        dirname(__DIR__) . '/public/' . $path
    ];

    foreach ($candidates as $full) {
        if (!is_file($full) || !is_readable($full)) continue;

        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $full) ?: '';
                finfo_close($finfo);
            }
        }
        if (!$mime && function_exists('mime_content_type')) {
            $mime = @mime_content_type($full) ?: '';
        }
        if (!$mime) return '';

        $data = @file_get_contents($full);
        if ($data === false) return '';

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    return '';
}

function recorderVerifyImageTag($path, $class, $alt) {
    $src = recorderVerifyImageDataUri($path);
    if ($src === '') return '';
    return '<img src="' . recorderVerifyH($src) . '" class="' . recorderVerifyH($class) . '" alt="' . recorderVerifyH($alt) . '">';
}

function recorderVerifyStatus($dueDate) {
    if (!$dueDate) {
        return ['class' => 'status-valid', 'title' => 'STATUS UNAVAILABLE', 'message' => 'The expiration date is not available.'];
    }

    try {
        $today = new DateTimeImmutable(date('Y-m-d'));
        $expiration = new DateTimeImmutable(date('Y-m-d', strtotime($dueDate)));

        if ($today >= $expiration) {
            $days = (int)$expiration->diff($today)->days;
            return ['class' => 'status-expired', 'title' => 'EXPIRED', 'message' => 'This certificate expired ' . $days . ' day' . ($days === 1 ? '' : 's') . ' ago.'];
        }

        $oneMonthBefore = $expiration->modify('-1 month');
        if ($today >= $oneMonthBefore) {
            $days = (int)$today->diff($expiration)->days;
            return ['class' => 'status-soon', 'title' => 'EXPIRING SOON', 'message' => 'This certificate will expire in ' . $days . ' day' . ($days === 1 ? '' : 's') . '.'];
        }

        $days = (int)$today->diff($expiration)->days;
        return ['class' => 'status-valid', 'title' => 'VALID', 'message' => 'This certificate is valid for another ' . $days . ' day' . ($days === 1 ? '' : 's') . '.'];
    } catch (Throwable $e) {
        return ['class' => 'status-valid', 'title' => 'STATUS UNAVAILABLE', 'message' => 'Unable to determine certificate status.'];
    }
}

$recorderCertificate = $recorderCertificate ?? [];
$recorderReadings = $recorderReadings ?? [];
$risingReadings = [];
$fallingReadings = [];

foreach ($recorderReadings as $row) {
    if (($row['reading_type'] ?? '') === 'rising') {
        $risingReadings[] = $row;
    } elseif (($row['reading_type'] ?? '') === 'falling') {
        $fallingReadings[] = $row;
    }
}

$pressureUnit = (string)($recorderCertificate['pressure_unit'] ?? 'BAR');
$temperatureUnit = (string)($recorderCertificate['temperature_unit'] ?? 'F');
$testedSignature = (string)($recorderCertificate['tested_signature'] ?? '');
$witnessedSignature = (string)($recorderCertificate['witnessed_signature'] ?? '');
$stampImage = (string)($recorderCertificate['stamp_image'] ?? '');
$companyLogo = 'assets/company-logo.png';

$status = recorderVerifyStatus($recorderCertificate['due_date'] ?? null);
$verificationUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/public/verify.php'), '/')
    . '/verify.php?certificate_no=' . rawurlencode((string)($recorderCertificate['certificate_no'] ?? ''));

$qrPayload = json_encode([
    'certificate_type' => 'RECORDER',
    'certificate_no' => $recorderCertificate['certificate_no'] ?? '',
    'verification_url' => $verificationUrl
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificate Verification - <?= recorderVerifyH($recorderCertificate['certificate_no'] ?? '') ?></title>
<link rel="stylesheet" href="css/recorder-certificate.css?v=7.1.0">
<style>
.verify-recorder-page{min-height:100vh;background:#eeeeee;padding:10px 0 30px;font-family:Tahoma,Arial,sans-serif;color:#111}
.verify-recorder-toolbar{width:210mm;max-width:calc(100% - 20px);margin:0 auto 8px;display:flex;align-items:center;justify-content:space-between;gap:10px}
.verify-recorder-toolbar .left{display:flex;gap:8px;align-items:center}
.verify-recorder-toolbar a,.verify-recorder-toolbar button{border:0;background:#111;color:#fff;text-decoration:none;padding:9px 14px;font:bold 12px Tahoma,Arial,sans-serif;cursor:pointer}
.verify-recorder-status{width:210mm;max-width:calc(100% - 20px);margin:0 auto 8px;padding:10px 14px;background:#fff;border:1px solid #111;display:flex;gap:10px;align-items:center;justify-content:center;flex-wrap:wrap}
.verify-recorder-status strong{font-size:14px}.verify-recorder-status span{font-size:12px}
.verify-recorder-status.status-valid strong{color:#167c2b}.verify-recorder-status.status-soon strong{color:#b06a00}.verify-recorder-status.status-expired strong{color:#b00020}
.verify-recorder-certificate{position:relative;width:210mm;height:297mm;margin:0 auto;background:#fff;border:1px solid #111;overflow:hidden;padding:7mm 8mm 5mm;font-family:Tahoma,Arial,sans-serif}
.verify-recorder-certificate .verification-banner{margin-bottom:3mm;border:1.5px solid #111;padding:2mm;text-align:center;font-weight:bold;font-size:13px;background:#e9f3ff}
.verify-recorder-certificate .bottom-certification-area{height:27mm}
.verification-hidden{display:none!important}
.verify-recorder-loading{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.72)}
.verify-recorder-loading-dialog{width:min(420px,calc(100% - 30px));background:#fff;border-radius:12px;padding:28px 24px;text-align:center;box-shadow:0 18px 55px rgba(0,0,0,.35);font-family:Tahoma,Arial,sans-serif}
.verify-recorder-spinner{width:54px;height:54px;border:5px solid #ddd;border-top-color:#111;border-radius:50%;margin:0 auto 18px;animation:recorderVerifySpin 1s linear infinite}
.verify-recorder-loading-dialog h2{margin:0 0 8px;font-size:20px}.verify-recorder-loading-dialog p{margin:0;color:#555;font-size:13px;line-height:1.5}
@keyframes recorderVerifySpin{to{transform:rotate(360deg)}}
@media(max-width:800px){.verify-recorder-toolbar,.verify-recorder-status,.verify-recorder-certificate{width:210mm;max-width:none}.verify-recorder-page{overflow-x:auto}}
@media print{.verify-recorder-toolbar,.verify-recorder-status{display:none!important}.verify-recorder-page{padding:0;background:#fff}.verify-recorder-certificate{width:210mm;height:297mm;margin:0;border:0}.verify-recorder-certificate .verification-banner{display:none}}
</style>
</head>
<body>
<div class="verify-recorder-page">
    <div id="recorderVerificationOverlay" class="verify-recorder-loading">
        <div class="verify-recorder-loading-dialog">
            <div class="verify-recorder-spinner"></div>
            <h2>Verifying Certificate</h2>
            <p>Please wait while we verify the authenticity of this calibration certificate.</p>
        </div>
    </div>
    <div id="recorderVerificationToolbar" class="verify-recorder-toolbar verification-hidden">
        <div class="left">
            <a href="verify.php">VERIFY ANOTHER</a>
        </div>
        <button type="button" onclick="window.print()">PRINT / SAVE PDF</button>
    </div>

    <div id="recorderVerificationStatus" class="verify-recorder-status <?= recorderVerifyH($status['class']) ?> verification-hidden">
        <strong>✓ RECORDER CERTIFICATE VERIFIED — <?= recorderVerifyH($status['title']) ?></strong>
        <span><?= recorderVerifyH($status['message']) ?></span>
    </div>

    <main id="recorderVerificationCertificate" class="verify-recorder-certificate verification-hidden">
        <div class="verification-banner">PUBLIC VERIFICATION — RECORDER CALIBRATION CERTIFICATE</div>

        <section class="company-header">
            <div class="company-name">
                <?php $logoSrc = recorderVerifyImageDataUri($companyLogo); ?>
                <?php if ($logoSrc !== ''): ?>
                    <img src="<?= recorderVerifyH($logoSrc) ?>" alt="Company Logo">
                <?php endif; ?>
            </div>
        </section>

        <div class="blue-bar"></div>
        <div class="section-banner">CALIBRATION CERTIFICATE</div>

        <section class="two-column info-box certificate-info-box">
            <div class="info-column">
                <p><strong>CLIENT:</strong> <?= recorderVerifyH($recorderCertificate['client'] ?? '') ?></p>
            </div>
            <div class="info-column">
                <p><strong>NUPRC/OGISP:</strong> <?= recorderVerifyH($recorderCertificate['nuprc'] ?? '') ?></p>
                <p><strong>CERTIFICATE NO:</strong> <?= recorderVerifyH($recorderCertificate['certificate_no'] ?? '') ?></p>
            </div>
        </section>

        <div class="section-banner">CALIBRATION INSTRUMENT DATA</div>
        <section class="two-column info-box instrument-info-box">
            <div class="info-column">
                <p><strong>TEST ITEM:</strong> <?= recorderVerifyH($recorderCertificate['test_item'] ?? 'TEMP./PRESSURE RECORDER') ?></p>
                <p><strong>MANUFACTURER:</strong> <?= recorderVerifyH($recorderCertificate['manufacturer'] ?? '') ?></p>
                <p><strong>SERIAL NO:</strong> <?= recorderVerifyH($recorderCertificate['serial_no'] ?? '') ?></p>
                <p><strong>INSTRUMENT RANGE:</strong> 0 - <?= recorderVerifyNumber($recorderCertificate['pressure_range'] ?? 0) ?> <?= recorderVerifyH($pressureUnit) ?></p>
            </div>
            <div class="info-column">
                <p><strong>TEMPERATURE RANGE:</strong> 0–<?= recorderVerifyNumber($recorderCertificate['temperature_range'] ?? 0) ?> <?= recorderVerifyH($temperatureUnit) ?></p>
                <p><strong>DATE OF CALIBRATION:</strong> <?= recorderVerifyDate($recorderCertificate['calibration_date'] ?? '') ?></p>
                <p><strong>DUE DATE:</strong> <?= recorderVerifyDate($recorderCertificate['due_date'] ?? '') ?></p>
            </div>
        </section>

        <div class="section-banner">CALIBRATION EQUIPMENT USED</div>
        <section class="two-column info-box equipment-info-box">
            <div class="info-column">
                <p><strong>NAME:</strong> <?= recorderVerifyH($recorderCertificate['pressure_equipment_name'] ?? '') ?></p>
                <p><strong>EQUIPMENT RANGE:</strong> <?= recorderVerifyH($recorderCertificate['pressure_equipment_range'] ?? '') ?></p>
                <p><strong>CALIBRATION DATE:</strong> <?= recorderVerifyDate($recorderCertificate['pressure_equipment_calibration_date'] ?? '') ?></p>
                <p><strong>NEXT DUE DATE:</strong> <?= recorderVerifyDate($recorderCertificate['pressure_equipment_due_date'] ?? '') ?></p>
                <p><strong>CERTIFICATE NO:</strong> <?= recorderVerifyH($recorderCertificate['pressure_equipment_certificate_no'] ?? '') ?></p>
                <p><strong>SERIAL NO:</strong> <?= recorderVerifyH($recorderCertificate['pressure_equipment_serial'] ?? '') ?></p>
            </div>
            <div class="info-column">
                <p><strong>NAME:</strong> <?= recorderVerifyH($recorderCertificate['temperature_equipment_name'] ?? '') ?></p>
                <p><strong>EQUIPMENT RANGE:</strong> <?= recorderVerifyH($recorderCertificate['temperature_equipment_range'] ?? '') ?></p>
                <p><strong>CALIBRATION DATE:</strong> <?= recorderVerifyDate($recorderCertificate['temperature_equipment_calibration_date'] ?? '') ?></p>
                <p><strong>NEXT DUE DATE:</strong> <?= recorderVerifyDate($recorderCertificate['temperature_equipment_due_date'] ?? '') ?></p>
                <p><strong>CERTIFICATE NO:</strong> <?= recorderVerifyH($recorderCertificate['temperature_equipment_certificate_no'] ?? '') ?></p>
                <p><strong>SERIAL NO:</strong> <?= recorderVerifyH($recorderCertificate['temperature_equipment_serial'] ?? '') ?></p>
            </div>
        </section>

        <div class="section-banner">CALIBRATION TEST DATA (RISING READINGS)</div>
        <table class="calibration-table">
            <thead><tr>
                <th>%<br>RANGE</th>
                <th>ACTUAL<br>VALUE<br>(<?= recorderVerifyH($pressureUnit) ?>)</th>
                <th>DEAD WEIGHT<br>READINGS<br>(<?= recorderVerifyH($pressureUnit) ?>)</th>
                <th>RECORDER<br>READINGS<br>(<?= recorderVerifyH($pressureUnit) ?>)</th>
                <th>TEMP<br>CAL.<br>(<?= recorderVerifyH($temperatureUnit) ?>)</th>
                <th>RECORDER<br>READINGS<br>(<?= recorderVerifyH($temperatureUnit) ?>)</th>
            </tr></thead>
            <tbody>
            <?php foreach ($risingReadings as $row): ?>
                <tr>
                    <td><?= recorderVerifyNumber($row['percentage_range'] ?? 0) ?></td>
                    <td><?= recorderVerifyNumber($row['actual_value'] ?? 0) ?></td>
                    <td><?= recorderVerifyNumber($row['dead_weight_reading'] ?? 0) ?></td>
                    <td><?= recorderVerifyNumber($row['recorder_reading'] ?? 0) ?></td>
                    <td><?= recorderVerifyNumber($row['temperature_cal'] ?? 0) ?></td>
                    <td><?= recorderVerifyNumber($row['temperature_recorder'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="section-banner">CALIBRATION TEST DATA (FALLING READINGS)</div>
        <table class="calibration-table">
            <thead><tr>
                <th>%<br>RANGE</th>
                <th>ACTUAL<br>VALUE<br>(<?= recorderVerifyH($pressureUnit) ?>)</th>
                <th>DEAD WEIGHT<br>READINGS<br>(<?= recorderVerifyH($pressureUnit) ?>)</th>
                <th>RECORDER<br>READINGS<br>(<?= recorderVerifyH($pressureUnit) ?>)</th>
                <th>TEMP<br>CAL.<br>(<?= recorderVerifyH($temperatureUnit) ?>)</th>
                <th>RECORDER<br>READINGS<br>(<?= recorderVerifyH($temperatureUnit) ?>)</th>
            </tr></thead>
            <tbody>
            <?php foreach ($fallingReadings as $row): ?>
                <tr>
                    <td><?= recorderVerifyNumber($row['percentage_range'] ?? 0) ?></td>
                    <td><?= recorderVerifyNumber($row['actual_value'] ?? 0) ?></td>
                    <td><?= recorderVerifyNumber($row['dead_weight_reading'] ?? 0) ?></td>
                    <td><?= recorderVerifyNumber($row['recorder_reading'] ?? 0) ?></td>
                    <td><?= recorderVerifyNumber($row['temperature_cal'] ?? 0) ?></td>
                    <td><?= recorderVerifyNumber($row['temperature_recorder'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <section class="certificate-note">
            This is to certify that the above instrument has been tested and its test certified as per test equipment used.
            The readings are as shown above. We therefore recommend that the equipment can be used for its purpose within the calibrated range.
        </section>

        <section class="signature-section">
            <div class="signature-column">
                <h3>TESTED BY:</h3>
                <p><strong>Name:</strong> <?= recorderVerifyH($recorderCertificate['tested_by'] ?? '') ?></p>
                <p><strong>Date:</strong> <?= recorderVerifyDate($recorderCertificate['tested_date'] ?? '') ?></p>
                <p class="signature-row">
                    <strong>Signature:</strong>
                    <?= recorderVerifyImageTag($testedSignature, 'uploaded-signature', 'Tested By signature') ?>
                    <?php if (recorderVerifyImageDataUri($testedSignature) === ''): ?><span class="signature-line"></span><?php endif; ?>
                </p>
            </div>
            <div class="signature-column">
                <h3>WITNESSED BY:</h3>
                <p><strong>Name:</strong> <?= recorderVerifyH($recorderCertificate['witnessed_by'] ?? '') ?></p>
                <p><strong>Date:</strong> <?= recorderVerifyDate($recorderCertificate['witnessed_date'] ?? '') ?></p>
                <p class="signature-row">
                    <strong>Signature:</strong>
                    <?= recorderVerifyImageTag($witnessedSignature, 'uploaded-signature', 'Witnessed By signature') ?>
                    <?php if (recorderVerifyImageDataUri($witnessedSignature) === ''): ?><span class="signature-line"></span><?php endif; ?>
                </p>
            </div>
        </section>

        <section class="bottom-certification-area">
            <div class="stamp-section">
                <?php if (recorderVerifyImageDataUri($stampImage) !== ''): ?>
                    <?= recorderVerifyImageTag($stampImage, 'uploaded-stamp', 'Calibration stamp') ?>
                <?php endif; ?>
            </div>
            <div class="qr-section">
                <div id="recorderVerificationQR" class="qr-code"></div>
            </div>
        </section>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const qr = document.getElementById('recorderVerificationQR');
    if (qr && typeof QRCode !== 'undefined') {
        new QRCode(qr, {
            text: <?= json_encode($verificationUrl) ?>,
            width: 90,
            height: 90,
            correctLevel: QRCode.CorrectLevel.M
        });
    }

    const overlay = document.getElementById('recorderVerificationOverlay');
    const toolbar = document.getElementById('recorderVerificationToolbar');
    const status = document.getElementById('recorderVerificationStatus');
    const certificate = document.getElementById('recorderVerificationCertificate');

    setTimeout(function () {
        if (overlay) overlay.style.display = 'none';
        if (toolbar) toolbar.classList.remove('verification-hidden');
        if (status) status.classList.remove('verification-hidden');
        if (certificate) certificate.classList.remove('verification-hidden');
    }, 2000);
});
</script>
</body>
</html>
<?php exit; ?>
