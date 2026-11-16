document.addEventListener("DOMContentLoaded", function () {
    var updateModal = document.getElementById("updateMaterialModal");
    if (updateModal) {
        updateModal.addEventListener("show.bs.modal", function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) {
                return;
            }

            document.getElementById("updateMaterialKey").value = trigger.dataset.materialKey || "";
            document.getElementById("updateMaterialName").value = trigger.dataset.material || "";
            document.getElementById("updateMaterialQuantity").value = trigger.dataset.quantity || "0";
        });
    }

    var useModal = document.getElementById("useMaterialModal");
    if (useModal) {
        useModal.addEventListener("show.bs.modal", function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) {
                return;
            }

            var materialName = trigger.dataset.material || "";
            var available = trigger.dataset.quantity || "0";
            document.getElementById("useMaterialKey").value = trigger.dataset.materialKey || "";
            document.getElementById("useMaterialName").textContent = materialName;
            document.getElementById("useMaterialAvailable").textContent = available;
            document.getElementById("useMaterialNameInput").value = materialName;
            document.getElementById("useMaterialAvailableInput").value = available;
            document.getElementById("useMaterialQuantity").max = available;
            document.getElementById("useMaterialQuantity").value = "";
        });
    }

    var deleteModal = document.getElementById("deleteMaterialModal");
    if (deleteModal) {
        deleteModal.addEventListener("show.bs.modal", function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) {
                return;
            }

            var materialName = trigger.dataset.material || "";
            document.getElementById("deleteMaterialKey").value = trigger.dataset.materialKey || "";
            document.getElementById("deleteMaterialName").textContent = materialName;
            document.getElementById("deleteMaterialNameInput").value = materialName;
        });
    }
});
