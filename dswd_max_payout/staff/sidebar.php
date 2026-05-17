<?php
$current = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div class="logo">NAVIGATION</div>

    <nav>
        <a href="verifier.php" class="<?php echo $current === 'verifier.php' ? 'active' : ''; ?>"><span class="material-icons">fact_check</span>Verify</a>
        <a href="assessment_screen.php" class="<?php echo ($current === 'assessment_screen.php' || $current === 'eligibility_form.php') ? 'active' : ''; ?>"><span class="material-icons">assignment</span>Assessment</a>
        <a href="confirmation_screen.php" class="<?php echo $current === 'confirmation_screen.php' ? 'active' : ''; ?>"><span class="material-icons">payments</span>Confirmation</a>
        <a href="analytics.php" class="<?php echo $current === 'analytics.php' ? 'active' : ''; ?>"><span class="material-icons">analytics</span>Analytics</a>
    </nav>

    <div class="sidebar-footer">
        <a href="../kiosk/index.php" target="_blank"><span class="material-icons">touch_app</span>Kiosk</a>
        <a href="counter_display.php" target="_blank"><span class="material-icons">desktop_windows</span>Counter Display</a>
        <a href="../auth/logout.php"><span class="material-icons">logout</span>Logout</a>
    </div>
</aside>

<style>
.quick-toast{position:fixed;right:18px;bottom:18px;background:#0f172a;color:#fff;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:800;z-index:99999;display:none;box-shadow:0 10px 24px rgba(15,23,42,.24)}
.verifier-table .btn-regenerate:not(:disabled){background:#f97316!important;color:#fff!important;opacity:1!important}.verifier-table .btn-regenerate:disabled{background:#9ca3af!important;color:#fff!important;opacity:.55!important}
.category-badge{display:inline-block;margin-left:6px;padding:3px 7px;border-radius:999px;font-size:10px;font-weight:900;text-transform:uppercase}.cat-pwd{background:#ede9fe;color:#6d28d9}.cat-pregnant{background:#fce7f3;color:#be185d}.cat-senior{background:#dbeafe;color:#1d4ed8}.cat-regular{background:#e5e7eb;color:#374151}
.detail-item.priority-detail{border-color:#fed7aa!important;background:#fff7ed!important}.detail-item.pwd-detail{border-color:#c4b5fd!important;background:#f5f3ff!important}.detail-item.pregnant-detail{border-color:#f9a8d4!important;background:#fdf2f8!important}.detail-item.priority-detail strong,.detail-item.pwd-detail strong,.detail-item.pregnant-detail strong{font-weight:900!important}
.counter-table td:nth-child(4){width:34%!important}.counter-table .action-buttons{display:flex!important;flex-wrap:wrap!important;gap:8px!important;align-items:center!important;justify-content:flex-start!important}.counter-table .action-form{display:inline-flex!important;margin:0!important}.counter-table .counter-select{width:110px!important;min-height:38px!important;height:38px!important;padding:0 8px!important;border-radius:8px!important;font-size:12px!important;font-weight:800!important;background:#fff!important;color:#111827!important}.counter-table .action-btn{width:38px!important;min-width:38px!important;height:38px!important;min-height:38px!important;padding:0!important;border-radius:8px!important;font-size:0!important;gap:0!important;color:#fff!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;border:none!important;box-shadow:none!important;opacity:1!important}.counter-table .action-btn .material-icons{font-size:20px!important;margin:0!important}.counter-table .action-btn.call{background:#16a34a!important}.counter-table .action-btn.gis{background:#2563eb!important}.counter-table .action-btn.revert{background:#f97316!important}.counter-table .action-btn.assessed{background:#0ea5e9!important}.counter-table .action-btn.paid{background:#9333ea!important}.counter-table .action-btn.assessed-paid{background:#14b8a6!important}.counter-table .action-btn.cancel{background:#dc2626!important}.counter-table .action-btn:disabled,.counter-table .counter-select:disabled{opacity:.35!important;cursor:not-allowed!important;filter:grayscale(.15)!important}.counter-table .action-btn:hover:not(:disabled){transform:translateY(-1px);opacity:.9!important}
</style>
<div id="quickToast" class="quick-toast"></div>
<script>
(function(){
var toast=document.getElementById('quickToast'),timer=null;
function show(msg){if(!toast)return;toast.textContent=msg||'Done';toast.style.display='block';clearTimeout(timer);timer=setTimeout(function(){toast.style.display='none'},1200)}
window.alert=function(msg){show(msg)};
document.addEventListener('click',function(e){var btn=e.target.closest('button,input[type=submit]');if(btn&&btn.form){btn.form.onsubmit=null;}},true);

function installEmptyStateForStepTables(){
    var path=window.location.pathname;
    if(!path.includes('assessment_screen.php')&&!path.includes('confirmation_screen.php'))return;
    var tbody=document.getElementById('rows');
    if(!tbody)return;
    var dataRows=tbody.querySelectorAll('tr.data');
    if(dataRows.length===0&&!tbody.querySelector('.empty-state-row')){
        tbody.innerHTML='<tr class="empty-state-row"><td colspan="9" class="empty-state">No beneficiaries found.</td></tr>';
        var p=document.getElementById('p');
        if(p)p.innerHTML='';
        var info=document.getElementById('recordInfo');
        if(info)info.textContent='Showing 0 of 0 filtered records (0 total)';
        var last=document.getElementById('lastUpdated');
        if(last)last.textContent='Last updated: '+new Date().toLocaleTimeString();
    }
}
setInterval(installEmptyStateForStepTables,500);
setTimeout(installEmptyStateForStepTables,300);

function csvEscape(v){v=String(v==null?'':v);return /[",\n]/.test(v)?'"'+v.replace(/"/g,'""')+'"':v}
function installVerifierHelpers(){
    if(!document.querySelector('.verifier-table')) return;
    window.downloadTemplate=function(){
        var first=['Juan','Maria','Pedro','Elena','Jose','Ana','Carlo','Rosa','Miguel','Lorna','Paolo','Grace','Mark','Aileen','Ramon','Camille','Daryl','Jessa','Nestor','Clara'];
        var middle=['Santos','Reyes','Cruz','Garcia','Lim','Mendoza','Flores','Aquino','Torres','Ramos'];
        var last=['Dela Cruz','Reyes','Garcia','Santos','Mendoza','Flores','Villanueva','Bautista','Aquino','Ramos','Torres','Navarro','Castillo','Rivera','Morales'];
        var addr=[
            ['Region IV-A','Cavite','Imus City','Barangay Alapan I-A','Imus City'],['Region IV-A','Cavite','Imus City','Barangay Anabu I-A','Imus City'],['Region IV-A','Cavite','Imus City','Barangay Bucandala I','Imus City'],['Region IV-A','Cavite','Imus City','Barangay Malagasang I-A','Imus City'],
            ['Region IV-A','Cavite','Bacoor City','Barangay Molino I','Bacoor City'],['Region IV-A','Cavite','Bacoor City','Barangay Molino II','Bacoor City'],['Region IV-A','Cavite','Bacoor City','Barangay Bayanan','Bacoor City'],['Region IV-A','Cavite','Bacoor City','Barangay Talaba I','Bacoor City'],
            ['Region IV-A','Cavite','Dasmariñas City','Barangay Burol I','Dasmariñas City'],['Region IV-A','Cavite','Dasmariñas City','Barangay Langkaan I','Dasmariñas City'],['Region IV-A','Cavite','Dasmariñas City','Barangay Salawag','Dasmariñas City'],
            ['Region IV-A','Cavite','General Trias City','Barangay Manggahan','General Trias City'],['Region IV-A','Cavite','General Trias City','Barangay San Francisco','General Trias City'],['Region IV-A','Cavite','General Trias City','Barangay Tejero','General Trias City']
        ];
        var ids=['PhilSys ID','Barangay Certificate','Senior Citizen ID','PWD ID','Voter ID','None'];
        var programs=['AICS','AKAP','Emergency Assistance','Medical Assistance','Educational Assistance'];
        var csv='last_name,first_name,middle_name,ext_name,birthday,age,sex,contact_number,id_presented,household_id,region,province,city_municipality,barangay,lgu,program_type,pwd,pregnant\n';
        for(var i=1;i<=100;i++){
            var sex=i%3===0?'Female':'Male';
            var age=(i%10===0)?(60+(i%20)):(18+(i%42));
            var pwd=i%17===0?'Yes':'No';
            var pregnant=(sex==='Female'&&i%13===0)?'Yes':'No';
            var a=addr[i%addr.length];
            var row=[last[i%last.length],first[i%first.length],middle[i%middle.length],i%25===0?'Jr.':'',String((i%12)+1).padStart(2,'0')+'/'+String((i%28)+1).padStart(2,'0')+'/'+(new Date().getFullYear()-age),age,sex,'09'+String(170000000+i).slice(0,9),ids[i%ids.length],'HH-'+String(i).padStart(3,'0'),a[0],a[1],a[2],a[3],a[4],programs[i%programs.length],pwd,pregnant];
            csv+=row.map(csvEscape).join(',')+'\n';
        }
        var blob=new Blob([csv],{type:'text/csv;charset=utf-8;'}),url=URL.createObjectURL(blob),a=document.createElement('a');
        a.href=url;a.download='beneficiary_import_template_100_complete_addresses.csv';document.body.appendChild(a);a.click();document.body.removeChild(a);URL.revokeObjectURL(url);
    };
    window.manualRefresh=function(){try{if(typeof isLoading!=='undefined')isLoading=false;if(typeof currentPage!=='undefined')currentPage=1;if(typeof loadVerifierData==='function'){loadVerifierData();show('Verifier refreshed')}else location.reload()}catch(e){location.reload()}};
    document.querySelectorAll('.refresh-main,.icon-link').forEach(function(btn){if(btn.dataset.refreshFixed==='1')return;btn.dataset.refreshFixed='1';btn.addEventListener('click',function(e){e.preventDefault();window.manualRefresh()},true)});
}
setInterval(installVerifierHelpers,500);setTimeout(installVerifierHelpers,1000);
})();
</script>