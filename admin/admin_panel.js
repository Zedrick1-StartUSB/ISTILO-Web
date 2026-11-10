const referenceImageInput = document.getElementById("reference-image");
const referenceImagePreview = document.getElementById("reference-image-preview");

if (referenceImageInput && referenceImagePreview) {
    const uploadLabel = referenceImageInput.closest(".upload-area");
    const uploadTitle = uploadLabel.querySelector("strong");
    const uploadDescription = uploadLabel.querySelector("small");
    const defaultTitle = uploadTitle.textContent;
    const defaultDescription = uploadDescription.textContent;
    const maxSize = Number(referenceImageInput.dataset.maxSize);

    referenceImageInput.addEventListener("change", () => {
        const [file] = referenceImageInput.files;
        referenceImagePreview.replaceChildren();

        if (!file) {
            uploadTitle.textContent = defaultTitle;
            uploadDescription.textContent = defaultDescription;
            return;
        }

        if ((file.type && !["image/png", "image/jpeg"].includes(file.type)) || file.size > maxSize) {
            referenceImageInput.value = "";
            uploadTitle.textContent = defaultTitle;
            uploadDescription.textContent = "Choose a PNG/JPEG image up to 5 MB.";
            return;
        }

        uploadTitle.textContent = file.name;
        uploadDescription.textContent = "Reference image selected";
    });

    referenceImageInput.form.addEventListener("reset", () => {
        referenceImagePreview.replaceChildren();
        uploadTitle.textContent = defaultTitle;
        uploadDescription.textContent = defaultDescription;
    });
}
