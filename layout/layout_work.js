document.addEventListener("DOMContentLoaded", () => {
    const viewModal = document.getElementById("viewWorkModal");
    const sendModal = document.getElementById("sendWorkModal");

    viewModal.addEventListener("show.bs.modal", (event) => {
        const button = event.relatedTarget;
        const details = [
            "invoice",
            "customer",
            "contact",
            "service",
            "quantity",
            "price",
            "due",
            "address",
            "landmarks",
            "notes",
        ];

        details.forEach((key) => {
            const value = button.dataset[key] || "";
            const detail = viewModal.querySelector(`[data-work-detail="${key}"]`);
            detail.textContent = value.trim() || "Not provided";
        });

        const fileUrl = button.dataset.file || "";
        const referenceSection = document.getElementById("workReferenceSection");
        const referenceEmpty = document.getElementById("workReferenceEmpty");
        const referenceImage = document.getElementById("workReferenceImage");
        const referenceLink = document.getElementById("workReferenceLink");

        referenceSection.hidden = !fileUrl;
        referenceEmpty.hidden = Boolean(fileUrl);
        if (fileUrl) {
            referenceImage.src = fileUrl;
            referenceLink.href = fileUrl;
        } else {
            referenceImage.removeAttribute("src");
            referenceLink.removeAttribute("href");
        }
    });

    sendModal.addEventListener("show.bs.modal", (event) => {
        const invoiceId = event.relatedTarget.dataset.invoice || "";
        document.getElementById("sendWorkInvoiceId").value = invoiceId;
        document.getElementById("sendWorkInvoiceLabel").textContent = invoiceId;
    });

    sendModal.addEventListener("hidden.bs.modal", () => {
        sendModal.querySelector("form").reset();
    });
});
