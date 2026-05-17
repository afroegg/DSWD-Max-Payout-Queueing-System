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
.category-badge{display:inline-block;margin-left:6px;padding:3px 7px;border-radius:999px;font-size:10px;font-weight:900;text-transform:uppercase}.cat-pwd{background:#ede9fe;color:#6d28d9}.cat-pregnant{background:#fce7f3;color:#be185d}.cat-senior{background:#dbeafe;color:#1d4ed8}.cat-regular{background:#e5e7eb;color:#374151}
.detail-item.priority-detail{border-color:#fed7aa!important;background:#fff7ed!important}.detail-item.pwd-detail{border-color:#c4b5fd!important;background:#f5f3ff!important}.detail-item.pregnant-detail{border-color:#f9a8d4!important;background:#fdf2f8!important}.detail-item.priority-detail strong,.detail-item.pwd-detail strong,.detail-item.pregnant-detail strong{font-weight:900!important}
.verifier-table .btn-regenerate:not(:disabled){background:#f97316!important;color:#fff!important;opacity:1!important}.verifier-table .btn-regenerate:disabled{background:#9ca3af!important;color:#fff!important;opacity:.55!important}
.sheet-card{position:relative}.step-loading-overlay{position:absolute;inset:0;background:rgba(255,255,255,.94);z-index:50;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:10px;color:#64748b;font-weight:900}.step-loading-overlay .spinner{width:34px;height:34px;border-radius:50%;border:4px solid #dbeafe;border-top-color:#168fcb;animation:spin .8s linear infinite}@keyframes spin{to{transform:rotate(360deg)}}
</style>
<div id="quickToast" class="quick-toast"></div>
<script>
(function(){
var toast=document.getElementById('quickToast'),timer=null;
function show(msg){if(!toast)return;toast.textContent=msg||'Done';toast.style.display='block';clearTimeout(timer);timer=setTimeout(function(){toast.style.display='none'},1200)}
function isStepPage(){var p=window.location.pathname;return p.indexOf('assessment_screen.php')>-1||p.indexOf('confirmation_screen.php')>-1}
function installStepLoading(){
    if(!isStepPage())return;
    var card=document.querySelector('.sheet-card');
    if(!card||card.querySelector('.step-loading-overlay'))return;
    var overlay=document.createElement('div');
    overlay.className='step-loading-overlay';
    overlay.innerHTML='<div class="spinner"></div><div>Loading beneficiaries...</div>';
    card.appendChild(overlay);
    function hide(){overlay.style.display='none'}
    window.addEventListener('load',function(){setTimeout(hide,350)});
    setTimeout(hide,900);
}
function installEmptyStateForStepTables(){
    if(!isStepPage())return;
    var tbody=document.getElementById('rows');
    if(!tbody)return;
    var dataRows=tbody.querySelectorAll('tr.data');
    if(dataRows.length===0&&!tbody.querySelector('.empty-state-row')){
        tbody.innerHTML='<tr class="empty-state-row"><td colspan="9" class="empty-state">No beneficiaries found.</td></tr>';
        var p=document.getElementById('p'); if(p)p.innerHTML='';
        var info=document.getElementById('recordInfo'); if(info)info.textContent='Showing 0 of 0 filtered records (0 total)';
        var last=document.getElementById('lastUpdated'); if(last)last.textContent='Last updated: '+new Date().toLocaleTimeString();
    }
}
installStepLoading();
setInterval(installEmptyStateForStepTables,500);
setTimeout(installEmptyStateForStepTables,300);
function installVerifierRefresh(){
    if(!document.querySelector('.verifier-table'))return;
    document.querySelectorAll('.refresh-main,.icon-link').forEach(function(btn){
        if(btn.dataset.refreshFixed==='1')return;
        btn.dataset.refreshFixed='1';
        btn.addEventListener('click',function(e){
            if(typeof loadVerifierData==='function'){e.preventDefault();try{isLoading=false;currentPage=1;loadVerifierData();show('Verifier refreshed')}catch(err){location.reload()}}
        },true);
    });
}
setInterval(installVerifierRefresh,500);setTimeout(installVerifierRefresh,1000);
})();
</script>
