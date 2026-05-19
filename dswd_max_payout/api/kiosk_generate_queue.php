<?php
include('../config/db.php');
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

function reply_json($ok, $data = []) { echo json_encode(array_merge(['success'=>$ok], $data)); exit; }
function val($arr,$key){ return trim((string)($arr[$key] ?? '')); }
function phone_clean($v){
    $v=preg_replace('/[^0-9+]/','',trim((string)$v));
    if($v==='') return '';
    if(preg_match('/^09\d{9}$/',$v)) return $v;
    if(preg_match('/^\+639\d{9}$/',$v)) return $v;
    if(preg_match('/^639\d{9}$/',$v)) return '+'.$v;
    return false;
}
function phone_variants($v){
    if(preg_match('/^09\d{9}$/',$v)) return [$v,'+63'.substr($v,1),'63'.substr($v,1)];
    if(preg_match('/^\+639\d{9}$/',$v)) return [$v,'0'.substr($v,3),substr($v,1)];
    return [$v,$v,$v];
}
function assistance_prefix($program){
    $map=['Medical Assistance'=>'MEDI','Funeral Assistance'=>'FNRL','Educational Assistance'=>'EDUC','Transportation Assistance'=>'TRAN','Material Assistance'=>'MTRL','Food Assistance'=>'FDAS','Cash Relief Assistance'=>'CRAS','Priority Assistance'=>'PRIO','Priority'=>'PRIO'];
    return $map[$program] ?? 'MEDI';
}
function next_prefixed_code($conn,$prefix,$table,$column,$dateFilter=''){
    $like=$prefix.'-%';
    $start=strlen($prefix)+2;
    $sql="SELECT MAX(CAST(SUBSTRING($column, ?) AS UNSIGNED)) AS n FROM $table WHERE $column LIKE ? $dateFilter";
    $stmt=$conn->prepare($sql);
    $stmt->bind_param('is',$start,$like);
    $stmt->execute();
    $res=$stmt->get_result();
    $n=1;
    if($res&&$res->num_rows){$r=$res->fetch_assoc(); if($r['n']!==null)$n=intval($r['n'])+1;}
    return $prefix.'-'.str_pad($n,5,'0',STR_PAD_LEFT);
}
try{
    if($_SERVER['REQUEST_METHOD']!=='POST') reply_json(false,['message'=>'Invalid request.']);
    $input=json_decode(file_get_contents('php://input'),true);
    if(!is_array($input)) $input=$_POST;

    $program=val($input,'program_type');
    $programs=['Medical Assistance','Funeral Assistance','Educational Assistance','Transportation Assistance','Material Assistance','Food Assistance','Cash Relief Assistance','Priority Assistance','Priority'];
    if(!in_array($program,$programs,true)) reply_json(false,['message'=>'Select an assistance type.']);

    $prefix=assistance_prefix($program);
    $qtype=($prefix==='PRIO')?'priority':'regular';
    if($prefix==='PRIO') $program='Priority Assistance';
    $is_kiosk=(val($input,'source')==='kiosk') || (val($input,'first_name')==='' && val($input,'last_name')==='');

    if($is_kiosk){
        $ticketSeed=substr(str_replace('.', '', (string)microtime(true)), -9);
        $first='KIOSK'; $middle=''; $last='CLIENT '.$ticketSeed; $ext='';
        $phone='09'.str_pad(substr($ticketSeed,-9),9,'0',STR_PAD_LEFT);
        $province='Oriental Mindoro'; $city='Kiosk'; $barangay='Kiosk'; $street='Kiosk Terminal';
    }else{
        $first=val($input,'first_name'); $middle=val($input,'middle_name'); $last=val($input,'last_name'); $ext=val($input,'ext_name');
        $phone=phone_clean($input['contact_number'] ?? '');
        $province=val($input,'province'); $city=val($input,'city_municipality'); $barangay=val($input,'barangay'); $street=val($input,'street_address');
        $provinces=['Oriental Mindoro','Occidental Mindoro','Marinduque','Romblon','Palawan','Independent City / No Province'];
        if($first===''||$last==='') reply_json(false,['message'=>'Name is required.']);
        if($phone===false||$phone==='') reply_json(false,['message'=>'Valid contact number is required.']);
        if(!in_array($province,$provinces,true)) reply_json(false,['message'=>'MIMAROPA address only.']);
        if($city===''||$barangay===''||$street==='') reply_json(false,['message'=>'Complete address is required.']);
        $pv=phone_variants($phone);
        $dup=$conn->prepare("SELECT beneficiary_code FROM beneficiaries WHERE TRIM(contact_number) IN (?,?,?) OR (LOWER(TRIM(first_name))=LOWER(TRIM(?)) AND LOWER(TRIM(last_name))=LOWER(TRIM(?)) AND LOWER(TRIM(city_municipality))=LOWER(TRIM(?)) AND LOWER(TRIM(barangay))=LOWER(TRIM(?))) LIMIT 1");
        $dup->bind_param('sssssss',$pv[0],$pv[1],$pv[2],$first,$last,$city,$barangay);
        $dup->execute(); $dr=$dup->get_result();
        if($dr && $dr->num_rows>0){$ex=$dr->fetch_assoc(); reply_json(false,['message'=>'Possible duplicate beneficiary found: '.($ex['beneficiary_code'] ?? 'existing record').'. Please ask staff to verify.']);}
    }

    $code=next_prefixed_code($conn,$prefix,'beneficiaries','beneficiary_code');
    $queue_no=next_prefixed_code($conn,$prefix,'queue_entries','queue_number',"AND DATE(transaction_date)=CURDATE()");
    $region='MIMAROPA'; $bm=1; $bd=1; $by=2000; $age=0; $sex='Male'; $lgu=$city; $id='For Verification'; $hh=''; $sms=0; $preg=0;
    $conn->begin_transaction();
    $stmt=$conn->prepare("INSERT INTO beneficiaries (beneficiary_code,first_name,middle_name,last_name,ext_name,contact_number,birthday_month,birthday_day,birthday_year,age,sex,lgu,national_id,household_id,program_type,region,province,city_municipality,barangay,sms_opt_in,is_pregnant) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('ssssssiiiisssssssssii',$code,$first,$middle,$last,$ext,$phone,$bm,$bd,$by,$age,$sex,$lgu,$id,$hh,$program,$region,$province,$city,$barangay,$sms,$preg);
    if(!$stmt->execute()) throw new Exception('Unable to save record.');
    $beneficiary_id=$conn->insert_id;
    $q=$conn->prepare("INSERT INTO queue_entries (queue_number,queue_type,beneficiary_id,transaction_date,status,workflow_status,table_number,counter_number,called_at,assessed_at,paid_at) VALUES (?,?,?,CURDATE(),'waiting','WAITING_STEP_2',NULL,NULL,NULL,NULL,NULL)");
    $q->bind_param('ssi',$queue_no,$qtype,$beneficiary_id);
    if(!$q->execute()) throw new Exception('Unable to create queue.');
    $conn->commit();
    reply_json(true,['queue_number'=>$queue_no,'queue_type'=>$qtype,'program_type'=>$program,'beneficiary_code'=>$code,'client_name'=>trim($first.' '.$middle.' '.$last.' '.$ext),'contact_number'=>$phone,'address'=>trim($street.', '.$barangay.', '.$city.', '.$province),'region'=>'MIMAROPA','date_time'=>date('M d, Y h:i A')]);
}catch(Throwable $e){ @ $conn->rollback(); reply_json(false,['message'=>'Queue registration failed. '.$e->getMessage()]); }
?>
