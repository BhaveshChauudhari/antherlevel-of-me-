<?php
// save_student_details_basic.php (Updates ONLY students table)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid request.'];

if (!$conn || $conn->connect_error) {
    $response['message'] = 'DB Connection Error';
    echo json_encode($response); exit;
}

// Basic CSRF Check Placeholder for POST requests
// session_start(); // If using session based CSRF
// if (empty($_POST['ci_csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['ci_csrf_token'] !== $_SESSION['csrf_token']) {
//    $response['message'] = 'CSRF Token Validation Failed.'; echo json_encode($response); exit;
// }
// unset($_SESSION['csrf_token']);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("PHP SAVE_STUDENT_BASIC: POST data: " . print_r($_POST, true));

    $studentDbId = isset($_POST['current_student_db_id']) ? trim($_POST['current_student_db_id']) : null;

    // --- Collect ALL data from POST meant for the 'students' table ---
    // <<== VERIFY THESE $_POST KEYS MATCH what JS sends in studentDataToSave for this button ==>>
    $udiseNumber = isset($_POST['udise_number_input']) ? trim($_POST['udise_number_input']) : null;
    $generalRegisterNo = isset($_POST['generalRegisterNo']) ? trim($_POST['generalRegisterNo']) : null;
    $studentFullName = isset($_POST['student_full_name']) ? trim($_POST['student_full_name']) : null;
    $fatherName = isset($_POST['father_name']) ? trim($_POST['father_name']) : null;
    $surname = isset($_POST['surname']) ? trim($_POST['surname']) : null;
    $motherFullName = isset($_POST['mother_full_name']) ? trim($_POST['mother_full_name']) : null;
    $nationality = isset($_POST['nationality']) ? trim($_POST['nationality']) : null;
    $motherTongue = isset($_POST['mother_tongue']) ? trim($_POST['mother_tongue']) : null;
    $religion = isset($_POST['religion']) ? trim($_POST['religion']) : null;
    $caste = isset($_POST['caste']) ? trim($_POST['caste']) : null;
    $subCaste = isset($_POST['sub_caste']) ? trim($_POST['sub_caste']) : null;
    $birthPlaceVillageCity = isset($_POST['birth_place_village_city']) ? trim($_POST['birth_place_village_city']) : null;
    $birthTaluka = isset($_POST['birth_taluka']) ? trim($_POST['birth_taluka']) : null;
    $birthDistrict = isset($_POST['birth_district']) ? trim($_POST['birth_district']) : null;
    $birthState = isset($_POST['birth_state']) ? trim($_POST['birth_state']) : null;
    $birthCountry = isset($_POST['birth_country']) ? trim($_POST['birth_country']) : null;
    $dobInput = isset($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : null;
    $dobInWords = isset($_POST['dob_in_words']) ? trim($_POST['dob_in_words']) : "";
    $previousSchool = isset($_POST['previous_school_and_standard']) ? trim($_POST['previous_school_and_standard']) : null;
    $admissionDateInput = isset($_POST['admission_date']) ? trim($_POST['admission_date']) : null;
    $admissionStandard = isset($_POST['admission_standard']) ? trim($_POST['admission_standard']) : null;
    $academicProgress = isset($_POST['academic_progress']) ? trim($_POST['academic_progress']) : null;
    $conduct = isset($_POST['conduct']) ? trim($_POST['conduct']) : null;
    $leavingDateInput = isset($_POST['leaving_date']) ? trim($_POST['leaving_date']) : null;
    $standardStudyingInAndSince = isset($_POST['standard_studying_in_and_since']) ? trim($_POST['standard_studying_in_and_since']) : null;
    $reasonForLeaving = isset($_POST['reason_for_leaving']) ? trim($_POST['reason_for_leaving']) : null;
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : null;

    if (empty($studentDbId) || !filter_var($studentDbId, FILTER_VALIDATE_INT)) {
        $response['message'] = 'Student Database ID is required for update.';
        echo json_encode($response); $conn->close(); exit;
    }
    $studentDbId = (int)$studentDbId;

    if (empty($studentFullName)) {
        $response['message'] = 'Student Full Name is required.';
        echo json_encode($response); $conn->close(); exit;
    }

    // Date Conversions (DDMMYYYY to YYYY-MM-DD for DB)
    $dbDob = null;
    if (!empty($dobInput)) { if (preg_match("/^(\d{2})(\d{2})(\d{4})$/", $dobInput, $m) && checkdate((int)$m[2],(int)$m[1],(int)$m[3])) $dbDob=$m[3].'-'.$m[2].'-'.$m[1]; else { $response['message']='Invalid DOB'; echo json_encode($response); exit;} }
    $dbAdmissionDate = null;
    if (!empty($admissionDateInput)) { if (preg_match("/^(\d{2})(\d{2})(\d{4})$/", $admissionDateInput, $m) && checkdate((int)$m[2],(int)$m[1],(int)$m[3])) $dbAdmissionDate=$m[3].'-'.$m[2].'-'.$m[1]; else { $response['message']='Invalid Admission Date'; echo json_encode($response); exit;} }
    $dbLeavingDate = null; // This will update students.leaving_certificate_date
    if (!empty($leavingDateInput)) { if (preg_match("/^(\d{2})(\d{2})(\d{4})$/", $leavingDateInput, $m) && checkdate((int)$m[2],(int)$m[1],(int)$m[3])) $dbLeavingDate=$m[3].'-'.$m[2].'-'.$m[1]; else { $response['message']='Invalid Leaving Date'; echo json_encode($response); exit;} }

    // Parse studentFullName for DB storage
    $nameParts = explode(' ', $studentFullName, 3); // Assuming "First Middle Last" or "First Last"
    $s_firstname = $nameParts[0] ?? null;
    // For students.middlename, use the dedicated father_name input
    $s_middlename_for_db = $fatherName;
    // For students.lastname, use the dedicated surname input
    $s_lastname_for_db = $surname;


    // --- Prepare SQL to UPDATE 'students' table ---
    // <<== VERIFY: List ALL columns that can be updated from your form. Remove any that don't exist in your table.
    // E.g., if 'udise_code' or 'dob_words' don't exist, remove them from SQL and bind_param.
    $sql = "UPDATE `students` SET
                `udise_code` = ?, `admission_no` = ?, `roll_no` = ?,
                `firstname` = ?, `middlename` = ?, `lastname` = ?, `mother_name` = ?,
                `nationality` = ?, `mother_tongue` = ?, `religion` = ?, `cast` = ?, `sub_caste_name` = ?,
                `birth_place` = ?, `birth_taluka` = ?, `birth_district` = ?, `birth_state_name` = ?, `birth_country` = ?,
                `dob` = ?, `dob_words` = ?, `previous_school` = ?, `admission_date` = ?, `admission_standard` = ?,
                `progress_report` = ?, `conduct_report` = ?, `leaving_certificate_date` = ?,
                `current_standard_info` = ?, `leaving_certificate_reason` = ?, `remarks` = ?
            WHERE `id` = ?"; // Update by Primary Key 'id'

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // <<== VERIFY: Type string ("s..." "i") must match the number of ? above (28 ? = 27 for SET + 1 for WHERE)
        // <<== VERIFY: Order of variables must match `?` in SQL
        $stmt->bind_param("ssssssssssssssssssssssssssi", // 27 's' then 1 'i' (if udise_code & dob_words are present)
                                                         // If you remove udise_code and dob_words, it becomes 25 's' and 1 'i'
            $udiseNumber,             // 1. IF udise_code column exists
            $generalRegisterNo,       // 2. for admission_no (if form's GRN updates admission_no)
            $generalRegisterNo,       // 3. for roll_no (if form's GRN also updates roll_no, or use a different var)
            $s_firstname,             // 4
            $s_middlename_for_db,     // 5
            $s_lastname_for_db,       // 6
            $motherFullName,          // 7
            $nationality,             // 8
            $motherTongue,            // 9
            $religion,                // 10
            $caste,                   // 11
            $subCaste,                // 12
            $birthPlaceVillageCity,   // 13
            $birthTaluka,             // 14
            $birthDistrict,           // 15
            $birthState,              // 16
            $birthCountry,            // 17
            $dbDob,                   // 18
            $dobInWords,              // 19. IF dob_words column exists
            $previousSchool,          // 20
            $dbAdmissionDate,         // 21
            $admissionStandard,       // 22
            $academicProgress,        // 23
            $conduct,                 // 24
            $dbLeavingDate,           // 25. Updates students.leaving_certificate_date
            $standardStudyingInAndSince,// 26. students.current_standard_info
            $reasonForLeaving,        // 27. students.leaving_certificate_reason
            $remarks,                 // 28
            $studentDbId              // 29. For WHERE id = ?
        );

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response = ['status' => 'success', 'message' => 'Student details updated successfully.'];
            } else {
                // Check if student ID actually exists to differentiate
                $checkExistStmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE id = ?");
                $checkExistStmt->bind_param("i", $studentDbId);
                $checkExistStmt->execute();
                $checkExistStmt->bind_result($count);
                $checkExistStmt->fetch();
                $checkExistStmt->close();
                if ($count > 0) {
                    $response = ['status' => 'success', 'message' => 'Student details submitted (no actual changes detected).'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Student ID not found, nothing updated.'];
                }
            }
        } else {
            $response['message'] = 'Database update execution failed: ' . $stmt->error . " (Errno: " . $stmt->errno . ")";
            error_log("PHP BASIC_SAVE (students): DB Execute Error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $response['message'] = 'SQL prepare failed for student update: ' . $conn->error . " SQL: " . $sql;
        error_log("PHP BASIC_SAVE (students): SQL Prepare Error: " . $conn->error);
    }
} else {
    $response['message'] = 'Invalid request method for saving details.';
}

if(isset($conn) && $conn instanceof mysqli) $conn->close();
echo json_encode($response);
?>