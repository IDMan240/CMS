/* =========================================================
   CMS GAUGE CALIBRATION
   FILE 10C
   CALCULATION + DATABASE SAVE
========================================================= */


/* =========================================
   SHORTCUT
========================================= */

const $ = id =>
    document.getElementById(id);


/* =========================================
   CALIBRATION POINTS
========================================= */

const RISING_POINTS = [
    0,
    25,
    50,
    75,
    100
];

const FALLING_POINTS = [
    100,
    75,
    50,
    25,
    0
];


/* =========================================
   NUMBER
========================================= */

function getNumber(value) {

    if (
        value === null ||
        value === undefined ||
        String(value).trim() === ""
    ) {
        return null;
    }

    const number = Number(value);

    return Number.isFinite(number)
        ? number
        : null;
}


/* =========================================
   FORMAT
========================================= */

function formatNumber(value) {

    if (
        value === null ||
        !Number.isFinite(value)
    ) {
        return "—";
    }

    return Number(value).toFixed(2);
}


/* =========================================
   ACTUAL VALUE
========================================= */

function calculateActual(
    range,
    percentage
) {

    if (
        range === null ||
        range === 0
    ) {
        return null;
    }

    return range *
        percentage /
        100;
}


/* =========================================
   ERROR %
========================================= */

function calculateError(
    gauge,
    actual,
    range
) {

    if (
        gauge === null ||
        actual === null ||
        range === null ||
        range === 0
    ) {
        return 0;
    }

    const error =
        (
            gauge -
            actual
        ) /
        range *
        100;

    return Math.abs(error) < 0.000001
        ? 0
        : error;
}


/* =========================================
   CREATE ROW
========================================= */

function createRow(
    percentage,
    tableType
) {

    const row =
        document.createElement("tr");

    row.dataset.readingType =
        tableType;


    const range =
        getNumber(
            $("instrumentRange")?.value
        );


    const actual =
        calculateActual(
            range,
            percentage
        );


    /* =====================================
       % RANGE
    ===================================== */

    const percentageCell =
        document.createElement("td");

    percentageCell.textContent =
        percentage + "%";


    /* =====================================
       ACTUAL VALUE
    ===================================== */

    const actualCell =
        document.createElement("td");

    actualCell.className =
        "actual-value";

    actualCell.dataset.percentage =
        percentage;

    actualCell.textContent =
        actual === null
            ? "—"
            : formatNumber(actual);


    /* =====================================
       DEAD WEIGHT
    ===================================== */

    const deadWeightCell =
        document.createElement("td");

    const deadWeight =
        document.createElement("input");

    deadWeight.type =
        "number";

    deadWeight.step =
        "any";

    deadWeight.className =
        "reading-input dead-weight-input";

    deadWeight.placeholder =
        "Enter reading";

    deadWeight.value =
        actual === null
            ? ""
            : formatNumber(actual);

    deadWeightCell.appendChild(
        deadWeight
    );


    /* =====================================
       GAUGE
    ===================================== */

    const gaugeCell =
        document.createElement("td");

    const gauge =
        document.createElement("input");

    gauge.type =
        "number";

    gauge.step =
        "any";

    gauge.className =
        "reading-input gauge-reading-input";

    gauge.placeholder =
        "Enter reading";

    gauge.value =
        actual === null
            ? ""
            : formatNumber(actual);

    gaugeCell.appendChild(
        gauge
    );


    /* =====================================
       ERROR
    ===================================== */

    const errorCell =
        document.createElement("td");

    errorCell.className =
        "error-value zero-error";

    errorCell.textContent =
        "0.00%";


    /* =====================================
       APPEND ROW
    ===================================== */

    row.appendChild(
        percentageCell
    );

    row.appendChild(
        actualCell
    );

    row.appendChild(
        deadWeightCell
    );

    row.appendChild(
        gaugeCell
    );

    row.appendChild(
        errorCell
    );


    /* =====================================
       UPDATE ERROR
    ===================================== */

    function updateError() {

        const currentRange =
            getNumber(
                $("instrumentRange")?.value
            );

        const currentActual =
            calculateActual(
                currentRange,
                percentage
            );

        const gaugeValue =
            getNumber(
                gauge.value
            );


        const error =
            calculateError(
                gaugeValue,
                currentActual,
                currentRange
            );


        errorCell.textContent =
            formatNumber(error) + "%";


        errorCell.classList.remove(
            "zero-error",
            "has-error"
        );


        if (error === 0) {

            errorCell.classList.add(
                "zero-error"
            );

        } else {

            errorCell.classList.add(
                "has-error"
            );

        }


        /* =================================
           RANGE VALIDATION
        ================================= */

        if (
            gaugeValue !== null &&
            currentRange !== null &&
            (
                gaugeValue < 0 ||
                gaugeValue > currentRange
            )
        ) {

            gauge.classList.add(
                "invalid"
            );

        } else {

            gauge.classList.remove(
                "invalid"
            );

        }

    }


    gauge.addEventListener(
        "input",
        updateError
    );

    gauge.addEventListener(
        "change",
        updateError
    );


    return row;
}


/* =========================================
   BUILD TABLE
========================================= */

function buildTable(
    tableId,
    percentages,
    tableType
) {

    const table =
        $(tableId);

    if (!table) {
        return;
    }

    table.innerHTML =
        "";

    percentages.forEach(
        percentage => {

            table.appendChild(
                createRow(
                    percentage,
                    tableType
                )
            );

        }
    );

}


/* =========================================
   BUILD BOTH TABLES
========================================= */

function rebuildTables() {

    buildTable(
        "risingTable",
        RISING_POINTS,
        "rising"
    );

    buildTable(
        "fallingTable",
        FALLING_POINTS,
        "falling"
    );

}


/* =========================================
   UPDATE ACTUAL VALUES
========================================= */

function updateActualValues() {

    const range =
        getNumber(
            $("instrumentRange")?.value
        );


    document
        .querySelectorAll(
            ".calibration-table tbody tr"
        )
        .forEach(
            row => {

                const actualCell =
                    row.querySelector(
                        ".actual-value"
                    );

                const gauge =
                    row.querySelector(
                        ".gauge-reading-input"
                    );

                const deadWeight =
                    row.querySelector(
                        ".dead-weight-input"
                    );


                if (!actualCell) {
                    return;
                }


                const percentage =
                    getNumber(
                        actualCell.dataset.percentage
                    );


                const actual =
                    calculateActual(
                        range,
                        percentage
                    );


                actualCell.textContent =
                    actual === null
                        ? "—"
                        : formatNumber(actual);


                /* Keep automatic readings synchronized */

                if (
                    gauge &&
                    !gauge.dataset.edited
                ) {

                    gauge.value =
                        actual === null
                            ? ""
                            : formatNumber(actual);

                }


                if (
                    deadWeight &&
                    !deadWeight.dataset.edited
                ) {

                    deadWeight.value =
                        actual === null
                            ? ""
                            : formatNumber(actual);

                }


                /* Update error */

                if (gauge) {

                    const gaugeValue =
                        getNumber(
                            gauge.value
                        );

                    const error =
                        calculateError(
                            gaugeValue,
                            actual,
                            range
                        );


                    const errorCell =
                        row.querySelector(
                            ".error-value"
                        );


                    if (errorCell) {

                        errorCell.textContent =
                            formatNumber(error)
                            + "%";

                    }

                }

            }
        );

}


/* =========================================
   MARK EDITED INPUTS
========================================= */

function setupReadingTracking() {

    document
        .querySelectorAll(
            ".gauge-reading-input, .dead-weight-input"
        )
        .forEach(
            input => {

                input.addEventListener(
                    "input",
                    function () {

                        this.dataset.edited =
                            "true";

                    }
                );

            }
        );

}


/* =========================================
   CALCULATE ALL
========================================= */

function calculateAll() {

    const range =
        getNumber(
            $("instrumentRange")?.value
        );


    if (
        range === null ||
        range <= 0
    ) {

        setStatus(
            "Please enter a valid instrument range.",
            "warning"
        );

        return false;
    }


    updateActualValues();


    let errors =
        0;


    document
        .querySelectorAll(
            ".error-value"
        )
        .forEach(
            cell => {

                const value =
                    parseFloat(
                        cell.textContent
                    );


                if (
                    Number.isFinite(value) &&
                    value !== 0
                ) {

                    errors++;

                }

            }
        );


    if (errors === 0) {

        setStatus(
            "READY — No error detected.",
            "success"
        );

    } else {

        setStatus(
            "Calculation complete — " +
            errors +
            " reading(s) have an error.",
            "warning"
        );

    }


    return true;
}


/* =========================================
   STATUS
========================================= */

function setStatus(
    message,
    type = ""
) {

    const status =
        $("calculationStatus");

    if (!status) {
        return;
    }

    status.textContent =
        message;

    status.className =
        "status";

    if (type) {
        status.classList.add(type);
    }

}


/* =========================================
   CLEAR
========================================= */

function clearForm() {

    if (
        !confirm(
            "Clear all gauge calibration data?"
        )
    ) {
        return;
    }


    document
        .querySelectorAll(
            "input"
        )
        .forEach(
            input => {

                input.value =
                    "";

                delete input.dataset.edited;

            }
        );


    rebuildTables();

    setDefaultDates();


    setStatus(
        "READY",
        "success"
    );

}


/* =========================================
   DEFAULT DATES
========================================= */

function setDefaultDates() {

    const now =
        new Date();


    const today =
        now.toISOString()
            .split("T")[0];


    const due =
        new Date(now);

    due.setFullYear(
        due.getFullYear() + 1
    );


    const dueDate =
        due.toISOString()
            .split("T")[0];


    const dateFields = [

        "calibrationDate",

        "equipmentCalibrationDate",

        "testedDate",

        "witnessedDate"

    ];


    dateFields.forEach(
        id => {

            const field =
                $(id);

            if (
                field &&
                !field.value
            ) {

                field.value =
                    today;

            }

        }
    );


    const dueFields = [

        "dueDate",

        "equipmentDueDate"

    ];


    dueFields.forEach(
        id => {

            const field =
                $(id);

            if (
                field &&
                !field.value
            ) {

                field.value =
                    dueDate;

            }

        }
    );

}


/* =========================================
   RANGE UNIT / TABLE HEADERS
========================================= */

function updateTableUnits() {

    const unitField = $("rangeUnit");
    const unit = (unitField?.value || "BAR").trim() || "BAR";

    // Update every unit shown in both Rising and Falling tables.
    document
        .querySelectorAll("[data-range-unit], .calibration-table .unit-label")
        .forEach(label => {
            label.textContent = `(${unit})`;
        });
}


function setupRangeUnit() {

    const rangeUnit =
        $("rangeUnit");

    if (!rangeUnit) {
        return;
    }

    rangeUnit.addEventListener(
        "input",
        updateTableUnits
    );

    rangeUnit.addEventListener(
        "change",
        updateTableUnits
    );

    // Set the correct unit immediately on page load.
    updateTableUnits();
}


/* =========================================
   RANGE
========================================= */

function setupRange() {

    const range =
        $("instrumentRange");

    if (!range) {
        return;
    }


    range.addEventListener(
        "input",
        updateActualValues
    );

    range.addEventListener(
        "change",
        updateActualValues
    );

}


/* =========================================
   COLLECT TABLE READINGS
========================================= */

function collectReadings(
    tableId
) {

    const table =
        $(tableId);

    const readings =
        [];


    if (!table) {
        return readings;
    }


    table
        .querySelectorAll("tbody tr")
        .forEach(
            row => {

                const actualCell =
                    row.querySelector(
                        ".actual-value"
                    );

                const deadWeight =
                    row.querySelector(
                        ".dead-weight-input"
                    );

                const gauge =
                    row.querySelector(
                        ".gauge-reading-input"
                    );

                const errorCell =
                    row.querySelector(
                        ".error-value"
                    );


                if (
                    !actualCell ||
                    !deadWeight ||
                    !gauge ||
                    !errorCell
                ) {
                    return;
                }


                const percentage =
                    getNumber(
                        actualCell.dataset.percentage
                    );


                const actual =
                    getNumber(
                        actualCell.textContent
                    );


                const deadWeightValue =
                    getNumber(
                        deadWeight.value
                    );


                const gaugeValue =
                    getNumber(
                        gauge.value
                    );


                const error =
                    getNumber(
                        errorCell.textContent
                            .replace("%", "")
                    );


                readings.push({

                    percentage:
                        percentage ?? 0,

                    actual:
                        actual ?? 0,

                    dead_weight:
                        deadWeightValue ?? 0,

                    gauge:
                        gaugeValue ?? 0,

                    error:
                        error ?? 0

                });

            }
        );


    return readings;
}


/* =========================================
   ADD HIDDEN FIELD
========================================= */

function addHidden(
    form,
    name,
    value
) {

    const input =
        document.createElement("input");

    input.type =
        "hidden";

    input.name =
        name;

    input.value =
        value ?? "";


    form.appendChild(
        input
    );

}


/* =========================================
   SAVE CALIBRATION
========================================= */

function saveCalibration() {

    /* =====================================
       VALIDATE CALCULATION
    ===================================== */

    if (!calculateAll()) {
        return;
    }


    const saveButton = $("saveButton");

    if (
        saveButton &&
        saveButton.dataset.saving === "true"
    ) {
        return;
    }


    /* =====================================
       REQUIRED BASIC FIELDS
    ===================================== */

    const requiredFields = [
        ["certificateNo", "Please enter a certificate number."],
        ["client", "Please enter the client name."],
        ["instrumentRange", "Please enter a valid instrument range."]
    ];

    for (const [id, message] of requiredFields) {

        const field = $(id);

        if (!field || !String(field.value || "").trim()) {

            setStatus(message, "warning");

            field?.focus();

            return;
        }
    }


    const range = getNumber($("instrumentRange")?.value);

    if (range === null || range <= 0) {

        setStatus(
            "Please enter a valid instrument range.",
            "warning"
        );

        $("instrumentRange")?.focus();

        return;
    }


    /* =====================================
       CALIBRATION EQUIPMENT
       IMPORTANT: USE THIS PROJECT'S REAL
       FIELD: equipmentName
    ===================================== */

    const equipmentNameField = $("equipmentName");

    const equipmentName =
        equipmentNameField
            ? String(equipmentNameField.value || "").trim()
            : "";


    if (!equipmentName) {

        setStatus(
            "Please enter the calibration equipment name.",
            "warning"
        );

        equipmentNameField?.focus();

        return;
    }


    /* =====================================
       LOCK SAVE BUTTON
    ===================================== */

    if (saveButton) {

        saveButton.disabled = true;
        saveButton.dataset.saving = "true";
        saveButton.textContent = "SAVING...";

    }


    /* =====================================
       CREATE POST FORM
    ===================================== */

    const form = document.createElement("form");

    form.method = "POST";
    form.action = "save-gauge-calibration.php";
    form.enctype = "multipart/form-data";
    form.style.display = "none";


    /* =====================================
       ADD FIELD HELPER
    ===================================== */

    function addSaveField(name, value) {

        const input = document.createElement("input");

        input.type = "hidden";
        input.name = name;
        input.value = value == null ? "" : String(value);

        form.appendChild(input);
    }


    /* =====================================
       NORMAL FIELDS
    ===================================== */

    const fieldIds = {

        certificate_no: "certificateNo",
        client: "client",
        nuprc: "nuprc",
        test_item: "testItem",
        manufacturer: "manufacturer",
        serial_no: "serialNo",
        gauge_connection: "gaugeConnection",
        instrument_range: "instrumentRange",
        range_unit: "rangeUnit",
        calibration_date: "calibrationDate",
        due_date: "dueDate",

        equipment_name: "equipmentName",
        equipment_serial: "equipmentSerial",
        equipment_range: "equipmentRange",
        equipment_qty: "equipmentQuantity",
        equipment_calibration_date: "equipmentCalibrationDate",
        equipment_due_date: "equipmentDueDate",
        equipment_certification_no: "equipmentCertification",

        tested_by: "testedBy",
        witnessed_by: "witnessedBy"
    };


    Object.entries(fieldIds).forEach(([name, id]) => {

        const source = $(id);

        addSaveField(
            name,
            source ? source.value : ""
        );

    });


    /* =====================================
       SEND EQUIPMENT NAME EXPLICITLY
       (This is the bug fix.)
    ===================================== */

    addSaveField(
        "equipment_name",
        equipmentName
    );


    /* =====================================
       SIGNATURE DATE
    ===================================== */

    addSaveField(
        "signature_date",
        $("testedDate")?.value || ""
    );


    /* =====================================
       RISING READINGS
    ===================================== */

    const rising = collectReadings("risingTable");

    rising.forEach((reading, index) => {

        addSaveField(
            `rising[${index}][percentage]`,
            reading.percentage
        );

        addSaveField(
            `rising[${index}][actual]`,
            reading.actual
        );

        addSaveField(
            `rising[${index}][dead_weight]`,
            reading.dead_weight
        );

        addSaveField(
            `rising[${index}][gauge]`,
            reading.gauge
        );

        addSaveField(
            `rising[${index}][error]`,
            reading.error
        );

    });


    /* =====================================
       FALLING READINGS
    ===================================== */

    const falling = collectReadings("fallingTable");

    falling.forEach((reading, index) => {

        addSaveField(
            `falling[${index}][percentage]`,
            reading.percentage
        );

        addSaveField(
            `falling[${index}][actual]`,
            reading.actual
        );

        addSaveField(
            `falling[${index}][dead_weight]`,
            reading.dead_weight
        );

        addSaveField(
            `falling[${index}][gauge]`,
            reading.gauge
        );

        addSaveField(
            `falling[${index}][error]`,
            reading.error
        );

    });


    /* =====================================
       FINAL SAFETY CHECK
    ===================================== */

    const sentEquipmentInput =
        form.querySelector('input[name="equipment_name"]');

    if (
        !sentEquipmentInput ||
        !String(sentEquipmentInput.value).trim()
    ) {

        if (saveButton) {
            saveButton.disabled = false;
            delete saveButton.dataset.saving;
            saveButton.textContent = "SAVE CERTIFICATE";
        }

        setStatus(
            "Calibration equipment name is required.",
            "warning"
        );

        equipmentNameField?.focus();

        return;
    }


    /* =====================================
       SUBMIT AS MULTIPART FORM DATA
    ===================================== */

    document.body.appendChild(form);

    const formData = new FormData(form);
    const testedSignature = $("testedSignature");
    const witnessedSignature = $("witnessedSignature");
    const stampImage = $("stampImage");

    if (testedSignature?.files?.[0]) {
        formData.delete("tested_signature");
        formData.append("tested_signature", testedSignature.files[0]);
    }

    if (witnessedSignature?.files?.[0]) {
        formData.delete("witnessed_signature");
        formData.append("witnessed_signature", witnessedSignature.files[0]);
    }

    if (stampImage?.files?.[0]) {
        formData.delete("stamp_image");
        formData.append("stamp_image", stampImage.files[0]);
    }

    fetch("save-gauge-calibration.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin"
    })
    .then(async response => {
        const text = await response.text();

        /* PHP returns a full HTML response when a certificate number already exists.
           Because SAVE uses fetch(), that HTML is never executed in the browser.
           Detect the duplicate response here and show our CMS modal instead. */
        if (text.includes("CERTIFICATE NUMBER ALREADY USED") ||
            text.includes("Certificate Number Already Used")) {
            const certificateNumber = String($("certificateNo")?.value || "").trim();
            showDuplicateCertificateModal(certificateNumber);

            form.remove();
            if (saveButton) {
                saveButton.disabled = false;
                delete saveButton.dataset.saving;
                saveButton.textContent = "SAVE CERTIFICATE";
            }
            return;
        }

        if (!response.ok) throw new Error(text || "Unable to save calibration.");
        if (response.redirected) { window.location.href = response.url; return; }
        window.location.href = "../certificate.php";
    })
    .catch(error => {
        console.error(error);
        form.remove();
        if (saveButton) { saveButton.disabled=false; delete saveButton.dataset.saving; saveButton.textContent="SAVE CERTIFICATE"; }
        setStatus(error.message || "Unable to save calibration.", "warning");
    });
}


/* =========================================
   DUPLICATE CERTIFICATE MODAL
========================================= */
function showDuplicateCertificateModal(certificateNumber) {
    const existing = document.getElementById("duplicateCertificateModal");
    if (existing) existing.remove();

    const overlay = document.createElement("div");
    overlay.id = "duplicateCertificateModal";
    overlay.innerHTML = `
        <div class="cms-duplicate-modal" role="dialog" aria-modal="true" aria-labelledby="duplicateCertificateTitle">
            <div class="cms-duplicate-icon">⚠</div>
            <h2 id="duplicateCertificateTitle">CERTIFICATE NUMBER ALREADY USED</h2>
            <p class="cms-duplicate-number">Certificate No: <strong>${escapeHtmlModal(certificateNumber)}</strong></p>
            <p>This certificate number is already registered in the system.</p>
            <p>The certificate was <strong>NOT saved</strong>.</p>
            <button type="button" id="duplicateCertificateOk">OK, CHANGE CERTIFICATE NO</button>
        </div>`;

    document.body.appendChild(overlay);
    document.body.classList.add("cms-modal-open");

    const close = () => {
        overlay.remove();
        document.body.classList.remove("cms-modal-open");
        const field = $("certificateNo");
        if (field) {
            field.focus();
            field.select?.();
        }
    };

    document.getElementById("duplicateCertificateOk")?.addEventListener("click", close);
    overlay.addEventListener("click", event => {
        if (event.target === overlay) close();
    });
}

function escapeHtmlModal(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/* =========================================
   SIGNATURE PREVIEW
========================================= */
function setupImageUpload(inputId, previewId, wrapId, removeId, label) {
    const input = $(inputId);
    const preview = $(previewId);
    const wrap = $(wrapId);
    const remove = $(removeId);

    if (!input || !preview || !wrap) {
        return;
    }

    input.addEventListener("change", () => {
        const file = input.files?.[0];

        if (!file) {
            wrap.hidden = true;
            preview.removeAttribute("src");
            return;
        }

        if (!/^image\/(png|jpeg|webp)$/i.test(file.type)) {
            setStatus(
                `${label} must be PNG, JPG or WebP.`,
                "warning"
            );
            input.value = "";
            wrap.hidden = true;
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            setStatus(
                `${label} image must not exceed 2 MB.`,
                "warning"
            );
            input.value = "";
            wrap.hidden = true;
            return;
        }

        const reader = new FileReader();

        reader.onload = event => {
            preview.src = event.target.result;
            wrap.hidden = false;
        };

        reader.readAsDataURL(file);
    });

    remove?.addEventListener("click", () => {
        input.value = "";
        preview.removeAttribute("src");
        wrap.hidden = true;
    });
}

function setupSignatureUpload(inputId, previewId, wrapId, removeId) {
    setupImageUpload(
        inputId,
        previewId,
        wrapId,
        removeId,
        "Signature"
    );
}

function setupStampUpload() {
    setupImageUpload(
        "stampImage",
        "stampPreview",
        "stampPreviewWrap",
        "removeStamp",
        "Stamp"
    );
}

/* =========================================
   BUTTONS
========================================= */

function setupButtons() {

    $("calculateButton")
        ?.addEventListener(
            "click",
            calculateAll
        );


    $("clearButton")
        ?.addEventListener(
            "click",
            clearForm
        );


    $("saveButton")
        ?.addEventListener(
            "click",
            saveCalibration
        );

}


/* =========================================
   INITIALIZE
========================================= */

function initializeGauge() {

    setDefaultDates();

    rebuildTables();

    setupReadingTracking();

    setupRange();
    setupRangeUnit();

    setupSignatureUpload("testedSignature","testedSignaturePreview","testedSignaturePreviewWrap","removeTestedSignature");
    setupSignatureUpload("witnessedSignature","witnessedSignaturePreview","witnessedSignaturePreviewWrap","removeWitnessedSignature");

    setupStampUpload();

    setupButtons();

    setStatus(
        "READY",
        "success"
    );

}


/* =========================================
   START
========================================= */

document.addEventListener(
    "DOMContentLoaded",
    initializeGauge
);