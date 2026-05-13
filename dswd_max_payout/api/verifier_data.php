<?php
include('../auth/check.php');
include('../config/db.php');
header('Content-Type: application/json');

$sql = "SELECT b.id,b.beneficiary_code,b.last_name,b.first_name,b.middle_name,b.ext_name,b.region,b.province,b.city_municipality,b.barangay,b.contact_number,b.birthday_month,b.birthday_day,b.birthday_year,b.age,b.sex,b.lgu,q.queue_number,q.queue_type,q.workflow_status,IFNULL(e.eligibility_status,'Pending Review') AS eligibility_status FROM beneficiaries b LEFT JOIN eligibility_forms e ON e.beneficiary_id=b.id LEFT JOIN queue_entries q ON q.beneficiary_id=b.id AND DATE(q.transaction_date)=CURDATE() AND q.id=(SELECT MAX(q2.id) FROM queue_entries q2 WHERE q2.beneficiary_id=b.id AND DATE(q2.transaction_date)=CURDATE() AND (q2.workflow_status IS NULL OR q2.workflow_status!='CANCELLED')) ORDER BY CASE WHEN q.queue_type='priority' AND (q.workflow_status IS NULL OR q.workflow_status!='CANCELLED') THEN 0 ELSE 1 END ASC, CASE WHEN q.queue_type='priority' THEN CAST(SUBSTRING(q.queue_number,6) AS UNSIGNED) ELSE 999999 END ASC, CASE WHEN q.queue_type='regular' THEN CAST(SUBSTRING(q.queue_number,5) AS UNSIGNED) ELSE 999999 END ASC, TRIM(b.last_name) ASC, TRIM(b.first_name) ASC, TRIM(b.middle_name) ASC, b.id ASC";

$result = $conn->query($sql);
if (!$result) {
    echo json_encode(['success'=>false,'message'=>'SQL Error: '.$conn->error,'beneficiaries'=>[]]);
    exit;
}

$beneficiaries = [];
while ($row = $result->fetch_assoc()) {
    $beneficiaries[] = [
        'id'=>$row['id'],
        'beneficiary_code'=>$row['beneficiary_code'],
        'last_name'=>$row['last_name'],
        'first_name'=>$row['first_name'],
        'middle_name'=>$row['middle_name'],
        'ext_name'=>$row['ext_name'],
        'region'=>$row['region'],
        'province'=>$row['province'],
        'city_municipality'=>$row['city_municipality'],
        'barangay'=>$row['barangay'],
        'contact_number'=>$row['contact_number'],
        'birthday_month'=>$row['birthday_month'],
        'birthday_day'=>$row['birthday_day'],
        'birthday_year'=>$row['birthday_year'],
        'age'=>$row['age'],
        'sex'=>$row['sex'],
        'lgu'=>$row['lgu'],
        'queue_number'=>$row['queue_number'],
        'queue_type'=>$row['queue_type'],
        'workflow_status'=>$row['workflow_status'],
        'eligibility_status'=>$row['eligibility_status']
    ];
}

echo json_encode(['success'=>true,'beneficiaries'=>$beneficiaries]);
exit;
?>
