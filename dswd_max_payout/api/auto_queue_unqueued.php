<?php
include('../auth/check.php');
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Auto queue assignment is disabled. Beneficiaries must be checked in verifier first, then queued manually.',
    'created' => 0,
    'regular_created' => 0,
    'priority_created' => 0,
    'disabled' => true
]);
exit;
?>
