
const $=id=>document.getElementById(id);
const RISING=[0,25,50,75,100], FALLING=[100,75,50,25,0];
function num(v){if(v===null||v===undefined||String(v).trim()==='')return null;const n=Number(v);return Number.isFinite(n)?n:null}
function fmt(v){return v===null||!Number.isFinite(v)?'—':Number(v).toFixed(2)}
function pressureActual(range,p){const r=num(range);return r===null?null:r*p/100}
function temperatureValue(tempRange,index){const max=num(tempRange);if(max===null||max<=0)return null;return max/5*(index+1)}
function buildRow(p,type,index){
 const tr=document.createElement('tr');tr.dataset.type=type;tr.dataset.index=index;
 const c1=document.createElement('td');c1.textContent=p+'%';
 const actual=pressureActual($('pressureRange').value,p);const c2=document.createElement('td');c2.className='actual-pressure';c2.textContent=actual===null?'—':fmt(actual);
 const c3=document.createElement('td');const dead=document.createElement('input');dead.type='number';dead.step='any';dead.className='dead-input';dead.value=actual===null?'':fmt(actual);c3.appendChild(dead);
 const c4=document.createElement('td');const recorder=document.createElement('input');recorder.type='number';recorder.step='any';recorder.className='pressure-recorder-input';recorder.value=actual===null?'':fmt(actual);c4.appendChild(recorder);
 const temp=temperatureValue($('temperatureRange').value,index);const c5=document.createElement('td');c5.className='temperature-cal';c5.textContent=temp===null?'—':fmt(temp);
 const c6=document.createElement('td');const tempRec=document.createElement('input');tempRec.type='number';tempRec.step='any';tempRec.className='temperature-recorder-input';tempRec.value=temp===null?'':fmt(temp);c6.appendChild(tempRec);
 [c1,c2,c3,c4,c5,c6].forEach(c=>tr.appendChild(c));return tr;
}
function buildTable(id,points,type){const t=$(id);if(!t)return;t.innerHTML='';points.forEach((p,i)=>t.appendChild(buildRow(p,type,i)));}
function rebuild(){buildTable('risingTable',RISING,'rising');buildTable('fallingTable',FALLING,'falling');}
function updateCalculations(){
 const pr=num($('pressureRange').value), tr=num($('temperatureRange').value);
 document.querySelectorAll('.calibration-table tbody tr').forEach(row=>{
  const p=num(row.children[0].textContent.replace('%',''));const index=num(row.dataset.index);
  const a=pressureActual(pr,p);row.querySelector('.actual-pressure').textContent=a===null?'—':fmt(a);
  const temp=temperatureValue(tr,index);row.querySelector('.temperature-cal').textContent=temp===null?'—':fmt(temp);
  const d=row.querySelector('.dead-input'),r=row.querySelector('.pressure-recorder-input'),t=row.querySelector('.temperature-recorder-input');
  if(d&&!d.dataset.edited)d.value=a===null?'':fmt(a);if(r&&!r.dataset.edited)r.value=a===null?'':fmt(a);if(t&&!t.dataset.edited)t.value=temp===null?'':fmt(temp);
 });
}
function updateUnits(){const pu=$('pressureUnit').value,tu=$('temperatureUnit').value;document.querySelectorAll('[data-pressure-unit]').forEach(e=>e.textContent='('+pu+')');document.querySelectorAll('[data-temperature-unit]').forEach(e=>e.textContent='('+tu+')');}
function setupTracking(){document.querySelectorAll('.calibration-table input').forEach(i=>i.addEventListener('input',()=>i.dataset.edited='true'));}
function status(m,type='success'){const s=$('calculationStatus');s.textContent=m;s.className='status '+type;}
function calculate(){const pr=num($('pressureRange').value),tr=num($('temperatureRange').value);if(pr===null||pr<=0){status('Please enter a valid pressure range.','warning');return false}if(tr===null||tr<=0){status('Please enter a valid temperature range.','warning');return false}updateCalculations();status('READY — Temperature points are divided into 5 equal calibration steps.','success');return true}
function setDates(){const today=new Date();const iso=new Date(today.getTime()-today.getTimezoneOffset()*60000).toISOString().slice(0,10);['calibrationDate','testedDate','witnessedDate','pressureEquipmentCalibrationDate','temperatureEquipmentCalibrationDate'].forEach(id=>{const e=$(id);if(e&&!e.value)e.value=iso});}

function setupImageUpload(inputId, previewId, wrapId, removeId, label){
 const input=$(inputId), preview=$(previewId), wrap=$(wrapId), remove=$(removeId);
 if(!input||!preview||!wrap)return;
 input.addEventListener('change',()=>{
  const file=input.files?.[0];
  if(!file){wrap.hidden=true;preview.removeAttribute('src');return;}
  if(!/^image\/(png|jpeg|webp)$/i.test(file.type)){status(label+' must be PNG, JPG or WebP.','warning');input.value='';wrap.hidden=true;preview.removeAttribute('src');return;}
  if(file.size>2*1024*1024){status(label+' image must not exceed 2 MB.','warning');input.value='';wrap.hidden=true;preview.removeAttribute('src');return;}
  const reader=new FileReader();
  reader.onload=e=>{preview.src=e.target.result;wrap.hidden=false;};
  reader.readAsDataURL(file);
 });
 remove?.addEventListener('click',()=>{input.value='';preview.removeAttribute('src');wrap.hidden=true;});
}
function setupSignatureUpload(inputId,previewId,wrapId,removeId){setupImageUpload(inputId,previewId,wrapId,removeId,'Signature');}
function setupStampUpload(){setupImageUpload('stampImage','stampPreview','stampPreviewWrap','removeStamp','Stamp');}

function clearForm(){if(!confirm('Clear all recorder calibration data?'))return;document.querySelectorAll('#recorderForm input').forEach(i=>{if(i.type==='file')i.value='';else i.value='';delete i.dataset.edited});['testedSignaturePreviewWrap','witnessedSignaturePreviewWrap','stampPreviewWrap'].forEach(id=>{const w=$(id);if(w)w.hidden=true});['testedSignaturePreview','witnessedSignaturePreview','stampPreview'].forEach(id=>{const img=$(id);if(img)img.removeAttribute('src')});$('testItem').value='TEMP./PRESSURE RECORDER';$('pressureUnit').value='BAR';$('temperatureUnit').value='F';setDates();rebuild();updateUnits();status('READY','success');}
function showDuplicate(no){const modal=document.createElement('div');modal.className='cms-modal';modal.innerHTML='<div class="cms-modal-box"><div class="cms-modal-icon">⚠</div><h2>CERTIFICATE NUMBER ALREADY USED</h2><p>Certificate No: <strong>'+String(no).replace(/[&<>"']/g,s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[s]))+'</strong></p><p>This certificate number is already registered. The recorder certificate was <b>NOT saved</b>.</p><button type="button">OK, CHANGE CERTIFICATE NO</button></div>';document.body.appendChild(modal);modal.querySelector('button').onclick=()=>{$('certificateNo').focus();modal.remove()};}
async function save(e){e.preventDefault();if(!calculate())return;const required=[['certificateNo','Please enter a certificate number.'],['client','Please enter the client name.'],['pressureRange','Please enter a valid pressure range.'],['temperatureRange','Please enter a valid temperature range.']];for(const [id,msg] of required){const el=$(id);if(!el||!String(el.value).trim()){status(msg,'warning');el?.focus();return}}
 const btn=$('saveButton');if(btn.dataset.saving==='true')return;btn.dataset.saving='true';btn.disabled=true;status('Saving recorder certificate...','success');
 const fd=new FormData($('recorderForm'));
 document.querySelectorAll('#risingTable tr,#fallingTable tr').forEach((row)=>{const type=row.parentElement.id==='risingTable'?'rising':'falling';const p=num(row.children[0].textContent.replace('%',''));const actual=num(row.querySelector('.actual-pressure').textContent);const dead=num(row.querySelector('.dead-input').value);const rec=num(row.querySelector('.pressure-recorder-input').value);const temp=num(row.querySelector('.temperature-cal').textContent);const tempRec=num(row.querySelector('.temperature-recorder-input').value);fd.append(type+'['+p+'][percentage]',p??0);fd.append(type+'['+p+'][actual]',actual??0);fd.append(type+'['+p+'][dead_weight]',dead??0);fd.append(type+'['+p+'][recorder]',rec??0);fd.append(type+'['+p+'][temperature_cal]',temp??0);fd.append(type+'['+p+'][temperature_recorder]',tempRec??0);});
 try{const res=await fetch('save-recorder-calibration.php',{method:'POST',body:fd});const text=await res.text();if(res.redirected){window.location.href=res.url;return}let data;try{data=JSON.parse(text)}catch(_){data=null}if(data?.status==='duplicate'){showDuplicate(data.certificate_no);status('Certificate number already used.','warning');return}if(data?.status==='success'&&data.url){window.location.href=data.url;return}status(data?.message||'Unable to save recorder certificate.','error');console.error(text)}catch(err){status('Unable to connect to the server.','error');console.error(err)}finally{btn.dataset.saving='false';btn.disabled=false}}
function init(){setDates();rebuild();updateUnits();setupTracking();setupSignatureUpload('testedSignature','testedSignaturePreview','testedSignaturePreviewWrap','removeTestedSignature');setupSignatureUpload('witnessedSignature','witnessedSignaturePreview','witnessedSignaturePreviewWrap','removeWitnessedSignature');setupStampUpload();$('pressureRange').addEventListener('input',updateCalculations);$('temperatureRange').addEventListener('input',updateCalculations);$('pressureUnit').addEventListener('change',updateUnits);$('temperatureUnit').addEventListener('change',updateUnits);$('calculateButton').addEventListener('click',calculate);$('clearButton').addEventListener('click',clearForm);$('recorderForm').addEventListener('submit',save)}document.addEventListener('DOMContentLoaded',init);
