// autofill_lc_form.js (Complete V1.8 - Name Suggestions & Full LC Issuance Flow)
$(document).ready(function () {
    console.log("Autofill JS: Document ready. V1.8 - Name Suggestions & LC Flow.");

    // --- Cache jQuery Objects ---
    const statusMessageDiv = $('#statusMessage');
    const lcContextualStatusContainerEl = $('#lcContextualStatusContainer');
    const lcContextualStatusTextEl = $('#lcContextualStatusText');
    const printableLcTypeIndicatorContainerEl = $('#printableLcTypeIndicatorContainer');
    const printableLcTypeIndicatorEl = $('#printableLcTypeIndicator');

    // <<== ENSURE THESE IDs MATCH YOUR HTML BUTTON AND ITS STATUS DIV >>>>
    const mainActionButtonEl = $('#saveAllStudentDetailsBtn'); // Your main action/save button
    const mainActionStatusEl = $('#saveOperationStatus');   // Status div for that button

    const currentStudentDbIdField = $('#current_student_db_id');
    const studentFullNameInput = $('#student_full_name');
    const fatherNameInput = $('#father_name');
    const surnameInput = $('#surname');

    // --- State Variables ---
    let isProgrammaticallyUpdating = false;
    let programmaticUpdateTimeoutId = null;
    let currentLoadedStudentData = null;
    let currentFetchedStudentIdentifier = { type: null, value: null };
    console.log("JS INIT (V1.8): isProgrammaticallyUpdating =", isProgrammaticallyUpdating);

    // --- Helper Functions ---
    function getMultiBoxValue(baseId, count) {
        let value = "";
        for (let i = 1; i <= count; i++) {
            const element = $(`#${baseId}${i}`);
            if (element.length) { value += element.val().trim(); }
        }
        return value;
    }
    // This function can be added to your autofill_lc_form.js,
    // ideally within the $(document).ready() scope or as a global helper if preferred.
    
    function convertNumericDateToWords(numericDate) { // Expects "DDMMYYYY" or "YYYY-MM-DD"
        if (!numericDate || typeof numericDate !== 'string') {
            return "";
        }
    
        let day, month, year;
        let dateObj = null;
    
        if (numericDate.match(/^\d{8}$/)) { // DDMMYYYY
            day = parseInt(numericDate.substring(0, 2), 10);
            month = parseInt(numericDate.substring(2, 4), 10);
            year = parseInt(numericDate.substring(4, 8), 10);
            if (isValidDate(day, month, year)) {
                dateObj = new Date(year, month - 1, day);
            }
        } else if (numericDate.match(/^\d{4}-\d{2}-\d{2}$/)) { // YYYY-MM-DD
            const parts = numericDate.split('-');
            year = parseInt(parts[0], 10);
            month = parseInt(parts[1], 10);
            day = parseInt(parts[2], 10);
            if (isValidDate(day, month, year)) {
                dateObj = new Date(year, month - 1, day);
            }
        } else {
             // Try parsing with Date.parse as a fallback for other common formats
            const parsedTimestamp = Date.parse(numericDate);
            if (!isNaN(parsedTimestamp)) {
                dateObj = new Date(parsedTimestamp);
                day = dateObj.getDate();
                month = dateObj.getMonth() + 1;
                year = dateObj.getFullYear();
            }
        }
    
    
        if (!dateObj) {
            console.warn("convertNumericDateToWords: Could not parse date:", numericDate);
            return ""; // Or "Invalid Date"
        }
    
        day = dateObj.getDate();
        month = dateObj.getMonth() + 1; // JS months are 0-11
        year = dateObj.getFullYear();
    
        const daysInWords = [
            "", "First", "Second", "Third", "Fourth", "Fifth", "Sixth", "Seventh", "Eighth", "Ninth", "Tenth",
            "Eleventh", "Twelfth", "Thirteenth", "Fourteenth", "Fifteenth", "Sixteenth", "Seventeenth", "Eighteenth", "Nineteenth", "Twentieth",
            "Twenty-First", "Twenty-Second", "Twenty-Third", "Twenty-Fourth", "Twenty-Fifth", "Twenty-Sixth", "Twenty-Seventh", "Twenty-Eighth", "Twenty-Ninth", "Thirtieth", "Thirty-First"
        ];
    
        const monthsInWords = [
            "", "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];
    
        // Simple number to words for year (up to 9999)
        function numberToWords(num) {
            const ones = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen"];
            const tens = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
            if (num === 0) return "Zero";
            let words = "";
    
            if (num >= 1000) {
                words += ones[Math.floor(num / 1000)] + " Thousand ";
                num %= 1000;
            }
            if (num >= 100) {
                words += ones[Math.floor(num / 100)] + " Hundred ";
                num %= 100;
            }
            if (num >= 20) {
                words += tens[Math.floor(num / 10)] + " ";
                num %= 10;
            }
            if (num > 0) {
                words += ones[num] + " ";
            }
            return words.trim();
        }
    
        // Helper to validate date components
        function isValidDate(d, m, y) {
            const testDate = new Date(y, m - 1, d);
            return testDate.getFullYear() === y && testDate.getMonth() === m - 1 && testDate.getDate() === d;
        }
    
    
        if (day < 1 || day > 31 || month < 1 || month > 12 || year < 1000 || year > 9999) { // Basic validation
            console.warn("convertNumericDateToWords: Invalid date components after parsing:", day, month, year);
            return "";
        }
    
        const dayWord = daysInWords[day] || String(day); // Fallback to number if out of array range (shouldn't happen with validation)
        const monthWord = monthsInWords[month] || "";
        const yearWord = numberToWords(year);
    
        if (!dayWord || !monthWord || !yearWord) return ""; // If any part failed
    
        return `${dayWord} ${monthWord} ${yearWord}`; // Example: "First June Two Thousand Five"
    }
    
    // Example Usage (for testing in console):
    // console.log(convertNumericDateToWords("01062005"));
    // console.log(convertNumericDateToWords("2005-06-15"));
    // console.log(convertNumericDateToWords("15081947"));

    function setMultiBoxValue(baseId, count, valueString) {
        if (typeof valueString !== 'string') valueString = String(valueString || '');
        for (let i = 1; i <= count; i++) {
            const element = $(`#${baseId}${i}`);
            if (element.length) { element.val(valueString.charAt(i - 1) || ''); }
        }
    }

    function displayStatus(element, message, type, autoClearDelay = 0) {
        if (element && element.length) {
            let typeClass = 'status-info'; let cssColor = '#6c757d';
            switch(type) {
                case 'loading': typeClass = 'status-loading'; cssColor = '#007bff'; break;
                case 'success': typeClass = 'status-success'; cssColor = '#28a745'; break;
                case 'error':   typeClass = 'status-error';   cssColor = '#dc3545'; break;
            }
            element.removeClass('status-loading status-success status-error status-info').addClass(typeClass);
            element.text(message).css('color', cssColor);
            if (autoClearDelay > 0) { setTimeout(() => { element.text('').removeClass(typeClass).css('color', ''); }, autoClearDelay); }
        }
    }

    function setupAutoTab(baseId, boxCount) {
        for (let i = 1; i <= boxCount; i++) {
            const currentBox = $(`#${baseId}${i}`);
            if (!currentBox.length) continue;
            currentBox.on('input keyup', function(e) {
                const $this = $(this);
                const maxLength = parseInt($this.attr('maxlength'), 10) || 1;
                if ($this.val().length >= maxLength && i < boxCount && e.key !== "Backspace" && !e.metaKey && !e.ctrlKey) {
                    $(`#${baseId}${i + 1}`).focus();
                }
                if (e.key === "Backspace" && $this.val().length === 0 && i > 1) {
                    $(`#${baseId}${i - 1}`).focus();
                }
            });
        }
    }
    function removeStudentSelectionDropdown() {
        $('#studentNameSuggestions').remove();
        // console.log("JS: Suggestion dropdown removed."); // Optional log
    }

    // --- Function to Update LC Contextual UI ---
    function updateLcContextualUI(studentData) {
        currentLoadedStudentData = studentData;
        console.log("UPDATE_LC_UI: Student data for context:", studentData ? studentData.unique_student_identifier : "null");

        let buttonText = mainActionButtonEl.data('default-text');
        if (!buttonText) { buttonText = mainActionButtonEl.text() || "सर्व माहिती जतन करा (Save All Details)"; mainActionButtonEl.data('default-text', buttonText); }

        let buttonBgColor = '#007bff'; let buttonTextColor = '#ffffff'; let buttonDisabled = true;
        let statusText = "विद्यार्थी निवडा (Select Student)...";
        let statusTextColor = '#6c757d';
        let statusContainerBgColor = '#f8f9fa'; let statusContainerBorderColor = '#dee2e6';

        if (studentData && studentData.unique_student_identifier && studentData.lc_issuance_info) {
            buttonDisabled = false;
            const lcInfo = studentData.lc_issuance_info;
            console.log("UPDATE_LC_UI: LC Info received:", lcInfo);
            if (lcInfo.next_lc_type === 'Original') {
                statusText = "मूळ एलसी जारी करण्यासाठी तयार (Ready for ORIGINAL LC Issuance)."; statusTextColor = 'green';
                statusContainerBgColor = '#d4edda'; statusContainerBorderColor = '#c3e6cb';
                buttonText = "नोंद करा आणि मूळ एलसी जारी करा (Save & Issue Original LC)";
                buttonBgColor = '#28a745';
            } else { // Duplicate
                statusText = `मूळ एलसी ${lcInfo.original_issued_date||'N/A'} रोजी जारी. डुप्लिकेटसाठी तयार (एकूण ${lcInfo.issued_lc_count}).`;
                statusTextColor = '#856404'; statusContainerBgColor = '#fff3cd'; statusContainerBorderColor = '#ffeeba';
                buttonText = "नोंद करा आणि डुप्लिकेट एलसी जारी करा (Save & Issue Duplicate LC)";
                buttonBgColor = '#ffc107'; buttonTextColor = '#212529';
            }
        } else if (studentData && studentData.unique_student_identifier) {
            statusText = "LC स्थिती अनिश्चित. तपशील जतन केले जाऊ शकतात."; statusTextColor = '#17a2b8';
            statusContainerBgColor = '#d1ecf1'; statusContainerBorderColor = '#bee5eb';
            buttonDisabled = false; buttonText = mainActionButtonEl.data('default-text');
            buttonBgColor = '#007bff';
            console.warn("UPDATE_LC_UI: lc_issuance_info missing for loaded student.");
        } else { buttonText = "प्रथम विद्यार्थी लोड करा (Load Student First)"; buttonBgColor = '#6c757d'; }

        if(lcContextualStatusTextEl.length) lcContextualStatusTextEl.text(statusText).css('color', statusTextColor);
        if(lcContextualStatusContainerEl.length) lcContextualStatusContainerEl.css({'background-color': statusContainerBgColor, 'border-color': statusContainerBorderColor});
        mainActionButtonEl.text(buttonText).css({'background-color': buttonBgColor, 'color': buttonTextColor}).prop('disabled', buttonDisabled);
    }

    // --- Function to Clear All Autofilled Form Fields ---
    function clearAllAutoFilledFields(preserveLookupInputs = false) {
        console.log("CLEAR_FORM: Start. Preserve:", preserveLookupInputs, "Current Flag:", isProgrammaticallyUpdating);
        if (programmaticUpdateTimeoutId) { clearTimeout(programmaticUpdateTimeoutId); programmaticUpdateTimeoutId = null; console.log("CLEAR_FORM: Cleared pending flag reset.");}
        isProgrammaticallyUpdating = true;
        console.log("CLEAR_FORM: Flag set TRUE.");

        const fieldsToClearById = [ /* <<== ADD ALL YOUR FORM INPUT IDs HERE ==>> */
            'udise_number_input', 'generalRegisterNo', /* student_full_name handled separately */
            'father_name', 'surname', 'mother_full_name', 'nationality', 'mother_tongue',
            'religion', 'caste', 'sub_caste', 'birth_place_village_city', 'birth_taluka',
            'birth_district', 'birth_state', 'birth_country', 'dob_in_words',
            'previous_school_and_standard', 'admission_standard', 'academic_progress',
            'conduct', 'standard_studying_in_and_since', 'reason_for_leaving', 'remarks',
            'current_student_db_id', 'manual_lc_sr_no' // If you added this
        ];
        if (!(preserveLookupInputs && currentFetchedStudentIdentifier && currentFetchedStudentIdentifier.type === 'name')) {
            studentFullNameInput.val('');
        }
        fieldsToClearById.forEach(id => $(`#${id}`).val(''));
        setMultiBoxValue('dobBox', 8, ''); setMultiBoxValue('admissionDateBox', 8, ''); setMultiBoxValue('leavingDateBox', 8, '');

        if (!preserveLookupInputs) {
            setMultiBoxValue('studentIdBox', 4, ''); setMultiBoxValue('uidBox', 12, '');
            studentFullNameInput.val('');
        } else {
            if (currentFetchedStudentIdentifier && currentFetchedStudentIdentifier.type !== 'student_id') setMultiBoxValue('studentIdBox', 4, '');
            if (currentFetchedStudentIdentifier && currentFetchedStudentIdentifier.type !== 'uid') setMultiBoxValue('uidBox', 12, '');
        }

        removeStudentSelectionDropdown();
        displayStatus(statusMessageDiv, '', 'info');
        if (mainActionStatusEl.length) displayStatus(mainActionStatusEl, '', 'info');
        updateLcContextualUI(null);
        if (printableLcTypeIndicatorContainerEl.length) printableLcTypeIndicatorContainerEl.hide();
        if (printableLcTypeIndicatorEl.length) printableLcTypeIndicatorEl.text('');

        programmaticUpdateTimeoutId = setTimeout(() => {
            isProgrammaticallyUpdating = false; programmaticUpdateTimeoutId = null;
            console.log("CLEAR_FORM: Flag reset to FALSE.");
        }, 70);
    }

    // --- Function to Populate Form After Data Fetch ---
    // In your autofill_lc_form.js, this is the function to be updated/replaced.
    // Make sure convertNumericDateToWords and its helpers are defined in the same scope.
    
    function populateFormAfterFetch(studentData) {
        console.log("----------------------------------------------------"); // Added for clarity
        console.log("POPULATE: ENTER populateFormAfterFetch.");
        // Log the ENTIRE studentData object to see EXACTLY what PHP sent.
        console.log("POPULATE: Raw studentData object from PHP:", JSON.stringify(studentData, null, 2));
        console.log("POPULATE: Current isProgrammaticallyUpdating (before set):", isProgrammaticallyUpdating);
    
    
        // Initial checks for valid studentData
        if (typeof studentData !== 'object' || studentData === null) {
            console.error("POPULATE ERROR: Invalid studentData (not an object or null). Function will exit.");
            if (typeof clearAllAutoFilledFields === "function") clearAllAutoFilledFields(); // Call if defined
            return;
        }
        if (!studentData.unique_student_identifier && studentData.unique_student_identifier !== 0) { // Allow 0 as a valid PK
            console.warn("POPULATE WARNING: studentData.unique_student_identifier is missing or empty. Autofill might be incomplete.");
            // You might decide to return here or proceed cautiously if this isn't fatal for basic population
        }
    
        // Manage programmatic update flag
        if (programmaticUpdateTimeoutId) {
            clearTimeout(programmaticUpdateTimeoutId);
            programmaticUpdateTimeoutId = null;
            console.log("POPULATE: Cleared pending programmaticUpdateTimeoutId.");
        }
        isProgrammaticallyUpdating = true;
        console.log("POPULATE: isProgrammaticallyUpdating SET to true.");
    
        // --- Populate ALL form fields (Your existing logic) ---
        // Example of existing population:
        if ($('#udise_number_input').length) { $('#udise_number_input').val(studentData.udise_number || ''); }
        else if ($('#udise_number').is('input')) { $('#udise_number').val(studentData.udise_number || '');}
        $('#generalRegisterNo').val(studentData.generalRegisterNo || '');
        studentFullNameInput.val(studentData.student_full_name || ''); // Assumes studentFullNameInput is defined
        fatherNameInput.val(studentData.father_name || '');         // Assumes fatherNameInput is defined
        surnameInput.val(studentData.surname || '');               // Assumes surnameInput is defined
        $('#mother_full_name').val(studentData.mother_full_name || '');
        $('#nationality').val(studentData.nationality || '');
        $('#mother_tongue').val(studentData.mother_tongue || '');
        $('#religion').val(studentData.religion || '');
        $('#caste').val(studentData.caste || '');
        $('#sub_caste').val(studentData.sub_caste || '');
        $('#birth_place_village_city').val(studentData.birth_place_village_city || '');
        $('#birth_taluka').val(studentData.birth_taluka || '');
        $('#birth_district').val(studentData.birth_district || '');
        $('#birth_state').val(studentData.birth_state || '');
        $('#birth_country').val(studentData.birth_country || '');
    
        // 1. Populate numeric DOB boxes (YOUR EXISTING LINE for dobBox)
        // This assumes studentData.date_of_birth contains "DDMMYYYY" from PHP
        setMultiBoxValue('dobBox', 8, studentData.date_of_birth || '');
        console.log(`POPULATE: Set numeric DOB boxes for 'dobBox' with: "${studentData.date_of_birth || ''}"`);
    
        // 2. --- NEW: Call conversion function and populate dob_in_words ---
        const dob_in_words_input = $('#dob_in_words'); // Cache selector for #dob_in_words input
        if (dob_in_words_input.length) { // Check if the #dob_in_words input field exists
            if (studentData.date_of_birth && studentData.date_of_birth.length === 8 && typeof convertNumericDateToWords === "function") {
                const dobInWordsValue = convertNumericDateToWords(studentData.date_of_birth);
                console.log(`POPULATE: Converted DOB "${studentData.date_of_birth}" to words: "${dobInWordsValue}"`);
                dob_in_words_input.val(dobInWordsValue);
                console.log(`POPULATE: Set #dob_in_words to: "${dob_in_words_input.val()}"`);
            } else {
                let reason = "";
                if (!studentData.date_of_birth || studentData.date_of_birth.length !== 8) {
                    reason = "No valid numeric DOB ('date_of_birth' key or format) in studentData.";
                }
                if (typeof convertNumericDateToWords !== "function") {
                    reason += (reason ? " Also, " : "") + "convertNumericDateToWords function is missing.";
                }
                console.log("POPULATE: " + reason + " Clearing #dob_in_words.");
                dob_in_words_input.val(''); // Clear if no valid numeric DOB or function missing
            }
        } else {
            console.warn("POPULATE: HTML Element with ID #dob_in_words NOT FOUND! Cannot set date in words.");
        }
        // --- END OF NEW DOB IN WORDS BLOCK ---
    
        // Continue with your existing population for other fields:
        $('#previous_school_and_standard').val(studentData.previous_school_and_standard || '');
        setMultiBoxValue('admissionDateBox', 8, studentData.admission_date || '');
        $('#admission_standard').val(studentData.admission_standard || '');
        $('#academic_progress').val(studentData.academic_progress || '');
        $('#conduct').val(studentData.conduct || '');
        setMultiBoxValue('leavingDateBox', 8, studentData.leaving_date || '');
        $('#standard_studying_in_and_since').val(studentData.standard_studying_in_and_since || '');
        $('#reason_for_leaving').val(studentData.reason_for_leaving || '');
        $('#remarks').val(studentData.remarks || '');
    
        // Populate hidden DB ID and other display IDs
        currentStudentDbIdField.val(studentData.unique_student_identifier || ''); // Assumes currentStudentDbIdField is defined
        console.log("POPULATE: Stored unique_student_identifier in hidden field:", studentData.unique_student_identifier);
        setMultiBoxValue('studentIdBox', 4, studentData.student_id_display_value || '');
        setMultiBoxValue('uidBox', 12, studentData.uid_display_value || '');
        // --- END OF YOUR EXISTING POPULATE LOGIC ---
    
    
        console.log("POPULATE: Form fields population attempt complete.");
        if (typeof updateLcContextualUI === "function") { // If you are using the LC Context UI
            console.log("POPULATE: Calling updateLcContextualUI.");
            updateLcContextualUI(studentData); // studentData should contain lc_issuance_info from fetch_lc_data.php
        }
    
        programmaticUpdateTimeoutId = setTimeout(() => {
            isProgrammaticallyUpdating = false; programmaticUpdateTimeoutId = null;
            console.log("POPULATE: isProgrammaticallyUpdating reset to FALSE via setTimeout.");
            console.log("----------------------------------------------------");
        }, 150); // A reasonable delay
    }
    // --- Function to Fetch Student Data ---
    // autofill_lc_form.js (Complete V1.7 - Snippet from performLookup success callback)

    function performLookup(identifierType, identifierValue) {
        // ... (start of performLookup: flag checks, currentFetchedStudentIdentifier set, etc.) ...
    
        $.ajax({
            url: 'fetch_lc_data.php', // This PHP MUST send lc_issuance_info
            type: 'POST', dataType: 'json',
            data: { identifier_type: identifierType, identifier_value: identifierValue },
            success: function(response) {
                console.log("PERFORM_LOOKUP: AJAX Success. Raw Response:", JSON.stringify(response));
                clearAllAutoFilledFields(true); // Clear form but preserve input that triggered lookup
    
                if (response && response.status === 'success' && Array.isArray(response.data)) {
                    if (response.data.length === 1) {
                        // **** CASE 1: EXACTLY ONE STUDENT FOUND ****
                        populateFormAfterFetch(response.data[0]); // This will call updateLcContextualUI
                        displayStatus(statusMessageDiv, 'माहिती मिळाली (Details loaded).', 'success', 3000);
                    } else if (response.data.length > 1 && identifierType === 'name') {
                        // **** CASE 2: MULTIPLE STUDENTS FOUND FOR 'name' LOOKUP ****
                        // This is the block that handles displaying the suggestion list
                        updateLcContextualUI(null); // Reset LC context while showing suggestions
                        let suggestionsHtml = '<ul id="studentNameSuggestions" style="list-style-type:none; padding:0; margin:0; border:1px solid #ccc; position:absolute; background-color:white; z-index:1000; max-height: 200px; overflow-y: auto; width:'+ studentFullNameInput.outerWidth() +'px;">';
                        response.data.forEach(function(student) {
                            const uniqueId = student.unique_student_identifier || ''; // This is students.id (PK)
                            const nameToDisplay = student.student_full_name || 'Unknown Name';
                            // When a suggestion is clicked, we will re-lookup by 'primary_key'
                            suggestionsHtml += `<li data-identifier="${uniqueId}" data-type="primary_key" style="padding:8px 12px; cursor:pointer; border-bottom:1px solid #eee;">${nameToDisplay} (ID: ${uniqueId})</li>`;
                        });
                        suggestionsHtml += '</ul>';
                        studentFullNameInput.after(suggestionsHtml); // Display below the name input
                        const nameInputPos = studentFullNameInput.position();
                        if (nameInputPos) { $('#studentNameSuggestions').css({ top: nameInputPos.top + studentFullNameInput.outerHeight() + 2, left: nameInputPos.left }); }
                        displayStatus(statusMessageDiv, 'अनेक विद्यार्थी सापडले, कृपया एक निवडा (Multiple students found, please select one).', 'info');
    
                        // Attach CLICK HANDLER to the new suggestion list items
                        $('#studentNameSuggestions li').off('click').on('click', function() {
                            const selectedDbId = $(this).data('identifier'); // Get the student's database PK
                            const selectedTypeForLookup = $(this).data('type'); // Should be 'primary_key'
                            const selectedNamePart = $(this).text().split(' (ID:')[0].trim(); // Get only name part
    
                            console.log("SUGGESTION CLICKED: ID:", selectedDbId, "Type:", selectedTypeForLookup);
    
                            if (programmaticUpdateTimeoutId) { clearTimeout(programmaticUpdateTimeoutId); programmaticUpdateTimeoutId = null;}
                            isProgrammaticallyUpdating = true; // Set flag: about to change input value
                            studentFullNameInput.val(selectedNamePart); // Update the name field
                            removeStudentSelectionDropdown(); // Remove the list
    
                            if (selectedDbId && selectedTypeForLookup === 'primary_key') {
                                console.log("SUGGESTION CLICK: Calling performLookup by primary_key for ID:", selectedDbId);
                                // The performLookup -> populateFormAfterFetch sequence will handle resetting the flag.
                                performLookup(selectedTypeForLookup, selectedDbId);
                            } else {
                                console.error("SUGGESTION CLICK: Missing ID or invalid type for re-lookup.");
                                // Fallback: reset flag if no PK lookup happens
                                programmaticUpdateTimeoutId = setTimeout(() => {
                                    isProgrammaticallyUpdating = false; programmaticUpdateTimeoutId=null;
                                    console.log("Suggestion Click: Flag reset (no PK lookup).");
                                }, 50);
                            }
                        });
                        // **** END OF MULTIPLE STUDENTS FOUND LOGIC ****
                    } else { // No data in array (response.data.length === 0)
                        displayStatus(statusMessageDiv, 'विद्यार्थी आढळला नाही (No student record found).', 'error', 3000);
                        clearAllAutoFilledFields(); // Full clear and UI reset
                    }
                } else if (response && response.status === 'not_found') {
                    displayStatus(statusMessageDiv, response.message || 'विद्यार्थी आढळला नाही.', 'error', 3000);
                    clearAllAutoFilledFields();
                } else { // Other errors from PHP (e.g. status: 'error') or unexpected JSON structure
                    displayStatus(statusMessageDiv, (response && response.message) ? response.message : 'माहिती शोधताना त्रुटी.', 'error', 5000);
                    clearAllAutoFilledFields();
                }
            },
            error: function(xhr, status, error) {
                // ... your error handling ...
            }
        });
    } // End of performLookup
    // --- Functions to manage clearing other lookup fields ---
    function clearOtherLookups(activeLookupType) {
        console.log("CLEAR_OTHER_LOOKUPS for:", activeLookupType, "isProg:", isProgrammaticallyUpdating);
        if (isProgrammaticallyUpdating) { console.log("CLEAR_OTHER_LOOKUPS: Skipped (flag true)."); return; }
        isProgrammaticallyUpdating = true;
        if (activeLookupType !== 'student_id') { setMultiBoxValue('studentIdBox', 4, ''); }
        if (activeLookupType !== 'uid') { setMultiBoxValue('uidBox', 12, ''); }
        if (activeLookupType !== 'name' && !$('#studentNameSuggestions').is(':visible')) {
             studentFullNameInput.val('');
        }
        setTimeout(() => { isProgrammaticallyUpdating = false; console.log("CLEAR_OTHER_LOOKUPS: Flag reset."); }, 50);
    }
    function isOtherLookupsEmpty(activeLookupType) {
        let sidEmpty = (activeLookupType === 'student_id' || getMultiBoxValue('studentIdBox', 4) === '');
        let uidEmpty = (activeLookupType === 'uid' || getMultiBoxValue('uidBox', 12) === '');
        let nameEmpty = (activeLookupType === 'name' || studentFullNameInput.val().trim() === '');
        return sidEmpty && uidEmpty && nameEmpty;
    }

    // --- Event Handlers for Lookup Fields (Blur events) ---
    $('#studentIdBox4').on('blur', function() { if (isProgrammaticallyUpdating) return; const val = getMultiBoxValue('studentIdBox', 4); if (val.length === 4) { clearOtherLookups('student_id'); performLookup('student_id', val); } else if (val.length === 0 && isOtherLookupsEmpty('student_id')) { clearAllAutoFilledFields(); } });
    $('#uidBox12').on('blur', function() { if (isProgrammaticallyUpdating) return; const val = getMultiBoxValue('uidBox', 12); if (val.length === 12) { clearOtherLookups('uid'); performLookup('uid', val); } else if (val.length === 0 && isOtherLookupsEmpty('uid')) { clearAllAutoFilledFields(); } });
    studentFullNameInput.on('blur', function() {
        const $this = $(this); if (isProgrammaticallyUpdating) { console.log("NameInput BLUR: SKIPPED (flag true)."); return; }
        setTimeout(() => {
            if ($('#studentNameSuggestions:hover').length || $('#studentNameSuggestions li:hover').length || $('#studentNameSuggestions').is(':visible')) { return; }
            removeStudentSelectionDropdown(); const finalValue = $this.val().trim();
            if (finalValue !== "") { clearOtherLookups('name'); performLookup('name', finalValue); }
            else if (isOtherLookupsEmpty('name')) { clearAllAutoFilledFields(); }
        }, 250);
    });
    $(document).on('click', function(e) { if (!$(e.target).closest('#student_full_name, #studentNameSuggestions').length) removeStudentSelectionDropdown(); });


    // --- Click Handler for your Main Action/Save Button ---
    mainActionButtonEl.on('click', function() {
        console.log("JS MAIN ACTION BUTTON: Click handler initiated!");
        const studentDbId_PK = currentStudentDbIdField.val();

        console.log("JS SAVE BUTTON: studentDbId_PK from hidden field:", studentDbId_PK);
        if (!studentDbId_PK || !currentLoadedStudentData) {
            displayStatus(mainActionStatusEl, 'No student loaded (PK or context missing). Fetch first.', 'error', 4000);
            console.error("JS SAVE BUTTON: Exiting - No studentDbId_PK or currentLoadedStudentData.");
            return;
        }

        // --- Get CSRF Token ---
        let csrfTokenName = 'ci_csrf_token'; // <<== VERIFY THIS from your HTML form's source
        let csrfTokenValue = $('input[name="' + csrfTokenName + '"]').val();
        if (!csrfTokenValue) {
            console.warn('CSRF token (' + csrfTokenName + ') not found/empty on page! Save may fail.');
            alert('Security token missing. Please refresh and try again, or contact support.');
            displayStatus(mainActionStatusEl, 'Security token error. Action aborted.', 'error', 5000);
            return; // Stop if CSRF token is critical and not found
        } else {
            console.log('CSRF Token Found for Save: Name=' + csrfTokenName + ', Value=' + csrfTokenValue);
        }

        // --- Collect ALL Form Data for save_student_data_and_process_lc.php ---
        // <<== ENSURE THIS MATCHES ALL FIELDS YOUR PHP SCRIPT EXPECTS IN $_POST ==>>
        const formDataToSubmit = {
            current_student_db_id: studentDbId_PK,
            udise_number_input: $('#udise_number_input').val(),
            generalRegisterNo: $('#generalRegisterNo').val(),
            student_full_name: studentFullNameInput.val().trim(),
            father_name: fatherNameInput.val().trim(),
            surname: surnameInput.val().trim(),
            mother_full_name: $('#mother_full_name').val().trim(),
            nationality: $('#nationality').val().trim(),
            mother_tongue: $('#mother_tongue').val().trim(),
            religion: $('#religion').val().trim(),
            caste: $('#caste').val().trim(),
            sub_caste: $('#sub_caste').val().trim(),
            birth_place_village_city: $('#birth_place_village_city').val().trim(),
            birth_taluka: $('#birth_taluka').val().trim(),
            birth_district: $('#birth_district').val().trim(),
            birth_state: $('#birth_state').val().trim(),
            birth_country: $('#birth_country').val().trim(),
            date_of_birth: getMultiBoxValue('dobBox', 8),
            dob_in_words: $('#dob_in_words').val().trim(),
            previous_school_and_standard: $('#previous_school_and_standard').val().trim(),
            admission_date: getMultiBoxValue('admissionDateBox', 8),
            admission_standard: $('#admission_standard').val().trim(),
            academic_progress: $('#academic_progress').val().trim(),
            conduct: $('#conduct').val().trim(),
            leaving_date: getMultiBoxValue('leavingDateBox', 8), // Crucial for LC issuance
            standard_studying_in_and_since: $('#standard_studying_in_and_since').val().trim(),
            reason_for_leaving: $('#reason_for_leaving').val().trim(),
            remarks: $('#remarks').val().trim(),
            // manual_lc_sr_no: $('#manual_lc_sr_no').val().trim(), // If you use this optional field
        };
        formDataToSubmit[csrfTokenName] = csrfTokenValue; // Add CSRF token

        console.log("JS SAVE BUTTON: formDataToSubmit (with CSRF):", JSON.stringify(formDataToSubmit, null, 2));

        if (!formDataToSubmit.student_full_name) {
            displayStatus(mainActionStatusEl, 'Student Full Name is required.', 'error', 3000);
            return;
        }

        if (printableLcTypeIndicatorContainerEl.length) printableLcTypeIndicatorContainerEl.hide();
        if (printableLcTypeIndicatorEl.length) printableLcTypeIndicatorEl.text('');

        let willIssueLc = formDataToSubmit.leaving_date && formDataToSubmit.leaving_date.trim().length === 8;
        let confirmMessage = "तुम्ही ही माहिती जतन करू इच्छिता? (Do you want to save these details?)";
        if (currentLoadedStudentData.lc_issuance_info) {
            if (willIssueLc) {
                let lcContext = currentLoadedStudentData.lc_issuance_info.next_lc_type || "नवीन (New)";
                confirmMessage = `यामुळे तपशील जतन केले जातील आणि ${lcContext} एलसी जारी केले जाईल. पुढे जायचे आहे का?`;
            } else {
                confirmMessage = "शाळा सोडल्याचा दिनांक रिकामा आहे. एलसी जारी होणार नाही, फक्त विद्यार्थी तपशील जतन केले जातील. पुढे जायचे आहे का?";
            }
        } else {
            confirmMessage = willIssueLc ? "Save details and issue LC?" : "Leaving date empty. Save details only (LC will not be issued)?";
            console.warn("MAIN ACTION: lc_issuance_info not found in currentLoadedStudentData. Using fallback confirm message.");
        }
        console.log("JS SAVE BUTTON: Confirm dialog message:", confirmMessage);

        if (!confirm(confirmMessage)) {
            displayStatus(mainActionStatusEl, 'ऑपरेशन रद्द केले (Operation Cancelled).', 'info', 3000);
            console.log("JS SAVE BUTTON: User cancelled operation.");
            return;
        }

        let statusMsgLoading = willIssueLc ? "एलसी प्रक्रिया करत आहे (Processing LC)..." : "माहिती जतन करत आहे (Saving details)...";
        displayStatus(mainActionStatusEl, statusMsgLoading, 'loading');
        console.log("JS SAVE BUTTON: MAKING AJAX CALL to save_student_data_and_process_lc.php");

        $.ajax({
            url: 'save_student_data_and_process_lc.php', // Your single PHP script
            type: 'POST',
            dataType: 'json',
            data: formDataToSubmit,
            success: function(response) {
                console.log("JS MAIN ACTION: AJAX Success, Response:", response);
                if (response && response.action === 'redirect_login') {
                    alert(response.message);
                    window.location.href = 'https://sssonaje.online/site/userlogin'; // <<== YOUR LOGIN PAGE URL
                    return;
                }

                if (response && response.status === 'success') {
                    displayStatus(mainActionStatusEl, response.message, 'success', 7000);

                    if (response.lc_issue_status === 'success' && response.tcid && response.lc_type_issued) {
                        if(printableLcTypeIndicatorEl.length) printableLcTypeIndicatorEl.text(response.lc_type_issued.toUpperCase());
                        if(printableLcTypeIndicatorContainerEl.length) printableLcTypeIndicatorContainerEl.show();
                        alert("LC " + response.lc_type_issued + " (TCID: " + response.tcid + ") नोंदवले गेले आहे. तुम्ही आता हे पेज प्रिंट करू शकता (Ctrl+P).");
                    }

                    // Re-fetch student data to update the contextual UI
                    if (typeof performLookup === "function") {
                        if (currentFetchedStudentIdentifier.type && currentFetchedStudentIdentifier.value) {
                            performLookup(currentFetchedStudentIdentifier.type, currentFetchedStudentIdentifier.value);
                        } else if (studentDbId_PK) {
                            performLookup('primary_key', studentDbId_PK);
                        }
                    }
                } else {
                    displayStatus(mainActionStatusEl, (response && response.message) ? response.message : 'Error processing request.', 'error', 5000);
                }
            },
            error: function(xhr, status, errorThrown) {
                console.error("JS MAIN ACTION: AJAX Error. Status:", status, "Error:", errorThrown);
                console.error("Response Text (DEBUG):", xhr.responseText);
                let errorMsg = 'Server error. Check console.';
                if (xhr.status === 401 || xhr.status === 403) { errorMsg = 'Authentication failed. Log in again.'; }
                else if (status === 'parsererror') { errorMsg = 'Unexpected server response (not JSON). PHP error or login redirect likely.';}
                try { const errResp = JSON.parse(xhr.responseText); if (errResp && errResp.message) errorMsg = errResp.message; if (errResp && errResp.action === 'redirect_login') { alert(errResp.message); window.location.href = 'https://sssonaje.online/site/userlogin'; return;}} catch (e) {}
                displayStatus(mainActionStatusEl, errorMsg, 'error', 7000);
            }
        });
    });

    // --- Auto-Tab Setup ---
    setupAutoTab('studentIdBox', 4); setupAutoTab('uidBox', 12); setupAutoTab('dobBox', 8);
    setupAutoTab('admissionDateBox', 8); setupAutoTab('leavingDateBox', 8);

    // --- Initial UI State ---
    updateLcContextualUI(null); // Set button to "Load Student First" and clear context text

    console.log("Autofill JS: V1.7 All event listeners setup complete. Full Integration.");
}); // End of $(document).ready