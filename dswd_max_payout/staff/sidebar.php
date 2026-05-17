<?php
$current = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div class="logo">NAVIGATION</div>

    <nav>
        <a href="verifier.php" class="<?php echo $current === 'verifier.php' ? 'active' : ''; ?>">
            <span class="material-icons">fact_check</span>
            Verify
        </a>

        <a href="assessment_screen.php" class="<?php echo ($current === 'assessment_screen.php' || $current === 'eligibility_form.php') ? 'active' : ''; ?>">
            <span class="material-icons">assignment</span>
            Assessment
        </a>

        <a href="confirmation_screen.php" class="<?php echo $current === 'confirmation_screen.php' ? 'active' : ''; ?>">
            <span class="material-icons">payments</span>
            Confirmation
        </a>

        <a href="analytics.php" class="<?php echo $current === 'analytics.php' ? 'active' : ''; ?>">
            <span class="material-icons">analytics</span>
            Analytics
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="../kiosk/index.php" target="_blank">
            <span class="material-icons">touch_app</span>
            Kiosk
        </a>

        <a href="counter_display.php" target="_blank">
            <span class="material-icons">desktop_windows</span>
            Counter Display
        </a>

        <a href="../auth/logout.php">
            <span class="material-icons">logout</span>
            Logout
        </a>
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

function installVerifierHelpers(){
    if(!document.querySelector('.verifier-table')) return;

    window.downloadTemplate=function(){
        var csv='last_name,first_name,middle_name,ext_name,birthday,age,sex,contact_number,id_presented,household_id,region,province,city_municipality,barangay,lgu,program_type,pwd,pregnant\n';
        csv+='Dela Cruz,Juan,Santos,,01/15/1980,44,Male,09171234567,PhilSys ID,HH-001,Region IV-A,Cavite,Imus City,Alapan I-A,Imus City,AICS,No,No\n';
        csv+='Reyes,Maria,Luna,,03/12/1995,29,Female,09181234567,Barangay Certificate,HH-002,Region IV-A,Cavite,Imus City,Bucandala I,Imus City,AICS,No,Yes\n';
        csv+='Garcia,Pedro,Lim,,05/08/1970,54,Male,09191234567,PWD ID,HH-003,Region IV-A,Cavite,Bacoor City,Molino I,Bacoor City,Medical Assistance,Yes,No\n';
        csv+='Santos,Elena,Cruz,,06/20/1955,70,Female,09201234567,Senior Citizen ID,HH-004,Region IV-A,Cavite,Dasmarinas City,Salawag,Dasmarinas City,AICS,No,No\n';
        var blob=new Blob([csv],{type:'text/csv;charset=utf-8;'});
        var url=URL.createObjectURL(blob);
        var a=document.createElement('a');
        a.href=url;
        a.download='beneficiary_import_template_pwd_pregnant.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    };

    window.manualRefresh=function(){
        try{
            if(typeof isLoading!=='undefined') isLoading=false;
            if(typeof currentPage!=='undefined') currentPage=1;
            if(typeof loadVerifierData==='function'){
                loadVerifierData();
                show('Verifier refreshed');
            }else{
                location.reload();
            }
        }catch(e){location.reload();}
    };

    document.querySelectorAll('.refresh-main,.icon-link').forEach(function(btn){
        if(btn.dataset.refreshFixed==='1') return;
        btn.dataset.refreshFixed='1';
        btn.addEventListener('click',function(e){e.preventDefault();window.manualRefresh();},true);
    });
}
setInterval(installVerifierHelpers,500);
setTimeout(installVerifierHelpers,1000);
})();
</script>
