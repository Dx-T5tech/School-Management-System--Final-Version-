(() => {
    const menuButton = document.querySelector("[data-menu-toggle]");
    const sidebar = document.getElementById("dashboard-sidebar");
    const dateElement = document.querySelector("[data-current-date]");
    const navigationLinks = sidebar?.querySelectorAll('a[href^="#"]') ?? [];

    const errorSummary = document.querySelector("[data-error-summary]");
    const errorFocusTarget = document.querySelector("[data-error-focus]");
    document.documentElement.classList.add("dashboard-js");
    const statusSummary = document.querySelector("[data-status-summary]");
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    const navigationGroupExpanders = new WeakMap();
    const navigationRole = ["super-admin", "admin", "teacher", "student"].find(
        (role) => sidebar?.classList.contains(`sidebar--${role}`),
    ) || "default";
    const formModalPanels = Array.from(document.querySelectorAll("[data-form-modal]"));
    const formModalDialogs = new Map();

    formModalPanels.forEach((panel, index) => {
        const heading = panel.querySelector("h2, h3");
        if (!(panel instanceof HTMLElement) || !(heading instanceof HTMLElement) || !panel.parentElement) {
            return;
        }

        const originalId = panel.id;
        const dialogId = originalId || `dashboard-form-modal-${index + 1}`;
        const dialog = document.createElement("dialog");
        if (typeof dialog.showModal !== "function") {
            return;
        }

        dialog.className = "form-modal";
        dialog.id = dialogId;
        dialog.dataset.openOnLoad = panel.dataset.modalOpenOnLoad || "false";
        if (!heading.id) {
            heading.id = `${dialogId}-heading`;
        }
        dialog.setAttribute("aria-labelledby", heading.id);

        const modalHeader = document.createElement("div");
        modalHeader.className = "form-modal-header";
        const closeButton = document.createElement("button");
        closeButton.className = "form-modal-close";
        closeButton.type = "button";
        closeButton.textContent = "Close";
        closeButton.setAttribute("aria-label", `Close ${heading.textContent?.trim() || "form"} dialog`);
        closeButton.addEventListener("click", () => dialog.close());
        modalHeader.append(closeButton);

        const showFormDialog = () => {
            if (dialog.open) {
                return;
            }
            dialog.showModal();
            if (errorSummary instanceof HTMLElement && dialog.contains(errorSummary)) {
                errorSummary.focus();
                return;
            }
            const firstField = dialog.querySelector(
                dialog.dataset.openOnLoad === "true"
                    ? '[aria-invalid="true"], [autofocus], input:not([type="hidden"]), select, textarea'
                    : '[autofocus], input:not([type="hidden"]), select, textarea',
            );
            if (firstField instanceof HTMLElement) {
                firstField.focus();
            }
        };

        const triggerClass = panel.dataset.modalTriggerClass || "admin-button";
        const existingAnchor = originalId
            ? Array.from(document.querySelectorAll("a[href]")).find(
                (anchor) => anchor.getAttribute("href") === `#${originalId}`,
            )
            : null;
        const trigger = existingAnchor instanceof HTMLAnchorElement
            ? existingAnchor
            : document.createElement("button");

        if (!(trigger instanceof HTMLAnchorElement)) {
            trigger.type = "button";
            trigger.className = triggerClass;
            trigger.textContent = panel.dataset.modalTriggerLabel || heading.textContent?.trim() || "Open form";
            panel.parentElement.insertBefore(trigger, panel);
        }
        trigger.setAttribute("aria-haspopup", "dialog");
        trigger.setAttribute("aria-controls", dialogId);
        trigger.addEventListener("click", (event) => {
            if (trigger instanceof HTMLAnchorElement) {
                event.preventDefault();
            }
            showFormDialog();
        });

        if (originalId) {
            panel.removeAttribute("id");
        }
        dialog.append(modalHeader, panel);
        document.body.append(dialog);
        formModalDialogs.set(dialogId, dialog);

        dialog.addEventListener("click", (event) => {
            if (event.target !== dialog) {
                return;
            }
            const bounds = dialog.getBoundingClientRect();
            const clickedOutside = event.clientX < bounds.left
                || event.clientX > bounds.right
                || event.clientY < bounds.top
                || event.clientY > bounds.bottom;
            if (clickedOutside) {
                dialog.close();
            }
        });
    });

    const openFormModal = Array.from(formModalDialogs.entries()).find(([dialogId, dialog]) => (
        dialog.dataset.openOnLoad === "true" || window.location.hash === `#${dialogId}`
    ));
    if (openFormModal) {
        const [dialogId, dialog] = openFormModal;
        if (dialog.dataset.openOnLoad === "true" && errorSummary instanceof HTMLElement) {
            const panel = dialog.querySelector(".admin-panel, .teacher-panel, .student-panel");
            if (panel) {
                dialog.insertBefore(errorSummary, panel);
            }
        }
        const dialogTrigger = document.querySelector(`[aria-controls="${dialogId}"]`);
        if (dialogTrigger instanceof HTMLElement) {
            dialogTrigger.focus();
            dialogTrigger.click();
        } else {
            dialog.showModal();
        }
    }

    document.querySelectorAll(".sidebar-nav-group").forEach((group) => {
        const heading = group.querySelector(".sidebar-nav-group-label");
        const links = group.querySelector(".sidebar-nav-group-list");
        if (!(heading instanceof HTMLElement) || !(links instanceof HTMLElement)) {
            return;
        }

        const groupLabel = heading.textContent?.trim() ?? "";
        const preferenceKey = `isms.sidebar.navigation.v1.${navigationRole}.${groupLabel}`;
        let savedExpanded = null;
        try {
            const savedPreference = window.localStorage.getItem(preferenceKey);
            if (savedPreference === "true" || savedPreference === "false") {
                savedExpanded = savedPreference === "true";
            }
        } catch (error) {
            console.warn("Unable to read sidebar navigation preferences.", error);
        }
        const initiallyExpanded = savedExpanded
            ?? Boolean(group.querySelector(".sidebar-link.is-current, .sidebar-subnav-link.is-current"));
        const toggle = document.createElement("button");
        toggle.className = "sidebar-nav-group-toggle";
        toggle.type = "button";
        toggle.textContent = heading.textContent?.trim() ?? "";
        toggle.setAttribute("aria-controls", links.id);
        toggle.setAttribute("aria-expanded", String(initiallyExpanded));
        heading.replaceChildren(toggle);
        links.hidden = !initiallyExpanded;
        links.inert = !initiallyExpanded;
        let collapseTimeout = 0;

        const finishCollapse = () => {
            if (!links.classList.contains("is-closing")) {
                return;
            }
            window.clearTimeout(collapseTimeout);
            links.hidden = true;
            links.classList.remove("is-closing");
        };

        const setExpanded = (shouldExpand) => {
            window.clearTimeout(collapseTimeout);
            toggle.setAttribute("aria-expanded", String(shouldExpand));
            links.classList.remove("is-opening", "is-closing");

            if (shouldExpand) {
                const wasHidden = links.hidden;
                links.hidden = false;
                links.inert = false;
                if (wasHidden && !reducedMotion.matches) {
                    void links.offsetWidth;
                    links.classList.add("is-opening");
                }
                return;
            }

            if (links.contains(document.activeElement)) {
                toggle.focus();
            }
            links.inert = true;
            if (reducedMotion.matches) {
                links.hidden = true;
                return;
            }
            links.classList.add("is-closing");
            collapseTimeout = window.setTimeout(
                finishCollapse,
                160,
            );
        };
        navigationGroupExpanders.set(group, () => setExpanded(true));
        toggle.addEventListener("click", () => {
            const shouldExpand = toggle.getAttribute("aria-expanded") !== "true";
            setExpanded(shouldExpand);
            try {
                window.localStorage.setItem(preferenceKey, String(shouldExpand));
            } catch (error) {
                console.warn("Unable to save sidebar navigation preferences.", error);
            }
        });

        links.addEventListener("animationend", (event) => {
            if (event.target === links) {
                finishCollapse();
            }
        });

        links.addEventListener("animationcancel", finishCollapse);
    });

    let navigationTimeout = 0;
    document.addEventListener("click", (event) => {
        if (
            event.defaultPrevented
            || event.button !== 0
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey
            || !(event.target instanceof Element)
        ) {
            return;
        }

        const link = event.target.closest("a[href]");
        if (
            !(link instanceof HTMLAnchorElement)
            || link.target !== ""
            || link.hasAttribute("download")
            || link.href.startsWith("javascript:")
        ) {
            return;
        }

        const destination = new URL(link.href, window.location.href);
        if (
            destination.origin !== window.location.origin
            || (destination.pathname === window.location.pathname
                && destination.search === window.location.search)
        ) {
            return;
        }

        if (reducedMotion.matches || document.body.classList.contains("is-transitioning-out")) {
            return;
        }

        event.preventDefault();
        document.body.classList.add("is-transitioning-out");
        navigationTimeout = window.setTimeout(() => {
            navigationTimeout = 0;
            window.location.assign(destination.href);
        }, 140);
    });
    window.addEventListener("pagehide", () => {
        window.clearTimeout(navigationTimeout);
        navigationTimeout = 0;
    });
    window.addEventListener("pageshow", () => {
        window.clearTimeout(navigationTimeout);
        navigationTimeout = 0;
        document.body.classList.remove("is-transitioning-out");
    });

    document.querySelector("[data-print-report]")?.addEventListener("click", () => {
        window.print();
    });
    if (errorFocusTarget instanceof HTMLElement) {
        errorFocusTarget.focus();
    } else if (errorSummary) {
        errorSummary.focus();
    } else {
        const invalidPaymentField = document.querySelector('.student-payment-form [aria-invalid="true"]');
        if (invalidPaymentField instanceof HTMLElement) {
            invalidPaymentField.focus();
        } else {
            statusSummary?.focus();
        }
    }

    const paymentForm = document.querySelector(".student-payment-form");
    if (paymentForm) {
        const feeSelect = paymentForm.querySelector("#student-payment-fee");
        const termSelect = paymentForm.querySelector("#student-payment-term");
        if (feeSelect instanceof HTMLSelectElement && termSelect instanceof HTMLSelectElement) {
            const updateTuitionTermRequirement = () => {
                const selectedFee = feeSelect.options[feeSelect.selectedIndex];
                termSelect.required = selectedFee?.dataset.category === "Tuition";
            };
            feeSelect.addEventListener("change", updateTuitionTermRequirement);
            updateTuitionTermRequirement();
        }
    }

    document.querySelectorAll("form[data-auto-submit-on-change]").forEach((form) => {
        if (!(form instanceof HTMLFormElement) || typeof form.requestSubmit !== "function") {
            return;
        }

        const filters = Array.from(form.querySelectorAll("select")).filter(
            (filter) => filter instanceof HTMLSelectElement,
        );
        if (filters.length === 0) {
            return;
        }

        const fallbackSubmit = form.querySelector("[data-auto-submit-fallback]");
        if (fallbackSubmit instanceof HTMLButtonElement) {
            fallbackSubmit.hidden = true;
        }

        filters.forEach((filter) => {
            filter.addEventListener("change", () => form.requestSubmit());
        });
    });

    document.querySelectorAll("[data-confirm-deactivation]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            const adminName = form.dataset.adminName || "this Admin account";
            const accountType = form.dataset.accountType || "Admin";
            const confirmed = window.confirm(
                `Deactivate the ${accountType} account for ${adminName}? This action cannot be undone from this page.`,
            );
            if (!confirmed) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll("[data-confirm-delete]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            const recordLabel = form.dataset.recordLabel || "this record";
            const actionLabel = form.dataset.deleteLabel || "Delete";
            const confirmed = window.confirm(
                `${actionLabel} "${recordLabel}"? This change will be recorded in the system audit log.`,
            );
            if (!confirmed) {
                event.preventDefault();
            }
        });
    });

    function updateCurrentLocation() {
        const currentHash = window.location.hash || "#overview";

        navigationLinks.forEach((link) => {
            const isCurrent = link.hash === currentHash;
            link.classList.toggle("is-current", isCurrent);

            if (isCurrent) {
                link.setAttribute("aria-current", "location");
                const group = link.closest(".sidebar-nav-group");
                if (group) {
                    navigationGroupExpanders.get(group)?.();
                }
            } else {
                link.removeAttribute("aria-current");
            }
        });
    }

    updateCurrentLocation();
    window.addEventListener("hashchange", updateCurrentLocation);

    if (dateElement) {
        const now = new Date();
        dateElement.dateTime = now.toISOString().slice(0, 10);
        dateElement.textContent = new Intl.DateTimeFormat(undefined, {
            weekday: "long",
            month: "long",
            day: "numeric",
            year: "numeric",
        }).format(now);
    }

    if (!menuButton || !sidebar) {
        return;
    }

    menuButton.hidden = false;
    const mobileViewport = window.matchMedia("(max-width: 860px)");

    function setMenuOpen(isOpen, returnFocus = true) {
        if (!mobileViewport.matches) {
            sidebar.classList.remove("is-open");
            menuButton.setAttribute("aria-expanded", "false");
            return;
        }

        sidebar.classList.toggle("is-open", isOpen);
        menuButton.setAttribute("aria-expanded", String(isOpen));

        if (isOpen) {
            const firstLink = sidebar.querySelector(".sidebar-nav a");
            firstLink?.focus();
        } else if (returnFocus) {
            menuButton.focus();
        }
    }

    menuButton.addEventListener("click", () => {
        const isOpen = menuButton.getAttribute("aria-expanded") === "true";
        setMenuOpen(!isOpen);
    });

    sidebar.addEventListener("click", (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const link = event.target.closest('a[href^="#"]');
        if (!link) {
            return;
        }

        const destination = document.getElementById(link.hash.slice(1));
        setMenuOpen(false, false);

        if (destination) {
            window.setTimeout(() => destination.focus({ preventScroll: true }), 0);
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") {
            return;
        }

        const openFormDialog = document.querySelector("dialog.form-modal[open]");
        if (openFormDialog instanceof HTMLDialogElement) {
            openFormDialog.close();
            return;
        }

        if (menuButton.getAttribute("aria-expanded") === "true") {
            setMenuOpen(false);
        }
    });

    mobileViewport.addEventListener("change", () => {
        const focusWasInSidebar = sidebar.contains(document.activeElement);
        const focusWasOnToggle = document.activeElement === menuButton;

        if (mobileViewport.matches && focusWasInSidebar) {
            menuButton.focus();
        }

        sidebar.classList.remove("is-open");
        menuButton.setAttribute("aria-expanded", "false");

        if (!mobileViewport.matches && focusWasOnToggle) {
            sidebar.querySelector(".sidebar-nav a")?.focus();
        }
    });
})();
