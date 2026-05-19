<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>DSWD MIMAROPA Kiosk</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#eef3fb;color:#111827;min-height:100vh;overflow:hidden}
.wrap{min-height:100vh;padding:28px;display:flex}
.card{background:#fff;border-radius:28px;padding:30px;box-shadow:0 18px 45px #0f172a24;width:100%;display:flex;flex-direction:column;gap:22px;border:1px solid #dbe7f7}
h1{text-align:center;color:#003f9e;margin:0;font-size:clamp(24px,3vw,40px);letter-spacing:2px;line-height:1.15}
.note{text-align:center;color:#64748b;margin:0;font-size:clamp(14px,1.5vw,18px);font-weight:700}.status{text-align:center;font-weight:900;min-height:24px;color:#0f766e;font-size:15px}.err{color:#dc2626}
.tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px;flex:1;min-height:0}.assist{border:0;border-radius:24px;background:linear-gradient(180deg,#0757c7,#003f9e);color:white;box-shadow:0 8px 0 #002964,0 18px 30px #0f172a40;font-weight:1000;font-size:clamp(20px,2.4vw,34px);letter-spacing:1px;text-align:center;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;text-transform:uppercase;line-height:1.08;padding:24px 18px;min-height:190px;touch-action:manipulation;user-select:none}.assist .ico{width:82px;height:82px;border-radius:22px;background:#ffffff22;display:grid;place-items:center;font-size:44px;line-height:1;border:2px solid #ffffff40}.assist:hover{filter:brightness(1.08);transform:translateY(-2px)}.assist:active{transform:translateY(4px);box-shadow:0 4px 0 #002964,0 10px 20px #0f172a30}.assist:disabled{opacity:.55;cursor:not-allowed;transform:none}.cash{grid-column:1}.priority{grid-column:2;background:linear-gradient(180deg,#fb923c,#f97316);box-shadow:0 8px 0 #9a3412,0 18px 30px #0f172a40}.priority:active{box-shadow:0 4px 0 #9a3412,0 10px 20px #0f172a30}.modal{position:fixed;inset:0;background:#0008;display:none;align-items:center;justify-content:center;padding:20px;z-index:10}.pop{background:white;border-radius:28px;padding:30px;max-width:560px;width:96%;text-align:center;box-shadow:0 24px 80px #0008}.pop h2{margin:0;color:#166534;font-size:30px}.queue{font-size:82px;font-weight:1000;color:#003f9e;margin:12px 0}.pop p{font-size:18px;font-weight:800;color:#334155}.btn{border:0;border-radius:999px;padding:15px 26px;font-weight:1000;cursor:pointer;margin:7px;font-size:16px}.print{background:#16a34a;color:white}.again{background:#003f9e;color:white}.stubwrap{display:none}.stub{width:80mm;max-width:80mm;margin:0 auto;padding:8mm 6mm;background:#fff}.stub h2{text-align:center;margin:0 0 6px}.row{display:flex;justify-content:space-between;border-bottom:1px dotted #aaa;font-size:12px;padding:5px 0;gap:10px}.row b{text-align:right}@media(max-width:950px){body{overflow:auto}.wrap{padding:18px;min-height:auto}.card{padding:20px}.tiles{grid-template-columns:1fr 1fr}.assist{min-height:165px}.cash,.priority{grid-column:auto}}@media(max-width:620px){.wrap{padding:12px}.card{padding:16px;border-radius:22px}.tiles{grid-template-columns:1fr;gap:16px}.assist{min-height:135px;font-size:19px;padding:18px 14px}.assist .ico{width:60px;height:60px;font-size:34px;border-radius:18px}.queue{font-size:56px}}@media print{.wrap,.modal{display:none!important}.stubwrap{display:block}@page{size:80mm auto;margin:0}}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>DSWD MIMAROPA REGISTRATION KIOSK</h1>
    <p class="note">Tap the assistance type to instantly get a queue number.</p>
    <div id="status" class="status"></div>
    <div class="tiles">
      <button class="assist" onclick="generateQueue('Medical Assistance')"><span class="ico">🏥</span>Medical<br>Assistance</button>
      <button class="assist" onclick="generateQueue('Funeral Assistance')"><span class="ico">🕊️</span>Funeral<br>Assistance</button>
      <button class="assist" onclick="generateQueue('Educational Assistance')"><span class="ico">🎓</span>Educational<br>Assistance</button>
      <button class="assist" onclick="generateQueue('Transportation Assistance')"><span class="ico">🚌</span>Transportation<br>Assistance</button>
      <button class="assist" onclick="generateQueue('Material Assistance')"><span class="ico">📦</span>Material<br>Assistance</button>
      <button class="assist" onclick="generateQueue('Food Assistance')"><span class="ico">🍚</span>Food<br>Assistance</button>
      <button class="assist cash" onclick="generateQueue('Cash Relief Assistance')"><span class="ico">₱</span>Cash Relief<br>Assistance</button>
      <button class="assist priority" onclick="generateQueue('Priority Assistance')"><span class="ico">⭐</span>Priority<br>Assistance</button>
    </div>
  </div>
</div>
<div id="modal" class="modal">
  <div class="pop">
    <h2>Queue Generated</h2>
    <div id="queueNo" class="queue">-</div>
    <p id="summary"></p>
    <button class="btn print" onclick="printStub()">Print Stub</button>
    <button class="btn again" onclick="resetScreen()">New Queue</button>
  </div>
</div>
<div class="stubwrap">
  <div class="stub">
    <h2>DSWD QUEUE STUB</h2>
    <div class="row"><span>Queue Number</span><b id="stubQueue">-</b></div>
    <div class="row"><span>Code</span><b id="stubCode">-</b></div>
    <div class="row"><span>Assistance</span><b id="stubProgram">-</b></div>
    <div class="row"><span>Region</span><b>MIMAROPA</b></div>
    <div class="row"><span>Date</span><b id="stubDate">-</b></div>
  </div>
</div>
<script>
let lastQueue=null;
function msg(text,isError=false){status.className='status'+(isError?' err':'');status.textContent=text;}
function lock(disabled){document.querySelectorAll('.assist').forEach(btn=>btn.disabled=disabled);}
async function generateQueue(program){
  msg('Generating queue...');lock(true);
  try{
    const response=await fetch('../api/kiosk_generate_queue.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({program_type:program,source:'kiosk'})});
    const data=await response.json();
    if(!data.success) throw Error(data.message||'Unable to generate queue');
    lastQueue=data;queueNo.textContent=data.queue_number;summary.textContent=(data.program_type||program)+' - MIMAROPA';setStub(data);modal.style.display='flex';msg('Queue number ready.');
  }catch(error){msg(error.message,true);}finally{lock(false);}
}
function setStub(data){stubQueue.textContent=data.queue_number||'-';stubCode.textContent=data.beneficiary_code||'-';stubProgram.textContent=data.program_type||'-';stubDate.textContent=data.date_time||new Date().toLocaleString();}
function printStub(){if(lastQueue) setStub(lastQueue);setTimeout(()=>print(),80);}
function resetScreen(){lastQueue=null;modal.style.display='none';msg('');}
</script>
</body>
</html>
