document.addEventListener("DOMContentLoaded", function () {
    var viewModal = document.getElementById("viewWorkModal");
    if (viewModal) {
        viewModal.addEventListener("show.bs.modal", function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) {
                return;
            }

            var details = {
                invoice: trigger.dataset.invoice || "",
                customer: trigger.dataset.customer || "",
                contact: trigger.dataset.contact || "",
                address: trigger.dataset.address || "",
                landmarks: trigger.dataset.landmarks || "",
                service: trigger.dataset.service || "",
                quantity: trigger.dataset.quantity || "",
                price: trigger.dataset.price || "",
                due: trigger.dataset.due || "",
                notes: trigger.dataset.notes || ""
            };

            Object.keys(details).forEach(function (key) {
                var target = viewModal.querySelector('[data-work-detail="' + key + '"]');
                if (target) {
                    target.textContent = details[key];
                }
            });

            var referenceUrl = trigger.dataset.file || "";
            var referenceSection = document.getElementById("workReferenceSection");
            var referenceEmpty = document.getElementById("workReferenceEmpty");
            var referenceLink = document.getElementById("workReferenceLink");
            var referenceImage = document.getElementById("workReferenceImage");

            if (referenceUrl) {
                referenceLink.href = referenceUrl;
                referenceImage.src = referenceUrl;
                referenceSection.hidden = false;
                referenceEmpty.hidden = true;
            } else {
                referenceImage.removeAttribute("src");
                referenceLink.removeAttribute("href");
                referenceSection.hidden = true;
                referenceEmpty.hidden = false;
            }
        });
    }

    var sendModal = document.getElementById("sendWorkModal");
    if (sendModal) {
        sendModal.addEventListener("show.bs.modal", function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) {
                return;
            }

            var invoiceId = trigger.dataset.invoice || "";
            var invoiceInput = document.getElementById("sendWorkInvoiceId");
            var invoiceLabel = document.getElementById("sendWorkInvoiceLabel");
            invoiceInput.value = invoiceId;
            invoiceLabel.textContent = invoiceId;
        });
    }
});
