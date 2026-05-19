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
try{
    if($_SERVER['REQUEST_METHOD']!=='POST') reply_json(false,['message'=>'Invalid request.']);
    $input=json_decode(file_get_contents('php://input'),true);
    if(!is_array($input)) $input=$_POST;
    $first=val($input,'first_name'); $middle=val($input,'middle_name'); $last=val($input,'last_name'); $ext=val($input,'ext_name');
    $phone=phone_clean($input['contact_number'] ?? '');
    $province=val($input,'province'); $city=val($input,'city_municipality'); $barangay=val($input,'barangay'); $street=val($input,'street_address');
    $program=val($input,'program_type');
    $provinces=['Oriental Mindoro','Occidental Mindoro','Marinduque','Romblon','Palawan','Independent City / No Province'];
    $programs=['Medical Assistance','Funeral Assistance','Educational Assistance','Transportation Assistance','Material Assistance','Food Assistance','Cash Relief Assistance'];
    if($first===''||$last==='') reply_json(false,['message'=>'Name is required.']);
    if($phone===false||$phone==='') reply_json(false,['message'=>'Valid contact number is required.']);
    if(!in_array($province,$provinces,true)) reply_json(false,['message'=>'MIMAROPA address only.']);
    if($city===''||$barangay===''||$street==='') reply_json(false,['message'=>'Complete address is required.']);
    if(!in_array($program,$programs,true)) reply_json(false,['message'=>'Select an assistance type.']);
    $pv=phone_variants($phone);
    $dup=$conn->prepare("SELECT beneficiary_code FROM beneficiaries WHERE TRIM(contact_number) IN (?,?,?) OR (LOWER(TRIM(first_name))=LOWER(TRIM(?)) AND LOWER(TRIM(last_name))=LOWER(TRIM(?)) AND LOWER(TRIM(city_municipality))=LOWER(TRIM(?)) AND LOWER(TRIM(barangay))=LOWER(TRIM(?))) LIMIT 1");
    $dup->bind_param('sssssss',$pv[0],$pv[1],$pv[2],$first,$last,$city,$barangay);
    $dup->execute(); $dr=$dup->get_result();
    if($dr && $dr->num_rows>0){ $ex=$dr->fetch_assoc(); reply_json(false,['message'=>'Possible duplicate beneficiary found: '.($ex['beneficiary_code'] ?? 'existing record').'. Please ask staff to verify.']); }
    $res=$conn->query("SELECT MAX(CAST(SUBSTRING(beneficiary_code,5) AS UNSIGNED)) AS n FROM beneficiaries WHERE beneficiary_code LIKE 'PAL-%'");
    $n=1; if($res&&$res->num_rows){$r=$res->fetch_assoc(); if($r['n']!==null)$n=intval($r['n'])+1;} $code='PAL-'.str_pad($n,5,'0',STR_PAD_LEFT);
    $res=$conn->query("SELECT MAX(CAST(SUBSTRING(queue_number,5) AS UNSIGNED)) AS n FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND queue_type='regular' AND queue_number LIKE 'PAL-%'");
    $qn=1; if($res&&$res->num_rows){$r=$res->fetch_assoc(); if($r['n']!==null)$qn=intval($r['n'])+1;} $queue_no='PAL-'.str_pad($qn,4,'0',STR_PAD_LEFT);
    $region='MIMAROPA'; $bm=1; $bd=1; $by=2000; $age=0; $sex='Male'; $lgu=$city; $id='For Verification'; $hh=''; $sms=0; $preg=0;
    $conn->begin_transaction();
    $stmt=$conn->prepare("INSERT INTO beneficiaries (beneficiary_code,first_name,middle_name,last_name,ext_name,contact_number,birthday_month,birthday_day,birthday_year,age,sex,lgu,national_id,household_id,program_type,region,province,city_municipality,barangay,sms_opt_in,is_pregnant) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('ssssssiiiisssssssssii',$code,$first,$middle,$last,$ext,$phone,$bm,$bd,$by,$age,$sex,$lgu,$id,$hh,$program,$region,$province,$city,$barangay,$sms,$preg);
    if(!$stmt->execute()) throw new Exception('Unable to save record.');
    $beneficiary_id=$conn->insert_id; $qtype='regular';
    $q=$conn->prepare("INSERT INTO queue_entries (queue_number,queue_type,beneficiary_id,transaction_date,status,workflow_status,table_number,counter_number,called_at,assessed_at,paid_at) VALUES (?,?,?,CURDATE(),'waiting','WAITING_STEP_2',NULL,NULL,NULL,NULL,NULL)");
    $q->bind_param('ssi',$queue_no,$qtype,$beneficiary_id); if(!$q->execute()) throw new Exception('Unable to create queue.');
    $conn->commit();
    reply_json(true,['queue_number'=>$queue_no,'queue_type'=>$qtype,'program_type'=>$program,'beneficiary_code'=>$code,'client_name'=>trim($first.' '.$middle.' '.$last.' '.$ext),'contact_number'=>$phone,'address'=>trim($street.', '.$barangay.', '.$city.', '.$province),'region'=>'MIMAROPA','date_time'=>date('M d, Y h:i A')]);
}catch(Throwable $e){ @ $conn->rollback(); reply_json(false,['message'=>'Queue registration failed. '.$e->getMessage()]); }
?>
