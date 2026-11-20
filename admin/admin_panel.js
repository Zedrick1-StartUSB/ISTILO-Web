document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-open-admin-modal]").forEach(function (button) {
        var dialogId = button.getAttribute("data-open-admin-modal");
        var dialog = dialogId ? document.getElementById(dialogId) : null;
        if (!dialog || typeof dialog.showModal !== "function") {
            return;
        }

        button.addEventListener("click", function () {
            if (!dialog.open) {
                dialog.showModal();
            }
        });
    });

    var invoiceDetailsDialog = document.getElementById("invoice-details-dialog");
    document.querySelectorAll("[data-view-invoice]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!invoiceDetailsDialog || typeof invoiceDetailsDialog.showModal !== "function") {
                return;
            }

            var invoiceDetails;
            try {
                invoiceDetails = JSON.parse(button.getAttribute("data-invoice-details") || "{}");
            } catch (error) {
                console.error("Unable to read invoice details.", error);
                return;
            }

            invoiceDetailsDialog.querySelectorAll("[data-invoice-detail]").forEach(function (field) {
                var name = field.getAttribute("data-invoice-detail");
                var value = invoiceDetails[name];
                if (value === null || typeof value === "undefined" || value === "") {
                    value = "—";
                }

                if (name === "price" && Number.isFinite(Number(value))) {
                    value = new Intl.NumberFormat("en-PH", {
                        style: "currency",
                        currency: "PHP",
                    }).format(Number(value));
                }

                field.textContent = String(value);
            });

            var completeInvoiceInput = document.getElementById("complete-project-invoice-id");
            if (completeInvoiceInput) {
                completeInvoiceInput.value = String(invoiceDetails.invoice_id || "");
            }

            if (!invoiceDetailsDialog.open) {
                invoiceDetailsDialog.showModal();
            }
        });
    });

    document.querySelectorAll("[data-complete-project-form]").forEach(function (form) {
        form.addEventListener("submit", function (event) {
            var invoiceInput = form.querySelector("[name='complete-project']");
            var invoiceId = invoiceInput ? invoiceInput.value : "";
            if (!window.confirm("Mark invoice #" + invoiceId + " as completed and move it to Completed Projects?")) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll("[data-close-admin-modal]").forEach(function (button) {
        var dialog = button.closest("dialog");
        if (!dialog || typeof dialog.close !== "function") {
            return;
        }

        button.addEventListener("click", function () {
            if (dialog.open) {
                dialog.close();
            }
        });
    });
});
