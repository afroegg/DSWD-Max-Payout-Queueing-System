<?php
include_once(__DIR__ . '/../config/db.php');
include_once(__DIR__ . '/semaphore_sms.php');

function notifyCustomersTenAway(mysqli $conn, $today) {
    $sql = "
        SELECT
            q.id AS queue_entry_id,
            q.queue_number,
            q.sms_notified_10,
            b.first_name,
            b.last_name,
            b.contact_number,
            b.sms_opt_in,
            (
                SELECT COUNT(*)
                FROM queue_entries q2
                WHERE q2.transaction_date = q.transaction_date
                  AND q2.id < q.id
                  AND q2.status IN ('waiting', 'serving')
            ) AS ahead_count
        FROM queue_entries q
        JOIN beneficiaries b ON q.beneficiary_id = b.id
        WHERE q.transaction_date = ?
          AND q.status = 'waiting'
        ORDER BY q.id ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $queueEntryId = (int)$row['queue_entry_id'];
        $aheadCount = (int)$row['ahead_count'];
        $smsNotified = (int)$row['sms_notified_10'];
        $smsOptIn = (int)$row['sms_opt_in'];
        $mobile = trim((string)$row['contact_number']);
        $queueNumber = $row['queue_number'];

        if ($aheadCount === 10 && $smsNotified === 0 && $smsOptIn === 1 && $mobile !== '') {
            $message = "DSWD Max Payout: Queue {$queueNumber}, you are almost up. Please return to the payout area. You are now 10 queues away.";

            $send = sendSemaphoreSMS($mobile, $message);

            $status = $send['success'] ? 'sent' : $send['status'];
            $response = $send['response'];

            $log = $conn->prepare("
                INSERT INTO sms_logs (queue_entry_id, mobile_number, message, provider, status, provider_response)
                VALUES (?, ?, ?, 'semaphore', ?, ?)
            ");
            $log->bind_param("issss", $queueEntryId, $mobile, $message, $status, $response);
            $log->execute();

            if ($send['success']) {
                $update = $conn->prepare("UPDATE queue_entries SET sms_notified_10 = 1 WHERE id = ?");
                $update->bind_param("i", $queueEntryId);
                $update->execute();
            }
        }
    }
}