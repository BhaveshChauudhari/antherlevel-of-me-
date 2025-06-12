<?php
// save_all_student_data.php
ini_set('display_errors', 1); // DEBUG
ini_set('display_startup_errors', 1); // DEBUG
error_reporting(E_ALL); // DEBUG

require_once 'db_connection.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid save request (initial).'];
error_log("PHP SAVE ALL: Script accessed.");

if (!$conn || $conn->connect_error) {
    $response['message'] = 'Database connection failed.';
    error_log("PHP SAVE ALL: " . $response['message']);
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("PHP SAVE ALL: POST data received: " . print_r($_POST, true));

    // This 'unique_db_id' from JS holds the value of your 'students.id' (primary key)
    $studentPrimaryKey_Value = isset($_POST['unique_db_id']) ? trim($_POST['unique_db_id']) : null;

    if (empty($studentPrimaryKey_Value)) {
        $response['message'] = 'Student Primary ID (unique_db_id) missing. Cannot update.';
        error_log("PHP SAVE ALL: Error - " . $response['message']);
        echo json_encode($response); $conn->close(); exit;
    }

    // --- Map JS keys (from studentDataToSave JS object) to YOUR ACTUAL DB Column Names ---
    // These JS keys are what you defined in the studentDataToSave object in autofill_lc_form.js
    $jsKeyToDbColumnMap = [
        'udise_number'       => "udise_code", // Your DB column for UDISE, or "" if not exists
        'generalRegisterNo'  => "roll_no",    // DB column 'roll_no'
        'student_full_name'  => "full_name",  // Your DB column for the full name (e.g., 'full_name' or 'firstname' if you only save that part)
        'father_name'        => "middlename",   // Form's father_name maps to DB's middlename
        'surname'            => "lastname",
        'mother_full_name'   => "mother_name",
        'nationality'        => "nationality",
        'mother_tongue'      => "mother_tongue",
        'religion'           => "religion",
        'caste'              => "cast",
        'sub_caste'          => "sub_caste_name",
        'birth_place_village_city' => "birth_place", // Check DB for 'birth_palce' typo if you used that
        'birth_taluka'       => "birth_taluka",
        'birth_district'     => "birth_district",
        'birth_state'        => "birth_state_name",
        'birth_country'      => "birth_country",
        'date_of_birth'      => "dob",                // JS sends DDMMYYYY
        'dob_in_words'       => "dob_words",          // Your DB column for this, or ""
        'previous_school_and_standard' => "previous_school",
        'admission_date'     => "admission_date",     // JS sends DDMMYYYY
        'admission_standard' => "admission_standard",
        'academic_progress'  => "progress_report",
        'conduct'            => "conduct_report",
        'leaving_date'       => "leaving_certificate_date", // JS sends DDMMYYYY
        'standard_studying_in_and_since' => "current_standard_info",
        'reason_for_leaving' => "leaving_certificate_reason",
        'remarks'            => "remarks",
        // If JS sends updated studentIdBox or uidBox values:
        // 'student_id_from_form' => "admission_no",
        // 'uid_from_form'        => "adhar_no",
    ];

    $updateSetParts = [];
    $updateValues = [];
    $paramTypes = "";

    $tableName = "students";
    // Get existing columns to only try updating existing ones
    $stmtCheckCols = $conn->query("SHOW COLUMNS FROM `$tableName`");
    $actualTableColumns = [];
    if ($stmtCheckCols) { while($col = $stmtCheckCols->fetch_assoc()){ $actualTableColumns[] = $col['Field']; } $stmtCheckCols->free(); }
    else { /* error checking columns */ }

    foreach ($jsKeyToDbColumnMap as $jsKey => $dbColumn) {
        if (isset($_POST[$jsKey])) {
            if (empty($dbColumn) || !in_array($dbColumn, $actualTableColumns)) {
                error_log("PHP SAVE ALL: Skipping field '{$jsKey}' - DB column '{$dbColumn}' not configured or doesn't exist.");
                continue;
            }
            $value = trim($_POST[$jsKey]);
            // ** SERVER-SIDE VALIDATION for $value IS ESSENTIAL HERE **

            if (($jsKey === "date_of_birth" || $jsKey === "admission_date" || $jsKey === "leaving_date")) {
                if (!empty($value) && preg_match("/^(\d{2})(\d{2})(\d{4})$/", $value, $matches)) {
                    $value = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                } elseif (empty($value)) {
                    $value = null;
                } else { /* date validation error */ }
            }
            $updateSetParts[] = "`" . $dbColumn . "` = ?";
            $updateValues[] = $value;
            $paramTypes .= "s"; // Default to string
        }
    }

    if (empty($updateSetParts)) {
        $response = ['status' => 'info', 'message' => 'No updatable data fields received by server.'];
        error_log("PHP SAVE ALL: " . $response['message']);
        echo json_encode($response); $conn->close(); exit;
    }

    // --- IMPORTANT: Your actual primary key column name in 'students' table ---
    $primaryKeyColumnInDb = "id"; // Using 'id' (INT AUTO_INCREMENT PK) for the WHERE clause

    $sql = "UPDATE `$tableName` SET " . implode(", ", $updateSetParts) . " WHERE `$primaryKeyColumnInDb` = ?";
    error_log("PHP SAVE ALL: SQL: " . $sql);

    $updateValues[] = $studentPrimaryKey_Value; // Add the PK value for WHERE clause
    if ($primaryKeyColumnInDb === "id") { // If PK is 'id' and it's an INT
        $paramTypes .= "i";
        $studentPrimaryKey_Value = (int)$studentPrimaryKey_Value;
    } else {
        $paramTypes .= "s"; // If your PK was a string type
    }

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        error_log("PHP SAVE ALL: Binding params. Types: {$paramTypes}. Values for SET: " . count($updateSetParts) . ", WHERE ID: {$studentPrimaryKey_Value}");
        $stmt->bind_param($paramTypes, ...$updateValues);

        if ($stmt->execute()) {
            error_log("PHP SAVE ALL: Execute successful. Affected rows: " . $stmt->affected_rows);
            if ($stmt->affected_rows > 0) {
                $response = ['status' => 'success', 'message' => 'Student details saved successfully!'];
            } else {
                $response = ['status' => 'info', 'message' => 'No changes were made to details (or student ID not found).'];
            }
        } else {
            $response['message'] = 'Database update execution failed: ' . $stmt->error;
            error_log("PHP SAVE ALL: DB Execute Error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $response['message'] = 'DB query preparation failed for update: ' . $conn->error;
        error_log("PHP SAVE ALL: DB Prepare Error: " . $conn->error);
    }
} else { /* invalid request method */ }

if (isset($conn)) $conn->close();
echo json_encode($response);
?>