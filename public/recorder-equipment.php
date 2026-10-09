<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["role"] ?? "technician") !== "admin") {
    http_response_code(403);
    exit("Access denied. Administrator access is required.");
}

require_once __DIR__ . "/../config/database.php";

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

$defaults = [
    'pressure' => ['DEADWEIGHT TESTER', '20-10000 PSI'],
    'temperature' => ['TEMPERATURE CALIBRATOR', '0-300C']
];

foreach ($defaults as $type => $vals) {
    $s = $pdo->prepare("INSERT IGNORE INTO recorder_equipment_settings
        (equipment_type, name, equipment_range) VALUES (?, ?, ?)");
    $s->execute([$type, $vals[0], $vals[1]]);
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $type = ($_POST["equipment_type"] ?? "") === "temperature" ? "temperature" : "pressure";
    $name = trim((string)($_POST["name"] ?? ""));
    $range = trim((string)($_POST["equipment_range"] ?? ""));
    $calDate = trim((string)($_POST["calibration_date"] ?? ""));
    $dueDate = trim((string)($_POST["due_date"] ?? ""));
    $cert = trim((string)($_POST["certificate_no"] ?? ""));
    $serial = trim((string)($_POST["serial_no"] ?? ""));

    if ($name === "" || $range === "") {
        $error = "Equipment name and equipment range are required.";
    } else {
        $s = $pdo->prepare("UPDATE recorder_equipment_settings
            SET name=?, equipment_range=?, calibration_date=?, due_date=?,
                certificate_no=?, serial_no=?, updated_by=?
            WHERE equipment_type=?");
        $s->execute([
            $name,
            $range,
            $calDate !== "" ? $calDate : null,
            $dueDate !== "" ? $dueDate : null,
            $cert,
            $serial,
            (int)$_SESSION["user_id"],
            $type
        ]);
        $message = strtoupper($type) . " EQUIPMENT UPDATED SUCCESSFULLY.";
    }
}

$rows = [];
foreach ($pdo->query("SELECT * FROM recorder_equipment_settings ORDER BY equipment_type") as $row) {
    $rows[$row["equipment_type"]] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMS | Recorder Equipment Settings</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#f4f4f4;color:#111}
.header{min-height:75px;display:flex;align-items:center;justify-content:space-between;padding:0 30px;background:#fff;border-bottom:4px solid #c90000}
.brand{display:flex;align-items:center;gap:12px}.brand-logo{width:42px;height:42px;border:3px solid #c90000;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:bold}
.brand strong{display:block;font-size:17px;letter-spacing:3px}.brand small{display:block;color:#777;font-size:7px;letter-spacing:1px}
.back{padding:11px 16px;background:#111;color:#fff;text-decoration:none;font-size:9px;font-weight:bold}
.page{max-width:1200px;margin:auto;padding:30px 20px 50px}.heading span{color:#c90000;font-size:8px;font-weight:bold;letter-spacing:2px}
.heading h1{margin:7px 0;font-size:28px}.heading p{color:#666;font-size:11px}
.notice{padding:13px 15px;margin:18px 0;border-left:4px solid #08752f;background:#e8f8ee;color:#08752f;font-size:10px}
.error{padding:13px 15px;margin:18px 0;border-left:4px solid #c90000;background:#ffeaea;color:#a00000;font-size:10px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}.panel{background:#fff;border:1px solid #ddd;border-top:5px solid #c90000;padding:22px;box-shadow:0 4px 15px rgba(0,0,0,.06)}
.panel h2{margin:0 0 18px;font-size:17px}.tag{display:inline-block;background:#111;color:#fff;padding:6px 9px;font-size:8px;font-weight:bold;letter-spacing:1px;margin-bottom:15px}
.field{margin-bottom:14px}.field label{display:block;margin-bottom:6px;font-size:9px;font-weight:bold;text-transform:uppercase}.field input{width:100%;padding:12px;border:1px solid #ccc;background:#fafafa;font:inherit}
.actions{display:flex;justify-content:flex-end;margin-top:8px}.btn{padding:12px 18px;border:2px solid #c90000;background:#c90000;color:#fff;font-size:9px;font-weight:bold;cursor:pointer}
.updated{margin-top:10px;color:#777;font-size:8px}
@media(max-width:700px){.grid{grid-template-columns:1fr}.header{padding:0 15px}.brand small,.brand strong{display:none}.page{padding:20px 12px 40px}}
</style>
</head>
<body>
<header class="header">
  <div class="brand"><div class="brand-logo">CAL</div><div><strong>CMS</strong><small>CALIBRATION MANAGEMENT SYSTEM</small></div></div>
  <a class="back" href="dashboard.php">← DASHBOARD</a>
</header>
<main class="page">
  <div class="heading">
    <span>ADMINISTRATION</span>
    <h1>Recorder Equipment Settings</h1>
    <p>Only administrators can update the equipment used on new Recorder certificates.</p>
  </div>

  <?php if ($message): ?><div class="notice">✓ <?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="grid">
    <?php foreach (['pressure'=>'PRESSURE EQUIPMENT','temperature'=>'TEMPERATURE EQUIPMENT'] as $type=>$title):
        $r = $rows[$type] ?? [];
    ?>
    <section class="panel">
      <span class="tag"><?= $title ?></span>
      <h2>Equipment Details</h2>
      <form method="post">
        <input type="hidden" name="equipment_type" value="<?= $type ?>">
        <div class="field"><label>Name</label><input name="name" value="<?= htmlspecialchars($r['name'] ?? '') ?>" required></div>
        <div class="field"><label>Equipment Range</label><input name="equipment_range" value="<?= htmlspecialchars($r['equipment_range'] ?? '') ?>" required></div>
        <div class="field"><label>Calibration Date</label><input type="date" name="calibration_date" value="<?= htmlspecialchars($r['calibration_date'] ?? '') ?>"></div>
        <div class="field"><label>Next Due Date</label><input type="date" name="due_date" value="<?= htmlspecialchars($r['due_date'] ?? '') ?>"></div>
        <div class="field"><label>Certificate No.</label><input name="certificate_no" value="<?= htmlspecialchars($r['certificate_no'] ?? '') ?>"></div>
        <div class="field"><label>Serial No.</label><input name="serial_no" value="<?= htmlspecialchars($r['serial_no'] ?? '') ?>"></div>
        <div class="actions"><button class="btn" type="submit">SAVE <?= $type === 'pressure' ? 'PRESSURE' : 'TEMPERATURE' ?> EQUIPMENT</button></div>
      </form>
      <?php if (!empty($r['updated_at'])): ?><div class="updated">Last updated: <?= htmlspecialchars($r['updated_at']) ?></div><?php endif; ?>
    </section>
    <?php endforeach; ?>
  </div>
</main>
</body>
</html>
