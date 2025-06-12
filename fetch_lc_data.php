<?php
// fetch_lc_data.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php';

header('Content-Type: application/json');
$response = [ /* ... initial response ... */ ];

if (!$conn || $conn->connect_error) { /* ... DB error ... */ }

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['identifier_type']) && isset($_POST['identifier_value'])) {
    $identifier_type = $_POST['identifier_type'];
    $identifier_value = trim($_POST['identifier_value']);

    if (empty($identifier_value) && !in_array($identifier_type, ['some_type_that_allows_empty'])) { /* ... error ... */ }

    // --- YOUR ACTUAL DATABASE COLUMN NAMES from 'students' table ---
    $db_col_primary_key = "id";
    $db_col_student_id_lookup = "admission_no";
    $db_col_uid_lookup          = "adhar_no";
    $db_col_name_lookup         = "firstname";
    $db_col_actual_firstname    = "firstname";
    $db_col_actual_middlename   = "middlename";
    $db_col_actual_lastname     = "lastname";
    $db_col_actual_mothername   = "mother_name";
    $db_col_roll_no             = "";
    $db_col_nationality         = "nationality";
    $db_col_mother_tongue       = "mother_tongue";
    $db_col_religion            = "religion";
    $db_col_caste               = "cast";
    $db_col_sub_caste           = "sub_caste_name";
    $db_col_birth_place         = "birth_place";
    $db_col_birth_taluka        = "birth_taluka";
    $db_col_birth_district      = "birth_district";
    $db_col_birth_state         = "birth_state_name";
    $db_col_birth_country       = "birth_country";
    $db_col_dob                 = "dob";
    // ***** THIS IS THE FIX *****
    $db_col_dob_words           = ""; // Assuming 'dob_words' column does NOT exist in your 'students' table
    // ***** END OF FIX *****
    $db_col_prev_school_std     = "previous_school";
    $db_col_admission_date      = "admission_date";
    $db_col_admission_std       = "admission_standard";
    $db_col_acad_progress       = "progress_report";
    $db_col_conduct             = "conduct_report";
    $db_col_leaving_date        = "leaving_certificate_date";
    $db_col_current_std_info    = "current_standard_info";
    $db_col_leaving_reason      = "leaving_certificate_reason";
    $db_col_remarks             = "remarks";
    $db_col_udise               = ""; // Corrected previously to empty or actual if exists

    $tableName = "students";

    $jsKeyToDbColMap = [
        'unique_student_identifier' => $db_col_primary_key,
        'student_id_display_value'  => $db_col_student_id_lookup,
        'uid_display_value'         => $db_col_uid_lookup,
        'internal_firstname'    => $db_col_actual_firstname,
        'internal_middlename'   => $db_col_actual_middlename,
        'internal_lastname'     => $db_col_actual_lastname,
        'generalRegisterNo'     => $db_col_roll_no,
        'father_name'           => $db_col_actual_middlename,
        'surname'               => $db_col_actual_lastname,
        'mother_full_name'      => $db_col_actual_mothername,
        'nationality'           => $db_col_nationality,
        'mother_tongue'         => $db_col_mother_tongue,
        'religion'              => $db_col_religion,
        'caste'                 => $db_col_caste,
        'sub_caste'             => $db_col_sub_caste,
        'birth_place_village_city'=> $db_col_birth_place,
        'birth_taluka'          => $db_col_birth_taluka,
        'birth_district'        => $db_col_birth_district,
        'birth_state'           => $db_col_birth_state,
        'birth_country'         => $db_col_birth_country,
        'date_of_birth'         => $db_col_dob,
        'dob_in_words'          => $db_col_dob_words, // This will now correctly map to an empty string if $db_col_dob_words is ""
        'previous_school_and_standard'=> $db_col_prev_school_std,
        'admission_date'        => $db_col_admission_date,
        'admission_standard'    => $db_col_admission_std,
        'academic_progress'     => $db_col_acad_progress,
        'conduct'               => $db_col_conduct,
        'leaving_date'          => $db_col_leaving_date,
        'standard_studying_in_and_since' => $db_col_current_std_info,
        'reason_for_leaving'    => $db_col_leaving_reason,
        'remarks'               => $db_col_remarks,
        'udise_number'          => $db_col_udise,
    ];

    // ... (rest of the script: $selectSQLParts generation, switch for $where_column_name, SQL execution,
    //      while loop for fetching rows, LC Issuance Status check, and echo json_encode($response)) ...
    // The logic for building $selectSQLParts will now correctly exclude `dob_words` if $db_col_dob_words is empty.
    // The population loop will set $current_student_data_for_js['dob_in_words'] = '' if $db_col_dob_words is empty.

    // For brevity, I'm pasting the relevant parts that use $db_col_dob_words.
    // The rest of the file remains the same as the complete version I provided before.

    $selectSQLParts = [];
    foreach ($jsKeyToDbColMap as $jsKey => $dbColNameString) {
        if (!empty($dbColNameString) && !in_array("`".$conn->real_escape_string($dbColNameString)."`", $selectSQLParts)) {
            // Only add to SELECT if $dbColNameString (the DB column name) is not empty
            $selectSQLParts[] = "`".$conn->real_escape_string($dbColNameString)."`";
        }
    }
    // ... (ensure essential cols are added if not already in map) ...
    $selectClause = implode(", ", array_unique($selectSQLParts));
    // ... (rest of query building) ...

    // Inside the while loop:
    // foreach ($jsKeyToDbColMap as $jsKey => $dbColNameString) {
    //     if (!empty($dbColNameString) && array_key_exists($dbColNameString, $row)) {
    //         // ...
    //     } else {
    //         // If $dbColNameString was empty (like $db_col_dob_words = ""), this key will get ''
    //         $current_student_data_for_js[$jsKey] = '';
    //     }
    // }
    // The complete loop from the previous full file is correct.
    // Just ensure $db_col_dob_words = ""; is set at the top if the column doesn't exist.

    // The rest of the file (from the full version provided previously) follows...
    // Make sure to copy the entire correct structure. For brevity, I'm only highlighting the change area.
    // The following is the remainder of the file structure after $jsKeyToDbColMap

    $essentialCols = [$db_col_primary_key, $db_col_actual_firstname, $db_col_actual_middlename, $db_col_actual_lastname];
    foreach ($essentialCols as $col) {
        if (!empty($col) && !in_array("`".$conn->real_escape_string($col)."`", $selectSQLParts)) {
            $selectSQLParts[] = "`".$conn->real_escape_string($col)."`";
        }
    }
    $selectSQLParts = array_unique($selectSQLParts);
    $selectClause = implode(", ", $selectSQLParts);

    if (empty($selectClause)) { /* ... error handling as before ... */ }

    $where_column_name = "";
    switch ($identifier_type) {
        case 'student_id': $where_column_name = $db_col_student_id_lookup; break;
        case 'uid':        $where_column_name = $db_col_uid_lookup; break;
        case 'name':       $where_column_name = $db_col_name_lookup; break;
        case 'primary_key':$where_column_name = $db_col_primary_key; break;
        default: $response = ['status' => 'error', 'message' => 'Invalid identifier type.']; echo json_encode($response); $conn->close(); exit;
    }
    if (empty($where_column_name)) { /* ... error handling as before ... */ }

    $limitClause = ($identifier_type === 'name') ? "LIMIT 20" : "LIMIT 1";
    $sql = "SELECT $selectClause FROM `$tableName` WHERE `$where_column_name` = ? $limitClause";
    error_log("PHP FETCH SQL (dob_words fix): " . $sql . " (Value: " . $identifier_value . ")");
    $stmt = $conn->prepare($sql); // Line 176 in your error log for the previous version

    if ($stmt) {
        $stmt->bind_param("s", $identifier_value);
        $stmt->execute();
        $result = $stmt->get_result();
        $fetched_students_array = [];
        $response['debug_info']['num_rows_from_db'] = ($result ? $result->num_rows : 'Result false');


        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $current_student_data_for_js = [];
                foreach ($jsKeyToDbColMap as $jsKey => $dbColNameString) {
                    // If $dbColNameString is empty (e.g. for dob_words if column doesn't exist),
                    // $row[$dbColNameString] would be an error.
                    // So, we check if $dbColNameString is not empty before using it as an array key for $row.
                    if (!empty($dbColNameString) && array_key_exists($dbColNameString, $row)) {
                        if (in_array($jsKey, ['date_of_birth', 'admission_date', 'leaving_date'])) {
                            $current_student_data_for_js[$jsKey] = $row[$dbColNameString] ? date("dmY", strtotime($row[$dbColNameString])) : '';
                        } else {
                            $current_student_data_for_js[$jsKey] = $row[$dbColNameString];
                        }
                    } else {
                        // Ensure key exists in JS object, set to empty if DB column not mapped or no value
                        $current_student_data_for_js[$jsKey] = '';
                    }
                }
                $current_student_data_for_js['unique_student_identifier'] = $row[$db_col_primary_key] ?? '';
                $sfn_firstname  = $row[$db_col_actual_firstname] ?? '';
                $sfn_middlename = $row[$db_col_actual_middlename] ?? '';
                $sfn_lastname   = $row[$db_col_actual_lastname] ?? '';
                $current_student_data_for_js['student_full_name'] = trim("$sfn_firstname $sfn_middlename $sfn_lastname");

                // --- FETCH LC ISSUANCE STATUS ---
                // (Keep the LC Issuance Status block from the previous complete version)
                $student_db_id_for_log_check = $row[$db_col_primary_key] ?? null;
                $lc_issuance_status = ['next_lc_type' => 'Original', 'issued_lc_count' => 0, 'original_issued_date' => null];
                if (!empty($student_db_id_for_log_check)) {
                    // ... (the rest of the lc_issuance_log query logic) ...
                     $stmt_lc_check = $conn->prepare(
                        "SELECT COUNT(*) as lc_count, MIN(CASE WHEN lc_type = 'Original' THEN lc_generation_datetime ELSE NULL END) as original_date
                         FROM lc_issuance_log WHERE student_db_id = ?"
                    );
                    if ($stmt_lc_check) {
                        $stmt_lc_check->bind_param("i", $student_db_id_for_log_check);
                        $stmt_lc_check->execute();
                        $result_lc_check = $stmt_lc_check->get_result();
                        if ($log_summary = $result_lc_check->fetch_assoc()) {
                            $lc_issuance_status['issued_lc_count'] = (int)$log_summary['lc_count'];
                            if ($log_summary['original_date']) {
                                $lc_issuance_status['next_lc_type'] = 'Duplicate';
                                try { $orig_date_obj = new DateTime($log_summary['original_date']);
                                      $lc_issuance_status['original_issued_date'] = $orig_date_obj->format('d-M-Y H:i');
                                } catch (Exception $e) { $lc_issuance_status['original_issued_date'] = 'Invalid Date'; error_log("FETCH_LC_DATA: DateTime Error for original_date: ".$e->getMessage());}
                            } else if ($lc_issuance_status['issued_lc_count'] > 0) {
                                $lc_issuance_status['next_lc_type'] = 'Duplicate';
                            }
                        }
                        $stmt_lc_check->close();
                    } else { error_log("PHP FETCH: LC log check prepare failed: " . $conn->error); }
                }
                $current_student_data_for_js['lc_issuance_info'] = $lc_issuance_status;

                $fetched_students_array[] = $current_student_data_for_js;
            }
            $response['status'] = 'success';
            $response['data'] = $fetched_students_array;
        } else {
            $response['status'] = 'not_found';
            $response['message'] = "No student found with {$identifier_type}: '{$identifier_value}' using DB column '{$where_column_name}'.";
        }
        if ($stmt) $stmt->close();
    } else {
        $response['status'] = 'error';
        $response['message'] = 'SQL statement preparation failed: ' . $conn->error . ' (Query was: ' . $sql .')';
        error_log("PHP FETCH: SQL Prepare Error: " . $conn->error . ". SQL: " . $sql);
    }
} else {
    // ... (invalid request method / missing params handling as before) ...
    $response['message'] = 'Invalid request method or missing POST parameters.';
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $missing_params = [];
        if (!isset($_POST['identifier_type'])) $missing_params[] = 'identifier_type';
        if (!isset($_POST['identifier_value'])) $missing_params[] = 'identifier_value';
        if (!empty($missing_params)) {
            $response['message'] = 'Missing POST parameters: ' . implode(', ', $missing_params);
        }
    }
    $response['debug_info'][] = $response['message'];
}

if (isset($conn) && $conn instanceof mysqli) { $conn->close(); }
echo json_encode($response);
?>