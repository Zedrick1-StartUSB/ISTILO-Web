document.addEventListener("DOMContentLoaded", function () {
    var sendModal = document.getElementById("qaSendWorkModal");
    if (!sendModal) {
        return;
    }

    var departmentSelect = document.getElementById("qaSendDepartment");

    sendModal.addEventListener("show.bs.modal", function (event) {
        var trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }

        var invoiceId = trigger.dataset.invoice || "";
        document.getElementById("qaSendInvoiceId").value = invoiceId;
        document.getElementById("qaSendInvoiceLabel").textContent = invoiceId;
        departmentSelect.value = "";
    });
});
