(function(){
function isVerifier(){return !!document.getElementById('importResult')&&!!document.getElementById('importFile')}
function esc(v){return String(v==null?'':v).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;')}
function progressBox(title,percent,detail){percent=Math.max(0,Math.min(100,Math.round(percent||0)));return '<div style="font-weight:900;margin-bottom:8px;">'+esc(title)+' <span style="float:right">'+percent+'%</span></div><div style="height:12px;background:#e5e7eb;border-radius:999px;overflow:hidden;border:1px solid #cbd5e1"><div style="height:100%;width:'+percent+'%;background:#168fcb;border-radius:999px;transition:width .25s ease"></div></div><div style="margin-top:8px;font-size:12px;color:#475569">'+esc(detail||'Please wait...')+'</div>'}
function show(html,ok){var box=document.getElementById('importResult');if(!box)return;box.style.display='block';box.style.borderColor=ok===false?'#fecaca':'#86efac';box.style.background=ok===false?'#fef2f2':'#f0fdf4';box.style.color=ok===false?'#991b1b':'#166534';box.innerHTML=html}
function install(){
 if(!isVerifier()||typeof uploadImportFile!=='function'||uploadImportFile._percentReady)return;
 var oldUpload=uploadImportFile;
 uploadImportFile=async function(){
  var percent=0,finished=false;
  show(progressBox('Preparing import',5,'Checking selected file...'),true);
  var timer=setInterval(function(){
   if(finished)return;
   if(percent<80)percent+=4;else if(percent<95)percent+=1;
   show(progressBox('Importing beneficiaries',percent,'Uploading, checking duplicates, and saving records...'),true);
  },350);
  try{
   percent=10;
   await oldUpload();
   finished=true;clearInterval(timer);
   show(progressBox('Import complete',100,'Refreshing verifier table...'),true);
   setTimeout(function(){var box=document.getElementById('importResult');if(box&&box.innerHTML.indexOf('Import complete')>-1){box.innerHTML='Import finished. Check the updated verifier table below.'}},900);
  }catch(e){finished=true;clearInterval(timer);console.error(e);show('Import failed. Please check the file format and try again.',false)}
 };
 uploadImportFile._percentReady=true;
}
setInterval(install,500);setTimeout(install,800);
})();
