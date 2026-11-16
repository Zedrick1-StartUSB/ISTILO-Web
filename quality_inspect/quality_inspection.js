document.addEventListener("DOMContentLoaded", function () {
    var sendModal = document.getElementById("qaSendWorkModal");
    if (!sendModal) {
        return;
    }

    var departmentSelect = document.getElementById("qaSendDepartment");
    var fileGroup = document.getElementById("qaWorkFileGroup");
    var fileInput = document.getElementById("qaWorkFile");

    function updateFileRequirement() {
        var requiresFile = departmentSelect.value === "production";
        fileGroup.hidden = !requiresFile;
        fileInput.required = requiresFile;
        if (!requiresFile) {
            fileInput.value = "";
        }
    }

    departmentSelect.addEventListener("change", updateFileRequirement);
    updateFileRequirement();

    sendModal.addEventListener("show.bs.modal", function (event) {
        var trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }

        var invoiceId = trigger.dataset.invoice || "";
        document.getElementById("qaSendInvoiceId").value = invoiceId;
        document.getElementById("qaSendInvoiceLabel").textContent = invoiceId;
        departmentSelect.value = "";
        updateFileRequirement();
    });
});
