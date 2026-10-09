<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
require_once __DIR__.'/../config/database.php';
$id=(int)($_GET['id']??0);if($id<=0)die('Invalid valve certificate ID.');
try{$st=$pdo->prepare('SELECT * FROM valve_certificates WHERE id=:id LIMIT 1');$st->execute([':id'=>$id]);$c=$st->fetch(PDO::FETCH_ASSOC);if(!$c)die('Valve certificate not found.');$p=$pdo->prepare('SELECT * FROM valve_pressure_log WHERE valve_id=:id ORDER BY sort_order,id');$p->execute([':id'=>$id]);$logs=$p->fetchAll(PDO::FETCH_ASSOC);$ph=$pdo->prepare('SELECT * FROM valve_physical_checks WHERE valve_id=:id ORDER BY sort_order,id');$ph->execute([':id'=>$id]);$checks=$ph->fetchAll(PDO::FETCH_ASSOC);}catch(Throwable $e){die('Unable to load valve certificate.');}
function v($x){return htmlspecialchars((string)($x??''),ENT_QUOTES,'UTF-8');}function fd($x){if(!$x)return ''; $t=strtotime($x);return $t?date('d/m/Y',$t):v($x);}function imageDataUri($path){
    $path=trim((string)$path);
    if($path==='') return '';
    $path=str_replace('\\','/',$path);
    $path=ltrim($path,'/');
    if(strpos($path,'public/')===0) $path=substr($path,7);
    $candidates=[__DIR__.'/'.$path, __DIR__.'/uploads/'.basename($path)];
    foreach($candidates as $full){
        if(is_file($full) && is_readable($full)){
            $mime='';
            if(function_exists('finfo_open')){ $fi=finfo_open(FILEINFO_MIME_TYPE); if($fi){$mime=finfo_file($fi,$full);finfo_close($fi);} }
            if(!$mime) $mime=@mime_content_type($full) ?: '';
            $data=@file_get_contents($full);
            if($mime && $data!==false) return 'data:'.$mime.';base64,'.base64_encode($data);
        }
    }
    return '';
}
$logo='assets/company-logo.png';$testSig=imageDataUri($c['tested_signature']);$witSig=imageDataUri($c['witnessed_signature']);$stamp=imageDataUri($c['stamp_image']);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CMS | Valve Test Certificate <?=v($c['certificate_no'])?></title><link rel="stylesheet" href="css/valve-certificate.css"></head><body>
<div class="actions"><button class="print" onclick="window.print()">PRINT / SAVE PDF</button><a class="back" href="valve.php">NEW VALVE</a></div>
<main class="certificate"><div class="header"><div class="logo-area"><img src="<?=v($logo)?>" alt="Company Logo"></div><div class="contact">B119, EDEWOR ESTATE, BY ENERHEN MOTEL, EFFURUN-WARRI, DELTA STATE. &nbsp;&nbsp; PHONE: 09035750494</div></div>
<div class="yellow">VALVE TEST CERTIFICATE</div>
<div class="info"><div class="cell"><p><b>CLIENT:</b> <?=v($c['client'])?></p><p><b>TEST LOCATION:</b> <?=v($c['test_location'])?></p></div><div class="cell"><p><b>NUPRC/OGISP:</b> <?=v($c['nuprc'])?></p><p><b>CERTIFICATE NO.</b> <?=v($c['certificate_no'])?></p></div></div>
<div class="yellow">TEST INSTRUMENT DATA</div><div class="info"><div class="cell"><p><b>TESTED ITEM:</b> <?=v($c['tested_item'])?></p><p>VALVE CLASS: <?=v($c['valve_class'])?></p></div><div class="cell"><p>&nbsp;</p><p>S/N: <?=v($c['serial_no'])?></p></div></div>
<div style="border:1.8px solid #111;border-top:0;padding:4px 6px;font-weight:800">MAXIMUM ALLOWABLE TEST PRESSURE (MATP): <?=v($c['matp'])?></div>
<div class="green">PRESSURE LOG:</div>
<table class="table"><thead><tr><th>DESCRIPTION</th><th>CAVITY /<br>BODY TEST</th><th>SEAT TEST</th><th>REMARKS</th></tr></thead><tbody><?php foreach($logs as $r): ?><tr><td><?=v($r['description'])?></td><td><?=v($r['cavity_body_test'])?></td><td><?=v($r['seat_test'])?></td><td><?=v($r['remarks'])?></td></tr><?php endforeach; ?></tbody></table>
<div class="physical">PHYSICAL CHECK</div><table class="table"><tbody><?php foreach($checks as $r): ?><tr><td style="width:35%;text-align:center;font-weight:400"><?=v($r['check_left'])?></td><td style="width:15%;text-align:center"><?=v($r['result_left'])?></td><td style="width:35%;text-align:center;font-weight:400"><?=v($r['check_right'])?></td><td style="width:15%;text-align:center"><?=v($r['result_right'])?></td></tr><?php endforeach; ?></tbody></table>
<div class="sign">
  <div class="signbox">
    <b>TESTED BY: <?=v($c['tested_by'])?></b><br><br>
    NAME: <?=v($c['tested_name'])?><br><br>
    SIGN:
    <?php if($testSig): ?><img class="sigimg" src="<?=$testSig?>" alt="Tested signature"><?php else: ?><span class="signature-line"></span><?php endif; ?>
  </div>
  <div class="signbox">
    <b>WITNESS BY:</b> <?=v($c['witnessed_by'])?><br><br>
    SIGNATURE:
    <?php if($witSig): ?><img class="sigimg" src="<?=$witSig?>" alt="Witness signature"><?php else: ?><span class="signature-line"></span><?php endif; ?>
  </div>
</div>
<div class="bottom-certification-area">
  <?php if($stamp): ?><div class="stamp-section"><img class="uploaded-stamp" src="<?=$stamp?>" alt="Certificate stamp"></div><?php endif; ?>
  <div class="qr-section"><div id="certificateQRCode" class="qr-code" aria-label="Certificate QR code"></div></div>
</div>
</main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
  const qr=document.getElementById('certificateQRCode');
  if(!qr || typeof QRCode==='undefined') return;
  const url=new URL('verify.php', window.location.href);
  url.searchParams.set('certificate_no', <?=json_encode($c['certificate_no'])?>);
  qr.innerHTML='';
  new QRCode(qr,{text:url.href,width:90,height:90,correctLevel:QRCode.CorrectLevel.M});
});
</script>
</body></html>
