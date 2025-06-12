<?php
// save_student_data_and_process_lc.php (Corrected bind_param for LC Log)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php';

header('Content-Type: application/json');
$response = [
    'status' => 'error', 'message' => 'Invalid request (initial).',
    'student_save_status' => 'not_attempted',
    'lc_issue_status' => 'not_attempted',
    'tcid' => null, 'lc_type_issued' => null,
    'debug_info' => [] // For detailed debugging feedback
];

if (!$conn) {
    $response['message'] = 'Database connection object not established.';
    $response['debug_info'][] = 'DB Connection: Object not established.';
    error_log("PHP SAVE_&_ISSUE_LC: DB Connection Error - No object");
    echo json_encode($response); exit;
}
if ($conn->connect_error) {
    $response['message'] = 'Database connection failed: ' . $conn->connect_error;
    $response['debug_info'][] = 'DB Connection: Failed - ' . $conn->connect_error;
    error_log("PHP SAVE_&_ISSUE_LC: DB Connection Error - " . $conn->connect_error);
    echo json_encode($response); exit;
}
$response['debug_info'][] = "DB Connection OK.";

// Basic CSRF Check Placeholder (Your framework likely handles this)
// session_start();
// if (empty($_POST['ci_csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['ci_csrf_token'] !== $_SESSION['csrf_token']) {
//    $response['message'] = 'CSRF Token Validation Failed.'; echo json_encode($response); exit;
// }
// unset($_SESSION['csrf_token']); // If one-time


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $response['debug_info']['post_data'] = $_POST;
    error_log("PHP SAVE_&_ISSUE_LC: POST data received: " . print_r($_POST, true));

    $studentDbId = isset($_POST['current_student_db_id']) ? trim($_POST['current_student_db_id']) : null;
    $response['debug_info']['received_pk_id'] = $studentDbId;

    if (empty($studentDbId) || !filter_var($studentDbId, FILTER_VALIDATE_INT)) {
        $response['message'] = 'Critical Error: Student Database ID (PK) missing or invalid.';
        $response['student_save_status'] = 'error_pk_missing';
        $response['lc_issue_status'] = 'error_pk_missing';
        echo json_encode($response); $conn->close(); exit;
    }
    $studentDbId = (int)$studentDbId;

    // --- Data from Form (ensure JS sends all these keys) ---
    $udiseNumber = isset($_POST['udise_number_input']) ? trim($_POST['udise_number_input']) : null;
    $generalRegisterNo = isset($_POST['generalRegisterNo']) ? trim($_POST['generalRegisterNo']) : null;
    $studentFullNameFromForm = isset($_POST['student_full_name']) ? trim($_POST['student_full_name']) : null;
    $fatherNameFromForm = isset($_POST['father_name']) ? trim($_POST['father_name']) : null;
    $surnameFromForm = isset($_POST['surname']) ? trim($_POST['surname']) : null;
    $motherFullNameFromForm = isset($_POST['mother_full_name']) ? trim($_POST['mother_full_name']) : null;
    $nationalityFromForm = isset($_POST['nationality']) ? trim($_POST['nationality']) : null;
    $motherTongueFromForm = isset($_POST['mother_tongue']) ? trim($_POST['mother_tongue']) : null;
    $religionFromForm = isset($_POST['religion']) ? trim($_POST['religion']) : null;
    $casteFromForm = isset($_POST['caste']) ? trim($_POST['caste']) : null;
    $subCasteFromForm = isset($_POST['sub_caste']) ? trim($_POST['sub_caste']) : null;
    $birthPlaceVillageCityFromForm = isset($_POST['birth_place_village_city']) ? trim($_POST['birth_place_village_city']) : null;
    $birthTalukaFromForm = isset($_POST['birth_taluka']) ? trim($_POST['birth_taluka']) : null;
    $birthDistrictFromForm = isset($_POST['birth_district']) ? trim($_POST['birth_district']) : null;
    $birthStateFromForm = isset($_POST['birth_state']) ? trim($_POST['birth_state']) : null;
    $birthCountryFromForm = isset($_POST['birth_country']) ? trim($_POST['birth_country']) : null;
    $dobInput = isset($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : null;
    $dobInWordsFromForm = isset($_POST['dob_in_words']) ? trim($_POST['dob_in_words']) : "";
    $previousSchoolFromForm = isset($_POST['previous_school_and_standard']) ? trim($_POST['previous_school_and_standard']) : null;
    $admissionDateInput = isset($_POST['admission_date']) ? trim($_POST['admission_date']) : null;
    $admissionStandardFromForm = isset($_POST['admission_standard']) ? trim($_POST['admission_standard']) : null;
    $academicProgressForLC = isset($_POST['academic_progress']) ? trim($_POST['academic_progress']) : null;
    $conductForLC = isset($_POST['conduct']) ? trim($_POST['conduct']) : null;
    $leavingDateInput = isset($_POST['leaving_date']) ? trim($_POST['leaving_date']) : null;
    $standardWhenLeavingForLC = isset($_POST['standard_studying_in_and_since']) ? trim($_POST['standard_studying_in_and_since']) : null;
    $reasonForLeavingForLC = isset($_POST['reason_for_leaving']) ? trim($_POST['reason_for_leaving']) : null;
    $remarksForStudentTable = isset($_POST['remarks']) ? trim($_POST['remarks']) : null;
    // $manualLcSrNo = isset($_POST['manual_lc_sr_no']) ? trim($_POST['manual_lc_sr_no']) : null; // If using for lc_issuance_log.sr_no

    if (empty($studentFullNameFromForm)) { /* ... error ... */ }

    $dbLeavingDateForRecord = null;
    if (!empty($leavingDateInput)) {
        if (preg_match("/^(\d{2})(\d{2})(\d{4})$/", $leavingDateInput, $m) && checkdate((int)$m[2],(int)$m[1],(int)$m[3])) {
            $dbLeavingDateForRecord = $m[3].'-'.$m[2].'-'.$m[1];
        } else { /* ... error response for invalid leaving date ... */ }
    }
    $response['debug_info']['parsed_leaving_date_for_processing'] = $dbLeavingDateForRecord;
    // ... (Convert other dates like $dbDob, $dbAdmissionDate for students table update) ...
    $dbDob = null; /* ... convert $_POST['date_of_birth'] ... */
    $dbAdmissionDate = null; /* ... convert $_POST['admission_date'] ... */

    $conn->begin_transaction();
    $response['debug_info'][] = 'DB Transaction Started.';
    try {
        // --- TASK 1: Update the 'students' table ---
        $nameParts = explode(' ', $studentFullNameFromForm ?? '', 3);
        $s_firstname = $nameParts[0] ?? '';
        $s_middlename_for_db = $fatherNameFromForm;
        $s_lastname_for_db = $surnameFromForm;

        // <<== YOUR FULL, WORKING `UPDATE students SET ... WHERE id = ?` SQL GOES HERE ==>>
        // Remove columns from SET if they don't exist (e.g., udise_code, dob_words)
        // and adjust bind_param string and variables accordingly.
        $updateStudentSQL = "UPDATE `students` SET
            `admission_no` = ?, `roll_no` = ?, /* Or however you map generalRegisterNo */
            `firstname` = ?, `middlename` = ?, `lastname` = ?, `mother_name` = ?,
            `nationality` = ?, `mother_tongue` = ?, `religion` = ?, `cast` = ?, `sub_caste_name` = ?,
            `birth_place` = ?, `birth_taluka` = ?, `birth_district` = ?, `birth_state_name` = ?, `birth_country` = ?,
            `dob` = ?, /* `dob_words` = ?, -- REMOVE if no such column */ `previous_school` = ?,
            `admission_date` = ?, `admission_standard` = ?,
            `progress_report` = ?, `conduct_report` = ?, `leaving_certificate_date` = ?,
            `current_standard_info` = ?, `leaving_certificate_reason` = ?, `remarks` = ?
            /* Add `udise_code` = ? here if that column exists AND you want to update it */
            WHERE `id` = ?";
        $stmtUpdateStudent = $conn->prepare($updateStudentSQL);
        if (!$stmtUpdateStudent) throw new Exception("Students table update prepare failed: " . $conn->error);

        // <<== ADJUST bind_param string and variables to match your SQL above ==>>
        // Example for the SQL above (assuming dob_words and udise_code are removed): 26 SET fields + 1 WHERE id
        // This would be 26 's' (or other types) followed by 1 'i'.
        $stmtUpdateStudent->bind_param("ssssssssssssssssssssssssssi", // 26 's' and 1 'i' (count carefully!)
            $generalRegisterNo, // for students.admission_no
            $generalRegisterNo, // for students.roll_no (if same, or use different POST var)
            $s_firstname, $s_middlename_for_db, $s_lastname_for_db, $motherFullNameFromForm,
            $nationalityFromForm, $motherTongueFromForm, $religionFromForm, $casteFromForm, $subCasteFromForm,
            $birthPlaceVillageCityFromForm, $birthTalukaFromForm, $birthDistrictFromForm, $birthStateFromForm, $birthCountryFromForm,
            $dbDob, /* $dobInWordsFromForm, // REMOVED */ $previousSchoolFromForm,
            $dbAdmissionDate, $admissionStandardFromForm,
            $academicProgressForLC, // This updates students.progress_report
            $conductForLC,          // This updates students.conduct_report
            $dbLeavingDateForRecord,  // This updates students.leaving_certificate_date
            $standardWhenLeavingForLC, // This updates students.current_standard_info
            $reasonForLeavingForLC,    // This updates students.leaving_certificate_reason
            $remarksForStudentTable,
            $studentDbId // For WHERE id = ?
        );

        if (!$stmtUpdateStudent->execute()) {
            throw new Exception("Students table update execution failed: " . $stmtUpdateStudent->error . " (Errno: ".$stmtUpdateStudent->errno.")");
        }
        $response['student_save_status'] = ($stmtUpdateStudent->affected_rows > 0) ? 'success_changed' : 'success_no_changes';
        $response['message'] = "Student details processed.";
        $response['debug_info'][] = "Students table updated for ID {$studentDbId}. Affected: " . $stmtUpdateStudent->affected_rows;
        $stmtUpdateStudent->close();

        // --- TASK 2: LC Issuance Logic ---
        $response['debug_info'][] = "LC Logging Check: dbLeavingDateForRecord is " . ($dbLeavingDateForRecord === null ? "NULL" : $dbLeavingDateForRecord);
        if ($response['status'] !== 'error' && $dbLeavingDateForRecord !== null) {
            $response['debug_info'][] = "Condition MET for LC Issuance logging.";
            error_log("PHP SAVE_&_ISSUE_LC: Condition MET for LC Issuance logging.");

            $stmtCheckOriginal = $conn->prepare("SELECT COUNT(*) FROM `lc_issuance_log` WHERE `student_db_id` = ? AND `lc_type` = 'Original'");
            if (!$stmtCheckOriginal) throw new Exception("LC type check prepare failed: " . $conn->error);
            $stmtCheckOriginal->bind_param("i", $studentDbId);
            $stmtCheckOriginal->execute();
            $stmtCheckOriginal->bind_result($originalLCCount);
            $stmtCheckOriginal->fetch();
            $stmtCheckOriginal->close();
            $lcTypeToLog = ($originalLCCount == 0) ? 'Original' : 'Duplicate';
            $response['lc_type_issued'] = $lcTypeToLog;
            $response['debug_info']['determined_lc_type_for_log'] = $lcTypeToLog;
            error_log("PHP SAVE_&_ISSUE_LC: Determined LC Type: " . $lcTypeToLog);

            $issuedByUserName = "School Office"; // TODO: Replace with actual logged-in username from session
            $currentDateTime = date('Y-m-d H:i:s');

            $logSql = "INSERT INTO `lc_issuance_log`
                (`student_db_id`, `student_name_at_issuance`, `lc_generation_datetime`,
                 `issued_by_user_name`, `lc_type`, `lc_actual_leaving_date`,
                 `lc_reason_for_leaving`, `lc_standard_when_leaving`,
                 `lc_progress_at_leaving`, `lc_conduct_at_leaving`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // 10 placeholders
            $stmtLog = $conn->prepare($logSql);
            if (!$stmtLog) throw new Exception("LC log insert prepare failed: " . $conn->error . " | SQL: " . $logSql);

            $response['debug_info']['lc_log_sql_prepared'] = $logSql;
            // These variables are already defined from $_POST at the top
            $response['debug_info']['lc_log_params_to_bind'] = [
                $studentDbId, $studentFullNameFromForm, $currentDateTime, $issuedByUserName, $lcTypeToLog,
                $dbLeavingDateForRecord, $reasonForLeavingForLC, $standardWhenLeavingForLC,
                $academicProgressForLC, $conductForLC
            ];
            error_log("PHP SAVE_&_ISSUE_LC: Params for LC Log: " . print_r($response['debug_info']['lc_log_params_to_bind'], true));

            // ***** CORRECTED bind_param FOR LC LOG - NO SPREAD OPERATOR *****
            $bindResult = $stmtLog->bind_param("isssssssss",
                $studentDbId,
                $studentFullNameFromForm,     // student_name_at_issuance
                $currentDateTime,             // lc_generation_datetime
                $issuedByUserName,            // issued_by_user_name
                $lcTypeToLog,                 // lc_type
                $dbLeavingDateForRecord,      // lc_actual_leaving_date
                $reasonForLeavingForLC,       // lc_reason_for_leaving
                $standardWhenLeavingForLC,    // lc_standard_when_leaving
                $academicProgressForLC,       // lc_progress_at_leaving
                $conductForLC                 // lc_conduct_at_leaving
            );
            // ***** END CORRECTION *****

            if ($bindResult === false) { // Check if bind_param failed
                 error_log("PHP SAVE_&_ISSUE_LC: LC log BIND_PARAM FAILED: " . $stmtLog->error);
                 throw new Exception("LC log bind_param failed: " . $stmtLog->error);
            }

            if (!$stmtLog->execute()) {
                $mysql_error = $stmtLog->error; $mysql_errno = $stmtLog->errno;
                error_log("PHP SAVE_&_ISSUE_LC: LC log insertion EXECUTE FAILED. MySQL Error: {$mysql_errno} - {$mysql_error}");
                throw new Exception("LC log insertion failed: " . $mysql_error . " | SQL Error No: " . $mysql_errno);
            }
            $newLogTcid = $stmtLog->insert_id;
            $stmtLog->close();

            $response['lc_issue_status'] = 'success';
            $response['tcid'] = $newLogTcid;
            $response['message'] .= " And " . $lcTypeToLog . " LC (TCID: " . $newLogTcid . ") recorded.";
            $response['debug_info'][] = "LC Logged successfully. TCID: {$newLogTcid}";
            error_log("PHP SAVE_&_ISSUE_LC: LC Logged successfully. TCID: {$newLogTcid}");
        } else if ($response['status'] !== 'error') {
            $response['lc_issue_status'] = 'skipped_no_leaving_date';
            $response['message'] .= " (LC not issued: leaving date empty/invalid).";
            $response['debug_info'][] = "LC issuance skipped: dbLeavingDateForRecord is null or invalid.";
            error_log("PHP SAVE_&_ISSUE_LC: LC issuance skipped: dbLeavingDateForRecord is null or invalid.");
        }
        $conn->commit();
        if ($response['status'] !== 'error') $response['status'] = 'success';
        $response['debug_info'][] = 'Transaction committed.';
    } catch (Exception $e) {
        $conn->rollback();
        $response['status'] = 'error'; $response['message'] = 'Transaction failed: ' . $e->getMessage();
        $response['debug_info'][] = 'Transaction ROLLED BACK: ' . $e->getMessage();
        if (strpos(strtolower($e->getMessage()), "lc log") !== false) $response['lc_issue_status'] = 'error_during_lc_log';
        elseif (strpos(strtolower($e->getMessage()), "student") !== false) $response['student_save_status'] = 'error';
        error_log("PHP SAVE_&_ISSUE_LC: Transaction Exception - " . $e->getMessage());
    }
} else { $response['message'] = 'Invalid request method.'; }
if (isset($conn) && $conn instanceof mysqli) { $conn->close(); }
echo json_encode($response);
?>