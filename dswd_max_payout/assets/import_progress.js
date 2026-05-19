(function(){
function isVerifier(){return !!document.getElementById('importResult')&&!!document.getElementById('importFile')}
function esc(v){return String(v==null?'':v).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;')}
function autoHide(delay){var box=document.getElementById('importResult');if(!box)return;clearTimeout(window.__importResultTimer);window.__importResultTimer=setTimeout(function(){box.style.display='none';box.innerHTML=''},delay||6500)}
function progressBox(title,percent,detail){percent=Math.max(0,Math.min(100,Math.round(percent||0)));return '<div style="font-weight:900;margin-bottom:8px;">'+esc(title)+' <span style="float:right">'+percent+'%</span></div><div style="height:12px;background:#e5e7eb;border-radius:999px;overflow:hidden;border:1px solid #cbd5e1"><div style="height:100%;width:'+percent+'%;background:#168fcb;border-radius:999px;transition:width .25s ease"></div></div><div style="margin-top:8px;font-size:12px;color:#475569">'+esc(detail||'Please wait...')+'</div>'}
function resultBox(data){
 var inserted=parseInt(data.inserted||0,10),dupes=parseInt(data.duplicates||0,10),failed=parseInt(data.failed||0,10);
 var total=inserted+dupes+failed;
 var html='<div style="font-weight:900;margin-bottom:8px;">Import finished <span style="float:right">100%</span></div>';
 html+='<div style="height:12px;background:#e5e7eb;border-radius:999px;overflow:hidden;border:1px solid #cbd5e1"><div style="height:100%;width:100%;background:#16a34a;border-radius:999px"></div></div>';
 html+='<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:10px;text-align:center">';
 html+='<div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:8px"><b>'+inserted+'</b><br><span style="font-size:11px">Imported</span></div>';
 html+='<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:8px"><b>'+dupes+'</b><br><span style="font-size:11px">Duplicate Rows</span></div>';
 html+='<div style="background:#fee2e2;border:1px solid #fecaca;border-radius:8px;padding:8px"><b>'+failed+'</b><br><span style="font-size:11px">Skipped/Failed</span></div>';
 html+='<div style="background:#dbeafe;border:1px solid #bfdbfe;border-radius:8px;padding:8px"><b>'+total+'</b><br><span style="font-size:11px">Total Checked</span></div>';
 html+='</div>';
 if(data.errors&&data.errors.length){html+='<div style="margin-top:10px;font-size:12px;color:#991b1b"><b>Skipped row notes:</b><br>'+data.errors.map(esc).join('<br>')+'</div>'}
 html+='<div style="margin-top:8px;font-size:11px;color:#64748b;text-align:right">This message will disappear automatically.</div>';
 return html;
}
function show(html,ok,noAutoHide){var box=document.getElementById('importResult');if(!box)return;clearTimeout(window.__importResultTimer);box.style.display='block';box.style.borderColor=ok===false?'#fecaca':'#86efac';box.style.background=ok===false?'#fef2f2':'#f0fdf4';box.style.color=ok===false?'#991b1b':'#166534';box.innerHTML=html;if(!noAutoHide)autoHide(6500)}
function install(){
 if(!isVerifier()||typeof uploadImportFile!=='function'||uploadImportFile._percentReady)return;
 uploadImportFile=async function(){
  if(!selectedImportFile){show('No file selected.',false);return}
  var lower=String(selectedImportFile.name||'').toLowerCase();
  if(!lower.endsWith('.csv')&&!lower.endsWith('.xlsx')){show('Please upload CSV or Excel .xlsx file only. Legacy .xls is not supported.',false);return}
  var percent=5,done=false;
  show(progressBox('Preparing import',percent,'Checking selected file...'),true,true);
  var timer=setInterval(function(){if(done)return;if(percent<88)percent+=3;else if(percent<96)percent+=1;show(progressBox('Importing beneficiaries',percent,'Uploading, checking duplicate rows, and skipping invalid rows...'),true,true)},350);
  try{
   var fd=new FormData();fd.append('import_file',selectedImportFile);
   var res=await fetch('../api/import_beneficiaries.php',{method:'POST',body:fd});
   var data=await res.json();
   done=true;clearInterval(timer);
   if(!data.success){show(esc(data.message||'Import failed.'),false);return}
   show(resultBox(data),true);
   selectedImportFile=null;
   var f=document.getElementById('importFile');if(f)f.value='';
   try{currentPage=1;loadVerifierData()}catch(e){}
  }catch(e){done=true;clearInterval(timer);console.error(e);show('Import failed. Please check the file format and try again.',false)}
 };
 uploadImportFile._percentReady=true;
}
setInterval(install,500);setTimeout(install,800);
})();
