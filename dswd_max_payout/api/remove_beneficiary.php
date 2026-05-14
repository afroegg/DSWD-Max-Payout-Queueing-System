<?php
include('../auth/check.php');
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../staff/verifier.php');
    exit;
}

$beneficiary_id = intval($_POST['beneficiary_id'] ?? 0);
$redirect = trim($_POST['redirect'] ?? '../staff/verifier.php');

if ($beneficiary_id <= 0) {
    echo "<script>alert('Invalid beneficiary selected.'); window.location.href='{$redirect}';</script>";
    exit;
}

$conn->begin_transaction();

try {
    $getQueues = $conn->prepare("SELECT id FROM queue_entries WHERE beneficiary_id = ?");
    $getQueues->bind_param('i', $beneficiary_id);
    $getQueues->execute();
    $queueResult = $getQueues->get_result();

    $queueIds = [];
    while ($row = $queueResult->fetch_assoc()) {
        $queueIds[] = intval($row['id']);
    }

    foreach ($queueIds as $queue_id) {
        $delEligibility = $conn->prepare("DELETE FROM eligibility_forms WHERE queue_entry_id = ?");
        $delEligibility->bind_param('i', $queue_id);
        $delEligibility->execute();

        $delPayouts = $conn->prepare("DELETE FROM payouts WHERE queue_entry_id = ?");
        $delPayouts->bind_param('i', $queue_id);
        $delPayouts->execute();
    }

    $delEligibilityByBeneficiary = $conn->prepare("DELETE FROM eligibility_forms WHERE beneficiary_id = ?");
    $delEligibilityByBeneficiary->bind_param('i', $beneficiary_id);
    $delEligibilityByBeneficiary->execute();

    $delPayoutsByBeneficiary = $conn->prepare("DELETE FROM payouts WHERE beneficiary_id = ?");
    $delPayoutsByBeneficiary->bind_param('i', $beneficiary_id);
    $delPayoutsByBeneficiary->execute();

    $delQueues = $conn->prepare("DELETE FROM queue_entries WHERE beneficiary_id = ?");
    $delQueues->bind_param('i', $beneficiary_id);
    $delQueues->execute();

    $delBeneficiary = $conn->prepare("DELETE FROM beneficiaries WHERE id = ?");
    $delBeneficiary->bind_param('i', $beneficiary_id);
    $delBeneficiary->execute();

    $conn->commit();

    echo "<script>alert('Beneficiary removed successfully.'); window.location.href='{$redirect}';</script>";
    exit;
} catch (Exception $e) {
    $conn->rollback();
    $error = addslashes($e->getMessage());
    echo "<script>alert('Failed to remove beneficiary. Error: {$error}'); window.location.href='{$redirect}';</script>";
    exit;
}
?>
