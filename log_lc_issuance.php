
<?php
// log_lc_issuance.php (NEW - Dedicated to lc_issuance_log table)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php';

header('Content-Type: application/json');
$response = [
    'status' => 'error',
    'message' => 'Invalid request to log LC.',
    'tcid' => null,
    'lc_type_issued' => null,
    'debug_info' => []
];

if (!$conn || $conn->connect_error) {
    $response['message'] = 'DB Connection error for LC logging.';
    echo json_encode($response); exit;
}
$response['debug_info'][] = "DB Connection OK for LC Log.";

// Basic CSRF Check Placeholder - IMPORTANT if this is a sensitive action
// session_start();
// if (empty($_POST['ci_csrf_token']) || !isset($_SESSION['csrf_token_lc']) || $_POST['ci_csrf_token'] !== $_SESSION['csrf_token_lc']) { // Use a different session token name if needed
//    $response['message'] = 'CSRF Token Validation Failed for LC Log.'; echo json_encode($response); exit;
// }
// unset($_SESSION['csrf_token_lc']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $response['debug_info']['post_data_for_lc_log'] = $_POST;
    error_log("PHP LOG_LC_ISSUANCE: POST data: " . print_r($_POST, true));

    // --- Data from JS POST, specific for LC logging ---
    // JavaScript will send these
    $studentDbId = isset($_POST['current_student_db_id']) ? trim($_POST['current_student_db_id']) : null;
    $studentFullNameForLC = isset($_POST['student_full_name']) ? trim($_POST['student_full_name']) : null;
    $leavingDateInputForLC = isset($_POST['leaving_date']) ? trim($_POST['leaving_date']) : null; // DDMMYYYY
    $reasonForLeavingForLC = isset($_POST['reason_for_leaving']) ? trim($_POST['reason_for_leaving']) : null;
    $standardWhenLeavingForLC = isset($_POST['standard_studying_in_and_since']) ? trim($_POST['standard_studying_in_and_since']) : null;
    $academicProgressForLC = isset($_POST['academic_progress']) ? trim($_POST['academic_progress']) : null;
    $conductForLC = isset($_POST['conduct']) ? trim($_POST['conduct']) : null;
    // $manualLcSrNo = isset($_POST['manual_lc_sr_no']) ? trim($_POST['manual_lc_sr_no']) : null; // If using this optional field

    // Validations
    if (empty($studentDbId) || !filter_var($studentDbId, FILTER_VALIDATE_INT)) {
        $response['message'] = 'Student Database ID required for LC logging.'; echo json_encode($response); exit;
    }
    $studentDbId = (int)$studentDbId;

    if (empty($studentFullNameForLC)) {
        $response['message'] = 'Student Full Name (for LC) cannot be empty.'; echo json_encode($response); exit;
    }
    if (empty($leavingDateInputForLC)) { // Leaving date is CRUCIAL for issuing an LC
        $response['status'] = 'info'; // Or 'error' depending on how strict you want to be
        $response['message'] = 'LC not logged: Leaving Date is required for issuance.';
        $response['debug_info'][] = $response['message'];
        echo json_encode($response); $conn->close(); exit;
    }

    $dbLeavingDateForLCLog = null;
    if (preg_match("/^(\d{2})(\d{2})(\d{4})$/", $leavingDateInputForLC, $m) && checkdate((int)$m[2],(int)$m[1],(int)$m[3])) {
        $dbLeavingDateForLCLog = $m[3].'-'.$m[2].'-'.$m[1];
    } else {
        $response['message'] = 'Invalid Leaving Date for LC log. Expected DDMMYYYY.';
        $response['debug_info'][] = $response['message'] . " Input: " . $leavingDateInputForLC;
        echo json_encode($response); $conn->close(); exit;
    }
    $response['debug_info']['parsed_leaving_date_for_lc_log'] = $dbLeavingDateForLCLog;

    $conn->begin_transaction();
    try {
        // Determine LC Type
        $stmtCheckOriginal = $conn->prepare("SELECT COUNT(*) FROM `lc_issuance_log` WHERE `student_db_id` = ? AND `lc_type` = 'Original'");
        if (!$stmtCheckOriginal) throw new Exception("LC type check prepare: " . $conn->error);
        $stmtCheckOriginal->bind_param("i", $studentDbId);
        $stmtCheckOriginal->execute();
        $stmtCheckOriginal->bind_result($originalLCCount);
        $stmtCheckOriginal->fetch();
        $stmtCheckOriginal->close();
        $lcTypeToLog = ($originalLCCount == 0) ? 'Original' : 'Duplicate';
        $response['debug_info']['determined_lc_type_to_log'] = $lcTypeToLog;

        $issuedByUserName = "School Office"; // TODO: Get from actual user session
        $currentDateTime = date('Y-m-d H:i:s');

        // If you have $manualLcSrNo and the sr_no column in lc_issuance_log table:
        // $logSql = "INSERT INTO `lc_issuance_log` (`sr_no`, `student_db_id`, ...) VALUES (?, ?, ...)";
        // $bindTypes = "sisssssssss";
        // $paramsToBind = [$manualLcSrNo, $studentDbId, ...];
        // else (without sr_no):
        $logSql = "INSERT INTO `lc_issuance_log`
            (`student_db_id`, `student_name_at_issuance`, `lc_generation_datetime`,
             `issued_by_user_name`, `lc_type`, `lc_actual_leaving_date`,
             `lc_reason_for_leaving`, `lc_standard_when_leaving`,
             `lc_progress_at_leaving`, `lc_conduct_at_leaving`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // 10 placeholders
        $stmtLog = $conn->prepare($logSql);
        if (!$stmtLog) throw new Exception("LC log INSERT prepare failed: " . $conn->error);
        $response['debug_info']['lc_log_insert_sql'] = $logSql;

        // Values to bind
        $params_for_log_insert = [
            $studentDbId,
            $studentFullNameForLC,      // Name from form for this specific LC
            $currentDateTime,
            $issuedByUserName,
            $lcTypeToLog,
            $dbLeavingDateForLCLog,     // Leaving date from form for this specific LC
            $reasonForLeavingForLC,     // Reason from form for this specific LC
            $standardWhenLeavingForLC,  // Standard from form for this specific LC
            $academicProgressForLC,     // Progress from form for this specific LC
            $conductForLC               // Conduct from form for this specific LC
        ];
        $response['debug_info']['params_for_lc_log_insert'] = $params_for_log_insert;

        // Corrected bind_param: list variables individually
        $bindResult = $stmtLog->bind_param("isssssssss", // 1 'i', 9 's'
            $studentDbId, $studentFullNameForLC, $currentDateTime, $issuedByUserName, $lcTypeToLog,
            $dbLeavingDateForLCLog, $reasonForLeavingForLC, $standardWhenLeavingForLC,
            $academicProgressForLC, $conductForLC
        );
        if ($bindResult === false) {
             error_log("PHP LOG_LC_ISSUANCE: LC log BIND_PARAM FAILED: " . $stmtLog->error);
             throw new Exception("LC log bind_param failed: " . $stmtLog->error);
        }

        if (!$stmtLog->execute()) {
            $mysql_error = $stmtLog->error; $mysql_errno = $stmtLog->errno;
            error_log("PHP LOG_LC_ISSUANCE: LC log EXECUTE FAILED. MySQL Err: {$mysql_errno} - {$mysql_error}");
            throw new Exception("LC log insertion failed: " . $mysql_error . " | SQL ErrNo: " . $mysql_errno);
        }
        $newLogTcid = $stmtLog->insert_id;
        $stmtLog->close();

        $conn->commit();
        $response['status'] = 'success';
        $response['message'] = $lcTypeToLog . " LC recorded successfully with TCID: " . $newLogTcid;
        $response['tcid'] = $newLogTcid;
        $response['lc_type_issued'] = $lcTypeToLog;
        $response['debug_info'][] = "LC Logged. TCID: {$newLogTcid}, Type: {$lcTypeToLog}";
        error_log("PHP LOG_LC_ISSUANCE: Success. TCID: {$newLogTcid}, Type: {$lcTypeToLog}");

    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'LC Logging Transaction failed: ' . $e->getMessage();
        $response['debug_info'][] = 'Transaction ROLLED BACK for LC log: ' . $e->getMessage();
        error_log("PHP LOG_LC_ISSUANCE: Transaction Exception: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method for LC logging.';
}

if(isset($conn) && $conn instanceof mysqli) $conn->close();
echo json_encode($response);
?>