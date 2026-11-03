(() => {
    const errorSummary = document.querySelector("[data-error-summary]");
    if (errorSummary instanceof HTMLElement) {
        errorSummary.focus();
    }

    document.querySelectorAll("[data-password-toggle]").forEach((toggle) => {
        const inputId = toggle.getAttribute("aria-controls");
        const passwordInput = inputId ? document.getElementById(inputId) : null;

        if (!(passwordInput instanceof HTMLInputElement)) {
            return;
        }

        toggle.hidden = false;
        toggle.addEventListener("click", () => {
            const shouldShow = passwordInput.type === "password";
            passwordInput.type = shouldShow ? "text" : "password";
            toggle.setAttribute("aria-pressed", String(shouldShow));
            toggle.textContent = shouldShow ? "Hide password" : "Show password";
        });
    });
})();
