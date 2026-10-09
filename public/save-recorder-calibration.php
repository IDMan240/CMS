<?php
session_start();
if(!isset($_SESSION['user_id'])){http_response_code(401);echo json_encode(['status'=>'error','message'=>'Session expired.']);exit;}
require_once __DIR__.'/../config/database.php';
header('Content-Type: application/json; charset=utf-8');
function postv($n){return isset($_POST[$n])&&!is_array($_POST[$n])?trim((string)$_POST[$n]):'';}
function numv($v){return is_numeric($v)?(float)$v:0;}
function ensureTables(PDO $pdo){
$pdo->exec("CREATE TABLE IF NOT EXISTS recorder_calibrations (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 certificate_no VARCHAR(100) NOT NULL UNIQUE,
 client VARCHAR(255) NOT NULL, nuprc VARCHAR(255) NULL, test_item VARCHAR(255) NULL,
 manufacturer VARCHAR(255) NULL, serial_no VARCHAR(255) NULL,
 pressure_range DECIMAL(15,4) NOT NULL, pressure_unit VARCHAR(20) NOT NULL DEFAULT 'BAR',
 temperature_range DECIMAL(15,4) NOT NULL, temperature_unit VARCHAR(20) NOT NULL DEFAULT 'F',
 calibration_date DATE NULL, due_date DATE NULL,
 pressure_equipment_name VARCHAR(255) NULL, pressure_equipment_range VARCHAR(255) NULL, pressure_equipment_calibration_date DATE NULL, pressure_equipment_due_date DATE NULL, pressure_equipment_certificate_no VARCHAR(255) NULL, pressure_equipment_serial VARCHAR(255) NULL,
 temperature_equipment_name VARCHAR(255) NULL, temperature_equipment_range VARCHAR(255) NULL, temperature_equipment_calibration_date DATE NULL, temperature_equipment_due_date DATE NULL, temperature_equipment_certificate_no VARCHAR(255) NULL, temperature_equipment_serial VARCHAR(255) NULL,
 tested_by VARCHAR(255) NULL, witnessed_by VARCHAR(255) NULL, tested_date DATE NULL, witnessed_date DATE NULL,
 tested_signature VARCHAR(255) NULL, witnessed_signature VARCHAR(255) NULL, stamp_image VARCHAR(255) NULL,
 created_by INT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$pdo->exec("CREATE TABLE IF NOT EXISTS recorder_calibration_readings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, calibration_id INT UNSIGNED NOT NULL, reading_type ENUM('rising','falling') NOT NULL,
 percentage_range DECIMAL(7,2) NOT NULL, actual_value DECIMAL(15,4) NOT NULL DEFAULT 0, dead_weight_reading DECIMAL(15,4) NOT NULL DEFAULT 0,
 recorder_reading DECIMAL(15,4) NOT NULL DEFAULT 0, temperature_cal DECIMAL(15,4) NOT NULL DEFAULT 0, temperature_recorder DECIMAL(15,4) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(calibration_id), CONSTRAINT fk_recorder_readings_calibration FOREIGN KEY(calibration_id) REFERENCES recorder_calibrations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function ensureEquipmentSettings(PDO $pdo){
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
foreach(['pressure'=>['DEADWEIGHT TESTER','20-10000 PSI'],'temperature'=>['TEMPERATURE CALIBRATOR','0-300C']] as $type=>$v){
$s=$pdo->prepare("INSERT IGNORE INTO recorder_equipment_settings(equipment_type,name,equipment_range) VALUES(?,?,?)");
$s->execute([$type,$v[0],$v[1]]);
}
}
function getEquipmentSettings(PDO $pdo){
ensureEquipmentSettings($pdo);
$out=['pressure'=>[],'temperature'=>[]];
foreach($pdo->query("SELECT * FROM recorder_equipment_settings") as $r){$out[$r['equipment_type']]=$r;}
return $out;
}
function upload($field,$prefix){if(!isset($_FILES[$field])||$_FILES[$field]['error']===UPLOAD_ERR_NO_FILE)return null;$f=$_FILES[$field];if($f['error']!==UPLOAD_ERR_OK||$f['size']<=0||$f['size']>2*1024*1024)throw new RuntimeException('Invalid '.$prefix.' upload.');$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$allow=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];if(!isset($allow[$mime])||@getimagesize($f['tmp_name'])===false)throw new RuntimeException($prefix.' must be a valid image.');$dir=__DIR__.'/uploads/'.$prefix.'s';if(!is_dir($dir)&&!mkdir($dir,0755,true))throw new RuntimeException('Unable to create upload directory.');$name=$prefix.'_'.bin2hex(random_bytes(16)).'.'.$allow[$mime];if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name))throw new RuntimeException('Unable to store '.$prefix.'.');return 'uploads/'.$prefix.'s/'.$name;}
function duplicateAcross(PDO $pdo,$certificateNo){$q=$pdo->prepare('SELECT id FROM gauge_calibrations WHERE certificate_no=:c LIMIT 1');$q->execute([':c'=>$certificateNo]);if($q->fetchColumn()!==false)return true;$q=$pdo->prepare('SELECT id FROM recorder_calibrations WHERE certificate_no=:c LIMIT 1');$q->execute([':c'=>$certificateNo]);return $q->fetchColumn()!==false;}
$cert=postv('certificate_no');
if($cert===''){echo json_encode(['status'=>'error','message'=>'Certificate number is required.']);exit;}
try{ensureTables($pdo);$equipment=getEquipmentSettings($pdo);$pdo->exec("CREATE TABLE IF NOT EXISTS nuprc_settings (id INT UNSIGNED PRIMARY KEY, nuprc VARCHAR(255) NOT NULL DEFAULT '', updated_by INT UNSIGNED NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");$pdo->exec("INSERT INTO nuprc_settings (id,nuprc) VALUES (1,'') ON DUPLICATE KEY UPDATE id=id");$adminNuprc=(string)($pdo->query("SELECT nuprc FROM nuprc_settings WHERE id=1")->fetchColumn() ?: "");if(duplicateAcross($pdo,$cert)){echo json_encode(['status'=>'duplicate','certificate_no'=>$cert]);exit;}
$pr=postv('pressure_range');$tr=postv('temperature_range');if(!is_numeric($pr)||numv($pr)<=0||!is_numeric($tr)||numv($tr)<=0)throw new RuntimeException('Pressure and temperature ranges must be greater than zero.');
$testedSig=upload('tested_signature','signature');$witnessSig=upload('witnessed_signature','signature');$stamp=upload('stamp_image','stamp');
$pdo->beginTransaction();
$s=$pdo->prepare("INSERT INTO recorder_calibrations(certificate_no,client,nuprc,test_item,manufacturer,serial_no,pressure_range,pressure_unit,temperature_range,temperature_unit,calibration_date,due_date,pressure_equipment_name,pressure_equipment_range,pressure_equipment_calibration_date,pressure_equipment_due_date,pressure_equipment_certificate_no,pressure_equipment_serial,temperature_equipment_name,temperature_equipment_range,temperature_equipment_calibration_date,temperature_equipment_due_date,temperature_equipment_certificate_no,temperature_equipment_serial,tested_by,witnessed_by,tested_date,witnessed_date,tested_signature,witnessed_signature,stamp_image,created_by) VALUES(:certificate_no,:client,:nuprc,:test_item,:manufacturer,:serial_no,:pressure_range,:pressure_unit,:temperature_range,:temperature_unit,:calibration_date,:due_date,:pen,:per,:pcd,:pdd,:pcn,:ps,:ten,:ter,:tcd,:tdd,:tcn,:ts,:tested_by,:witnessed_by,:tested_date,:witnessed_date,:tested_signature,:witnessed_signature,:stamp_image,:created_by)");
$s->execute([':certificate_no'=>$cert,':client'=>postv('client'),':nuprc'=>$adminNuprc,':test_item'=>postv('test_item'),':manufacturer'=>postv('manufacturer'),':serial_no'=>postv('serial_no'),':pressure_range'=>numv($pr),':pressure_unit'=>postv('pressure_unit')?:'BAR',':temperature_range'=>numv($tr),':temperature_unit'=>postv('temperature_unit')?:'F',':calibration_date'=>postv('calibration_date')?:null,':due_date'=>postv('due_date')?:null,':pen'=>$equipment['pressure']['name']??'',':per'=>$equipment['pressure']['equipment_range']??'',':pcd'=>$equipment['pressure']['calibration_date']??null,':pdd'=>$equipment['pressure']['due_date']??null,':pcn'=>$equipment['pressure']['certificate_no']??'',':ps'=>$equipment['pressure']['serial_no']??'',':ten'=>$equipment['temperature']['name']??'',':ter'=>$equipment['temperature']['equipment_range']??'',':tcd'=>$equipment['temperature']['calibration_date']??null,':tdd'=>$equipment['temperature']['due_date']??null,':tcn'=>$equipment['temperature']['certificate_no']??'',':ts'=>$equipment['temperature']['serial_no']??'',':tested_by'=>postv('tested_by'),':witnessed_by'=>postv('witnessed_by'),':tested_date'=>postv('tested_date')?:null,':witnessed_date'=>postv('witnessed_date')?:null,':tested_signature'=>$testedSig,':witnessed_signature'=>$witnessSig,':stamp_image'=>$stamp,':created_by'=>$_SESSION['user_id']]);
$id=(int)$pdo->lastInsertId();$rising=$_POST['rising']??[];$falling=$_POST['falling']??[];$rs=$pdo->prepare('INSERT INTO recorder_calibration_readings(calibration_id,reading_type,percentage_range,actual_value,dead_weight_reading,recorder_reading,temperature_cal,temperature_recorder) VALUES(:id,:type,:p,:a,:d,:r,:tc,:tr)');
foreach(['rising'=>$rising,'falling'=>$falling] as $type=>$rows){foreach($rows as $row){if(!is_array($row))continue;$rs->execute([':id'=>$id,':type'=>$type,':p'=>numv($row['percentage']??0),':a'=>numv($row['actual']??0),':d'=>numv($row['dead_weight']??0),':r'=>numv($row['recorder']??0),':tc'=>numv($row['temperature_cal']??0),':tr'=>numv($row['temperature_recorder']??0)]);}}
$pdo->commit();echo json_encode(['status'=>'success','certificate_id'=>$id,'certificate_no'=>$cert,'url'=>'recorder-certificate.php?certificate_no='.rawurlencode($cert)]);
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();foreach([$testedSig??null,$witnessSig??null,$stamp??null] as $p){if($p&&is_file(__DIR__.'/'.$p))@unlink(__DIR__.'/'.$p);}if($e instanceof PDOException&&$e->getCode()==='23000'){echo json_encode(['status'=>'duplicate','certificate_no'=>$cert]);}else{http_response_code(500);echo json_encode(['status'=>'error','message'=>$e->getMessage()]);}}
