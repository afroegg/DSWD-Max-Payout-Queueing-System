<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DSWD MIMAROPA Queue Kiosk</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:linear-gradient(135deg,#0b2e83,#168fcb);color:#111827;min-height:100vh}.kiosk{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:26px}.card{width:1050px;max-width:98%;background:#fff;border-radius:28px;padding:34px;box-shadow:0 22px 65px rgba(0,0,0,.28);text-align:center}.logo{width:96px;height:96px;border-radius:50%;margin:0 auto 14px;background:#eff6ff;color:#0b2e83;display:flex;align-items:center;justify-content:center}.logo .material-icons{font-size:56px}h1{margin:0;color:#00008b;font-size:38px}p{color:#475569;font-size:17px}.sub{margin:8px auto 24px;max-width:720px;line-height:1.45}.buttons{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:24px 0}.tap{border:0;border-radius:22px;min-height:138px;padding:18px;cursor:pointer;background:#f8fafc;border:2px solid #dbeafe;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;transition:.15s}.tap:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(15,23,42,.14)}.tap .material-icons{font-size:42px;color:#0b2e83}.tap b{font-size:22px;color:#111827}.tap span{font-size:13px;color:#64748b;font-weight:800}.prio{background:#fff7ed;border-color:#fed7aa}.prio .material-icons{color:#c2410c}.fourps{background:#ecfeff;border-color:#a5f3fc}.fourps .material-icons{color:#0e7490}.status{min-height:26px;font-weight:900;color:#0f766e}.status.err{color:#dc2626}.result{display:none;margin-top:22px;border:1px solid #bfdbfe;background:#eff6ff;border-radius:22px;padding:24px}.result h2{margin:0 0 8px;color:#166534;font-size:28px}.queue{font-size:60px;font-weight:1000;color:#00008b;letter-spacing:2px;margin:8px 0}.queue.prio-q{color:#c2410c}.meta{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;margin:12px 0}.chip{background:white;border:1px solid #cbd5e1;border-radius:999px;padding:8px 14px;font-weight:900;color:#334155}.actions{display:flex;justify-content:center;gap:12px;flex-wrap:wrap;margin-top:18px}.btn{border:0;border-radius:999px;padding:15px 24px;font-size:16px;font-weight:900;cursor:pointer;display:inline-flex;align-items:center;gap:7px}.print{background:#16a34a;color:white}.again{background:#0b2e83;color:white}.stub-wrap{display:none}.stub{width:80mm;max-width:80mm;margin:0 auto;padding:8mm 6mm;background:#fff;color:#111;font-family:Arial,sans-serif}.stub-head{text-align:center;border-bottom:1px dashed #111;padding-bottom:7px;margin-bottom:8px}.stub-head h2{margin:0;font-size:18px}.stub-head p{margin:3px 0 0;font-size:11px;color:#111}.stub-q{text-align:center;border:2px solid #111;border-radius:8px;padding:8px;margin:8px 0}.stub-q span{display:block;font-size:11px;text-transform:uppercase;font-weight:900}.stub-q strong{display:block;font-size:34px;margin-top:3px;letter-spacing:1px}.stub-row{display:flex;justify-content:space-between;gap:10px;font-size:12px;border-bottom:1px dotted #aaa;padding:5px 0}.stub-row b{text-align:right}.stub-note{font-size:11px;text-align:center;margin-top:9px;line-height:1.35}.stub-foot{text-align:center;border-top:1px dashed #111;margin-top:8px;padding-top:7px;font-size:10px}@media(max-width:850px){.buttons{grid-template-columns:1fr 1fr}.queue{font-size:46px}}@media(max-width:560px){.buttons{grid-template-columns:1fr}.card{padding:22px}h1{font-size:30px}}@media print{body{background:#fff!important}.kiosk{display:none!important}.stub-wrap{display:block!important}@page{size:80mm auto;margin:0}}
</style>
</head>
<body>
<main class="kiosk"><section class="card"><div class="logo"><span class="material-icons">touch_app</span></div><h1>DSWD MIMAROPA Queue Kiosk</h1><p class="sub">Tap the beneficiary category below. The system will instantly generate a queue number and prepare the same printable queue stub.</p><div class="buttons">
<button class="tap" onclick="generateQueue('regular')"><span class="material-icons">person</span><b>Regular</b><span>PAL queue</span></button>
<button class="tap prio" onclick="generateQueue('priority')"><span class="material-icons">priority_high</span><b>Priority</b><span>PRIO queue</span></button>
<button class="tap prio" onclick="generateQueue('pwd')"><span class="material-icons">accessible</span><b>PWD</b><span>Priority queue</span></button>
<button class="tap prio" onclick="generateQueue('senior')"><span class="material-icons">elderly</span><b>Senior Citizen</b><span>Priority queue</span></button>
<button class="tap prio" onclick="generateQueue('pregnant')"><span class="material-icons">pregnant_woman</span><b>Pregnant</b><span>Priority queue</span></button>
<button class="tap fourps" onclick="generateQueue('4ps')"><span class="material-icons">family_restroom</span><b>4Ps Beneficiary</b><span>PAL queue</span></button>
</div><div id="status" class="status"></div><div id="result" class="result"><h2>Queue Generated</h2><div id="queueNo" class="queue">-</div><div class="meta"><span id="categoryChip" class="chip">Category</span><span id="programChip" class="chip">AICS</span><span class="chip">MIMAROPA</span></div><div class="actions"><button class="btn print" onclick="printStub()"><span class="material-icons">print</span> Print Stub</button><button class="btn again" onclick="resetScreen()"><span class="material-icons">refresh</span> New Queue</button></div></div></section></main>
<div class="stub-wrap"><div class="stub" id="queueStub"><div class="stub-head"><h2>DSWD QUEUE STUB</h2><p>Max Payout Queueing and Monitoring System</p><p>MIMAROPA Beneficiary</p></div><div class="stub-q"><span>Queue Number</span><strong id="stubQueue">-</strong></div><div class="stub-row"><span>Beneficiary Code</span><b id="stubCode">-</b></div><div class="stub-row"><span>Category</span><b id="stubCategory">-</b></div><div class="stub-row"><span>Program</span><b id="stubProgram">-</b></div><div class="stub-row"><span>Region</span><b id="stubRegion">MIMAROPA</b></div><div class="stub-row"><span>Date / Time</span><b id="stubDate">-</b></div><div class="stub-note">Please keep this stub and wait for your queue number to be called. Present this when requested by staff.</div><div class="stub-foot">This is not proof of payout approval.</div></div></div>
<script>
let lastQueue=null;
async function generateQueue(category){
 const status=document.getElementById('status');
 status.className='status';status.textContent='Generating queue...';
 try{
  const res=await fetch('../api/kiosk_generate_queue.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({category:category,program_type:'AICS'})});
  const data=await res.json();
  if(!data.success)throw new Error(data.message||'Unable to generate queue.');
  lastQueue=data;
  document.getElementById('queueNo').textContent=data.queue_number;
  document.getElementById('queueNo').classList.toggle('prio-q',data.queue_type==='priority');
  document.getElementById('categoryChip').textContent=data.category;
  document.getElementById('programChip').textContent=data.program_type;
  setStub(data);
  document.getElementById('result').style.display='block';
  status.textContent='Queue number ready. You may print the stub.';
 }catch(e){status.className='status err';status.textContent=e.message;}
}
function setStub(data){
 document.getElementById('stubQueue').textContent=data.queue_number||'-';
 document.getElementById('stubCode').textContent=data.beneficiary_code||'-';
 document.getElementById('stubCategory').textContent=data.category||'-';
 document.getElementById('stubProgram').textContent=data.program_type||'AICS';
 document.getElementById('stubRegion').textContent=data.region||'MIMAROPA';
 document.getElementById('stubDate').textContent=data.date_time||new Date().toLocaleString();
}
function printStub(){if(lastQueue)setStub(lastQueue);setTimeout(()=>window.print(),80)}
function resetScreen(){lastQueue=null;document.getElementById('result').style.display='none';document.getElementById('status').textContent='';}
</script>
</body>
</html>
