// lc_issuance_actions.js
$(document).ready(function () {
    console.log("LC Issuance Actions JS: Document ready. V1.1 - Added displayStatusLocal.");

    // --- Selectors for this script's specific elements ---
    const issueAndLogLcButtonEl = $('#issueAndLogLcBtn');       // Your NEW LC Issue Button ID
    const issueLcLogStatusEl = $('#issueLcLogStatus');          // Its Status Div ID
    const printableLcTypeIndicatorContainerEl = $('#printableLcTypeIndicatorContainer');
    const printableLcTypeIndicatorEl = $('#printableLcTypeIndicator');
    const currentStudentDbIdField = $('#current_student_db_id'); // From main HTML
    const studentFullNameInput = $('#student_full_name');      // From main HTML

    // --- Helper Function: displayStatusLocal ---
    // (Copied from autofill_lc_form.js or your common utility)
    function displayStatusLocal(element, message, type, autoClearDelay = 0) {
        if (element && element.length) {
            let typeClass = 'status-info'; // Default class
            let cssColor = '#6c757d';    // Default color
            switch(type) {
                case 'loading': typeClass = 'status-loading'; cssColor = '#007bff'; break;
                case 'success': typeClass = 'status-success'; cssColor = '#28a745'; break;
                case 'error':   typeClass = 'status-error';   cssColor = '#dc3545'; break;
                // 'info' will use the defaults
            }
            element.removeClass('status-loading status-success status-error status-info').addClass(typeClass);
            element.text(message).css('color', cssColor); // Also set color directly for immediate feedback
            if (autoClearDelay > 0) {
                setTimeout(() => {
                    element.text('').removeClass(typeClass).css('color', ''); // Clear color too
                }, autoClearDelay);
            }
        }
    }
    // --- End Helper Function ---

    // Helper: getMultiBoxValue might be needed if not global
    // If it's defined globally in autofill_lc_form.js (e.g., window.getMultiBoxValue), you can use that.
    // Otherwise, copy it here too for self-containment.
    function getMultiBoxValue(baseId, count) {
        let value = "";
        for (let i = 1; i <= count; i++) {
            const element = $(`#${baseId}${i}`);
            if (element.length) { value += element.val().trim(); }
        }
        return value;
    }


    // Update "Issue LC" button appearance based on context from autofill script's event
    $(document).on('lc-context-has-updated', function(event, studentData) {
        console.log("LC Issuance JS: Received 'lc-context-has-updated'. Student Data:", studentData);
        let issueButtonText = "एलसी जारी करा आणि नोंद करा (Issue & Log LC)";
        let issueButtonBgColor = '#6c757d'; // Default disabled color
        let issueButtonTextColor = '#ffffff';
        let issueButtonDisabled = true;

        if (studentData && studentData.unique_student_identifier && studentData.lc_issuance_info) {
            issueButtonDisabled = false;
            const lcInfo = studentData.lc_issuance_info;
            if (lcInfo.next_lc_type === 'Original') {
                issueButtonText = "मूळ एलसी जारी करा (Issue Original LC)"; issueButtonBgColor = '#28a745';
            } else { // Duplicate
                issueButtonText = "डुप्लिकेट एलसी जारी करा (Issue Duplicate LC)"; issueButtonBgColor = '#ffc107'; issueButtonTextColor = '#212529';
            }
        } else if (studentData && studentData.unique_student_identifier) { // Student loaded, but no LC info
             issueButtonDisabled = false; issueButtonText = "एलसी जारी करण्याचा प्रयत्न करा (Attempt Issue LC)"; issueButtonBgColor = '#17a2b8';
        } else { // No student loaded
            issueButtonText = "लोड विद्यार्थी (LC साठी)";
        }

        if (issueAndLogLcButtonEl.length) {
            issueAndLogLcButtonEl.text(issueButtonText)
                .css({'background-color': issueButtonBgColor, 'color': issueButtonTextColor})
                .prop('disabled', issueButtonDisabled);
        }
    });


    if (issueAndLogLcButtonEl.length) {
        issueAndLogLcButtonEl.off('click').on('click', function() { // Used .off('click') to prevent multiple bindings
            console.log("JS ISSUE_LC_LOG_BTN: Click handler initiated."); // Changed log from V1.5 for clarity
            displayStatusLocal(issueLcLogStatusEl, 'Initiating LC log...', 'info', 2000); // Test displayStatusLocal

            const studentDbId = currentStudentDbIdField.val();
            console.log("JS ISSUE_LC_LOG_BTN: Student DB ID from hidden field:", studentDbId);


            if (!studentDbId) {
                // Using displayStatusLocal here
                displayStatusLocal(issueLcLogStatusEl, 'No student loaded (current_student_db_id is empty). Fetch first.', 'error', 4000);
                console.error("JS ISSUE_LC_LOG_BTN: current_student_db_id value is empty or null.");
                return;
            }

            const lcLogDataPayload = {
                current_student_db_id: studentDbId,
                student_full_name: studentFullNameInput.val().trim(),
                leaving_date: getMultiBoxValue('leavingDateBox', 8),
                reason_for_leaving: $('#reason_for_leaving').val().trim(),
                standard_studying_in_and_since: $('#standard_studying_in_and_since').val().trim(),
                academic_progress: $('#academic_progress').val().trim(),
                conduct: $('#conduct').val().trim(),
                // manual_lc_sr_no: $('#manual_lc_sr_no').val().trim(), // If you use this
            };
            console.log("JS ISSUE_LC_LOG_BTN: lcLogDataPayload before CSRF:", JSON.stringify(lcLogDataPayload));


            if (!lcLogDataPayload.leaving_date || lcLogDataPayload.leaving_date.trim().length !== 8) {
                alert("Leaving Date is required to issue an LC!");
                displayStatusLocal(issueLcLogStatusEl, 'Leaving Date required.', 'error', 4000);
                return;
            }

            let confirmMsg = "Are you sure you want to issue and log this LC?";
            // Accessing shared data from the other JS file. Make sure autofill_lc_form.js sets this.
            if (window.LC_APP_SHARED_DATA && window.LC_APP_SHARED_DATA.currentLoadedStudent && window.LC_APP_SHARED_DATA.currentLoadedStudent.lc_issuance_info) {
                 confirmMsg = `Are you sure you want to issue ${window.LC_APP_SHARED_DATA.currentLoadedStudent.lc_issuance_info.next_lc_type || 'New'} LC?`;
            } else {
                console.warn("JS ISSUE_LC_LOG_BTN: LC_APP_SHARED_DATA.currentLoadedStudent.lc_issuance_info not found. Using generic confirm.");
            }

            if (!confirm(confirmMsg)) {
                displayStatusLocal(issueLcLogStatusEl, 'LC issuance cancelled.', 'info', 3000);
                return;
            }

            // --- Add CSRF Token ---
            let csrfTokenName = 'ci_csrf_token'; // <<== VERIFY THIS NAME from your HTML
            let csrfTokenValue = $('input[name="' + csrfTokenName + '"]').val();
            if (!csrfTokenValue) {
                console.warn('JS ISSUE_LC_LOG_BTN: CSRF token (' + csrfTokenName + ') not found or empty. Request might be rejected.');
                // Depending on server strictness, you might want to 'return;' here.
            }
            if (csrfTokenValue) {
                lcLogDataPayload[csrfTokenName] = csrfTokenValue;
            }
            console.log("JS ISSUE_LC_LOG_BTN: Data to send to log_lc_issuance.php:", JSON.stringify(lcLogDataPayload));

            displayStatusLocal(issueLcLogStatusEl, 'Logging LC...', 'loading');
            if (printableLcTypeIndicatorContainerEl.length) printableLcTypeIndicatorContainerEl.hide();
            if (printableLcTypeIndicatorEl.length) printableLcTypeIndicatorEl.text('');

            $.ajax({
                url: 'log_lc_issuance.php',
                type: 'POST', dataType: 'json', data: lcLogDataPayload,
                success: function(response) {
                    console.log("JS ISSUE_LC_LOG_BTN: AJAX Success, Response from log_lc_issuance.php:", response);
                    if (response && response.action === 'redirect_login') { /* Handle redirect */ }
                    if (response && response.status === 'success') {
                        displayStatusLocal(issueLcLogStatusEl, response.message, 'success', 7000);
                        if (response.tcid && response.lc_type_issued) {
                            if(printableLcTypeIndicatorEl.length) printableLcTypeIndicatorEl.text(response.lc_type_issued.toUpperCase());
                            if(printableLcTypeIndicatorContainerEl.length) printableLcTypeIndicatorContainerEl.show();
                            alert("LC " + response.lc_type_issued + " (TCID: " + response.tcid + ") logged. Print page (Ctrl+P).");
                        }
                        // Trigger re-fetch in the autofill JS file
                        $(document).trigger('lc-action-complete', [studentDbId]);
                    } else {
                        displayStatusLocal(issueLcLogStatusEl, (response && response.message) ? response.message : 'Error logging LC.', 'error', 5000);
                    }
                },
                error: function(xhr, status, errorThrown) {
                    console.error("JS ISSUE_LC_LOG_BTN: AJAX Error:", status, errorThrown, xhr.responseText);
                    let errorMsg = 'Server error during LC logging.';
                    // ... (your robust error handling for login redirect etc.) ...
                    displayStatusLocal(issueLcLogStatusEl, errorMsg, 'error', 5000);
                }
            });
        });
    } // end if (issueAndLogLcButtonEl.length)

    // Listen for event from student save button in the other file if it needs to trigger a general context refresh
    $(document).on('lc-action-complete', function(event, studentPkIdToRefetch) {
        console.log("LC Issuance JS: Received 'lc-action-complete'. Attempting to call global re-fetch for ID:", studentPkIdToRefetch);
        if (typeof window.performStudentLookup_Main === "function" && studentPkIdToRefetch) {
            window.performStudentLookup_Main('primary_key', studentPkIdToRefetch);
        } else if (typeof window.performStudentLookup_Main === "function" && typeof currentFetchedStudentIdentifier !== 'undefined' && currentFetchedStudentIdentifier.value) {
            // Fallback to last known identifier if specific ID not passed
            window.performStudentLookup_Main(currentFetchedStudentIdentifier.type, currentFetchedStudentIdentifier.value);
        } else {
            console.warn("LC Issuance JS: performStudentLookup_Main function not found on window or no ID to refetch.");
        }
    });

    console.log("LC Issuance Actions JS: Initialized. V1.1");
});