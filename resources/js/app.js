import "./bootstrap";

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-pdf-download]").forEach((link) => {
        link.addEventListener(
            "click",
            () => {
                if (link.dataset.loading === "1") {
                    return;
                }

                link.dataset.loading = "1";
                link.classList.add("pointer-events-none", "opacity-70");
                link.textContent =
                    "Generando tu certificado de forma segura...";
            },
            { once: true },
        );
    });
});
