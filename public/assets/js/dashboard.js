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

    const roomMonitoring = document.querySelector("[data-room-monitor]");
    if (roomMonitoring instanceof HTMLElement) {
        const refreshUrl = new URL(roomMonitoring.dataset.roomRefreshUrl, window.location.href);
        const liveStatus = roomMonitoring.querySelector("[data-room-live-status]");
        const updatedTime = roomMonitoring.querySelector("[data-room-updated]");
        const occupiedCount = roomMonitoring.querySelector("[data-room-occupied-count]");
        const availableCount = roomMonitoring.querySelector("[data-room-available-count]");
        let roomRefreshInProgress = false;
        let roomRefreshUnavailable = false;
        const previousRoomStates = new Map();
        roomMonitoring.querySelectorAll("[data-room-card]").forEach((card) => {
            previousRoomStates.set(card.dataset.roomId, {
                status: card.querySelector("[data-room-status]")?.textContent?.trim() ?? "",
                timing: card.querySelector("[data-room-timing]")?.textContent?.trim() ?? "",
                details: card.querySelector("[data-room-details]")?.textContent?.trim() ?? "",
            });
        });

        const roomDialog = roomMonitoring.querySelector("#room-schedule-dialog");
        const roomDialogTitle = roomDialog?.querySelector("#room-dialog-title");
        const roomDialogContext = roomDialog?.querySelector("#room-dialog-context");
        const roomDialogSchedules = roomDialog?.querySelector("[data-room-dialog-schedules]");
        const roomDialogEmpty = roomDialog?.querySelector("[data-room-dialog-empty]");
        const roomDialogClose = roomDialog?.querySelector("[data-room-dialog-close]");
        if (
            roomDialog instanceof HTMLDialogElement
            && roomDialogTitle instanceof HTMLElement
            && roomDialogContext instanceof HTMLElement
            && roomDialogSchedules instanceof HTMLElement
            && roomDialogEmpty instanceof HTMLElement
            && roomDialogClose instanceof HTMLButtonElement
        ) {
            const roomDialogName = roomDialogTitle.querySelector("[data-room-dialog-name]");
            roomMonitoring.querySelectorAll("button[data-room-card]").forEach((card) => {
                card.addEventListener("click", () => {
                    const roomName = card.querySelector(".room-monitor-card-title")?.textContent?.trim()
                        || "Room";
                    if (roomDialogName instanceof HTMLElement) {
                        roomDialogName.textContent = roomName;
                    }
                    roomDialogContext.textContent = card.dataset.roomContext || "";
                    roomDialogSchedules.replaceChildren();
                    roomDialogEmpty.textContent = "";
                    roomDialogEmpty.hidden = true;

                    const schedules = JSON.parse(card.dataset.roomSchedules || "[]");
                    roomDialog.showModal();
                    if (schedules.length === 0) {
                        roomDialogEmpty.textContent = "No more classes are scheduled today.";
                        roomDialogEmpty.hidden = false;
                    } else {
                        schedules.forEach((schedule) => {
                            const item = document.createElement("li");
                            item.className = "room-schedule-item";

                            const time = document.createElement("p");
                            time.className = "room-schedule-time";
                            time.textContent = schedule.time;

                            const details = document.createElement("div");
                            details.className = "room-schedule-details";

                            const subject = document.createElement("h3");
                            subject.textContent = schedule.subject;

                            const section = document.createElement("p");
                            section.textContent = schedule.section;

                            const status = document.createElement("span");
                            status.className = `room-schedule-status room-schedule-status--${schedule.statusKey}`;
                            status.textContent = schedule.status;

                            details.append(subject, section);
                            item.append(time, details, status);
                            roomDialogSchedules.append(item);
                        });
                    }
                });
            });

            roomDialogClose.addEventListener("click", () => roomDialog.close());
            roomDialog.addEventListener("keydown", (event) => {
                if (event.key === "Escape") {
                    event.preventDefault();
                    roomDialog.close();
                }
            });
            roomDialog.addEventListener("click", (event) => {
                if (event.target !== roomDialog) {
                    return;
                }
                const bounds = roomDialog.getBoundingClientRect();
                const clickedOutside = event.clientX < bounds.left
                    || event.clientX > bounds.right
                    || event.clientY < bounds.top
                    || event.clientY > bounds.bottom;
                if (clickedOutside) {
                    roomDialog.close();
                }
            });
        }

        const refreshRoomStatuses = async () => {
            if (roomRefreshInProgress || document.hidden) {
                return;
            }

            roomRefreshInProgress = true;
            try {
                const response = await fetch(refreshUrl, {
                    cache: "no-store",
                    credentials: "same-origin",
                    headers: { Accept: "application/json" },
                });
                if (!response.ok) {
                    throw new Error(`Room status refresh failed with HTTP ${response.status}.`);
                }

                const data = await response.json();
                if (!data || typeof data.roomStates !== "object" || !data.roomStates) {
                    throw new Error("Room status refresh returned an invalid response.");
                }

                const statusClasses = ["available", "occupied", "maintenance", "inactive", "facility"];
                const changedRooms = [];
                roomMonitoring.querySelectorAll("[data-room-card]").forEach((card) => {
                    const state = data.roomStates[card.dataset.roomId];
                    if (!state || typeof state.status !== "string" || !statusClasses.includes(state.statusKey)) {
                        return;
                    }

                    const currentState = {
                        status: state.status,
                        timing: typeof state.timing === "string" ? state.timing : "",
                        details: typeof state.details === "string" ? state.details : "",
                    };
                    const previousState = previousRoomStates.get(card.dataset.roomId);
                    if (previousState && previousState.status !== currentState.status) {
                        const roomName = card.querySelector("h3")?.textContent?.trim() || "A room";
                        const statusChange = `${roomName} is ${currentState.status.toLowerCase()}. ${currentState.timing}`;
                        changedRooms.push(currentState.details === "" ? statusChange : `${statusChange}. ${currentState.details}`);
                    } else if (
                        previousState
                        && (previousState.timing !== currentState.timing || previousState.details !== currentState.details)
                    ) {
                        const roomName = card.querySelector("h3")?.textContent?.trim() || "A room";
                        const scheduleUpdate = `${roomName} schedule details updated. ${currentState.timing}`;
                        changedRooms.push(currentState.details === "" ? scheduleUpdate : `${scheduleUpdate}. ${currentState.details}`);
                    }
                    previousRoomStates.set(card.dataset.roomId, currentState);

                    statusClasses.forEach((statusClass) => {
                        card.classList.remove(`room-monitor-card--${statusClass}`);
                    });
                    card.classList.add(`room-monitor-card--${state.statusKey}`);
                    const status = card.querySelector("[data-room-status]");
                    const timing = card.querySelector("[data-room-timing]");
                    const details = card.querySelector("[data-room-details]");
                    if (status) {
                        status.textContent = state.status;
                    }
                    if (timing) {
                        timing.textContent = currentState.timing;
                    }
                    if (details) {
                        details.textContent = currentState.details;
                    }
                });

                if (occupiedCount && Number.isInteger(data.occupiedCount)) {
                    occupiedCount.textContent = String(data.occupiedCount);
                }
                if (availableCount && Number.isInteger(data.availableCount)) {
                    availableCount.textContent = String(data.availableCount);
                }
                if (updatedTime && typeof data.updatedAt === "string" && typeof data.updatedLabel === "string") {
                    updatedTime.dateTime = data.updatedAt;
                    updatedTime.textContent = data.updatedLabel;
                }
                if (liveStatus) {
                    const updateMessages = [];
                    if (roomRefreshUnavailable) {
                        updateMessages.push("Live room schedule updates have resumed.");
                        roomRefreshUnavailable = false;
                    }
                    if (changedRooms.length > 0) {
                        const visibleChanges = changedRooms.slice(0, 3);
                        updateMessages.push(
                            `Room monitoring updates: ${visibleChanges.join(" ")}${changedRooms.length > visibleChanges.length ? ` ${changedRooms.length - visibleChanges.length} more room(s) changed.` : ""}`,
                        );
                    }
                    const updateMessage = updateMessages.join(" ");
                    if (updateMessage !== "" && liveStatus.textContent !== updateMessage) {
                        liveStatus.textContent = updateMessage;
                    }
                }
            } catch (error) {
                if (!roomRefreshUnavailable) {
                    console.error("Unable to refresh room monitoring data.", error);
                }
                roomRefreshUnavailable = true;
                if (liveStatus && !liveStatus.textContent.startsWith("Live refresh unavailable.")) {
                    liveStatus.textContent = "Live refresh unavailable. Showing the last successful update.";
                }
            } finally {
                roomRefreshInProgress = false;
            }
        };

        window.setInterval(refreshRoomStatuses, 10_000);
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden) {
                refreshRoomStatuses();
            }
        });
    }

    const configurationVersion = document.body.dataset.configurationVersion;
    if (configurationVersion) {
        let currentConfigurationVersion = configurationVersion;
        let refreshPending = false;
        let updateCheckInProgress = false;
        let refreshTimer = null;
        const updateNotice = document.querySelector("[data-configuration-update-status]");
        const updateNoticeBanner = document.querySelector("[data-configuration-update-notice]");
        const updateNoticeMessage = document.querySelector("[data-configuration-update-message]");
        const refreshNowButton = document.querySelector("[data-configuration-refresh]");
        const postponeRefreshButton = document.querySelector("[data-configuration-postpone]");
        const focusStorageKey = "isms.configuration-update-focus";
        const formSnapshots = new WeakMap();
        const editableSnapshots = new WeakMap();
        const formValue = (control) => {
            if (control instanceof HTMLInputElement && (control.type === "checkbox" || control.type === "radio")) {
                return String(control.checked);
            }
            if (control instanceof HTMLSelectElement && control.multiple) {
                return Array.from(control.options)
                    .map((option, index) => option.selected ? String(index) : "")
                    .filter(Boolean)
                    .join(",");
            }
            return control.value;
        };
        document.querySelectorAll("form input, form select, form textarea").forEach((control) => {
            if (
                control.disabled
                || control instanceof HTMLInputElement && [
                    "hidden", "button", "submit", "reset", "image",
                ].includes(control.type)
            ) {
                return;
            }
            formSnapshots.set(control, formValue(control));
        });
        document.querySelectorAll('[contenteditable="true"], [contenteditable=""], [contenteditable="plaintext-only"]').forEach((element) => {
            editableSnapshots.set(element, element.innerHTML);
        });
        const announceUpdateStatus = (message) => {
            if (updateNotice instanceof HTMLElement) {
                updateNotice.textContent = message;
            }
            if (updateNoticeMessage instanceof HTMLElement) {
                updateNoticeMessage.textContent = message;
            }
        };

        const hasUnsavedFormChanges = () => Array.from(document.forms).some((form) => (
            Array.from(form.elements).some((control) => {
                const initialValue = formSnapshots.get(control);
                return initialValue !== undefined && formValue(control) !== initialValue;
            })
        )) || Array.from(document.querySelectorAll('[contenteditable="true"], [contenteditable=""], [contenteditable="plaintext-only"]')).some((element) => (
            editableSnapshots.get(element) !== element.innerHTML
        ));

        const hasActiveEditing = () => {
            const activeElement = document.activeElement;
            return (
                activeElement instanceof HTMLElement
                && (activeElement.closest("form") !== null || activeElement.isContentEditable)
            ) || document.querySelector("dialog[open]") !== null;
        };

        const scheduleRefresh = () => {
            if (!refreshPending || refreshTimer !== null) {
                return;
            }
            refreshTimer = window.setTimeout(() => {
                refreshTimer = null;
                refreshWhenSafe();
            }, 5000);
        };

        refreshNowButton?.addEventListener("click", () => {
            if (hasUnsavedFormChanges() || hasActiveEditing()) {
                announceUpdateStatus("Save or finish your current edits before refreshing.");
                return;
            }
            if (refreshTimer !== null) {
                window.clearTimeout(refreshTimer);
                refreshTimer = null;
            }
            refreshWhenSafe();
        });

        postponeRefreshButton?.addEventListener("click", () => {
            if (refreshTimer !== null) {
                window.clearTimeout(refreshTimer);
                refreshTimer = null;
            }
            refreshTimer = window.setTimeout(() => {
                refreshTimer = null;
                refreshWhenSafe();
            }, 60000);
            announceUpdateStatus("The refresh is postponed for one minute. System settings will still update automatically.");
        });

        const saveFocusForRefresh = () => {
            const activeElement = document.activeElement;
            if (!(activeElement instanceof HTMLElement)) {
                return;
            }
            const focusState = {
                id: activeElement.id,
                name: activeElement.getAttribute("name"),
                tagName: activeElement.tagName,
                ariaLabel: activeElement.getAttribute("aria-label"),
                role: activeElement.getAttribute("role"),
                ariaControls: activeElement.getAttribute("aria-controls"),
                href: activeElement instanceof HTMLAnchorElement ? activeElement.getAttribute("href") : null,
            };
            try {
                window.sessionStorage.setItem(focusStorageKey, JSON.stringify(focusState));
            } catch (error) {
                console.warn("Unable to preserve keyboard focus across the configuration refresh.", error);
            }
        };

        const refreshWhenSafe = () => {
            if (!refreshPending || document.visibilityState === "hidden") {
                return;
            }
            if (hasUnsavedFormChanges() || hasActiveEditing()) {
                return;
            }
            saveFocusForRefresh();
            window.location.reload();
        };

        try {
            const savedFocus = window.sessionStorage.getItem(focusStorageKey);
            if (savedFocus !== null) {
                window.sessionStorage.removeItem(focusStorageKey);
                const focusState = JSON.parse(savedFocus);
                window.requestAnimationFrame(() => {
                    if (
                        document.activeElement instanceof HTMLElement
                        && document.activeElement !== document.body
                        && document.activeElement !== document.documentElement
                    ) {
                        return;
                    }
                    let focusTarget = focusState.id ? document.getElementById(focusState.id) : null;
                    if (!focusTarget && focusState.name) {
                        const nameMatches = document.getElementsByName(focusState.name);
                        focusTarget = nameMatches.length === 1 ? nameMatches[0] : null;
                    }
                    if (!focusTarget && focusState.ariaControls) {
                        const controlsMatches = Array.from(document.querySelectorAll("[aria-controls]")).filter((element) => (
                            element.getAttribute("aria-controls") === focusState.ariaControls
                        ));
                        focusTarget = controlsMatches.length === 1 ? controlsMatches[0] : null;
                    }
                    if (!focusTarget && focusState.href) {
                        const linkMatches = Array.from(document.querySelectorAll("a[href]")).filter((element) => (
                            element.getAttribute("href") === focusState.href
                        ));
                        focusTarget = linkMatches.length === 1 ? linkMatches[0] : null;
                    }
                    if (!focusTarget && focusState.ariaLabel) {
                        const labelMatches = Array.from(document.querySelectorAll("[aria-label]")).filter((element) => (
                            element.tagName === focusState.tagName
                            && element.getAttribute("aria-label") === focusState.ariaLabel
                            && element.getAttribute("role") === focusState.role
                        ));
                        focusTarget = labelMatches.length === 1 ? labelMatches[0] : null;
                    }
                    if (focusTarget instanceof HTMLElement && focusTarget.getClientRects().length > 0) {
                        focusTarget.focus({ preventScroll: true });
                    } else {
                        document.getElementById("main-content")?.focus();
                    }
                });
            }
        } catch (error) {
            console.warn("Unable to restore keyboard focus after the configuration refresh.", error);
        }

        const checkConfigurationVersion = async () => {
            if (updateCheckInProgress || document.visibilityState === "hidden") {
                return;
            }
            updateCheckInProgress = true;
            try {
                const response = await fetch("configuration-version.php", {
                    cache: "no-store",
                    credentials: "same-origin",
                    headers: { Accept: "application/json" },
                });
                if (!response.ok) {
                    throw new Error(`Configuration update check returned HTTP ${response.status}.`);
                }
                const result = await response.json();
                if (typeof result.version !== "string" || !/^\d+$/.test(result.version)) {
                    throw new Error("Configuration update check returned an invalid version.");
                }
                if (result.version !== currentConfigurationVersion) {
                    currentConfigurationVersion = result.version;
                    refreshPending = true;
                    if (updateNoticeBanner instanceof HTMLElement) {
                        updateNoticeBanner.hidden = false;
                    }
                    announceUpdateStatus("System settings changed. This page will refresh automatically; use the update notice to refresh now or postpone.");
                }
                scheduleRefresh();
            } catch (error) {
                console.error("Unable to check for system configuration updates.", error);
            } finally {
                updateCheckInProgress = false;
            }
        };

        window.setInterval(checkConfigurationVersion, 5000);
        document.addEventListener("visibilitychange", checkConfigurationVersion);
        document.addEventListener("focusout", () => window.setTimeout(scheduleRefresh, 0));
        document.addEventListener("close", () => window.setTimeout(scheduleRefresh, 0), true);
        document.addEventListener("reset", () => window.setTimeout(scheduleRefresh, 0), true);
    }

    if (!reducedMotion.matches) {
        document.querySelectorAll(".metric-value").forEach((valueElement) => {
            const originalValue = valueElement.textContent ?? "";
            const numericValue = originalValue.match(/^(\s*[^\d]*)([\d,]+(?:\.\d+)?)(.*)$/);
            if (!numericValue) {
                return;
            }

            const [, prefix, numberText, suffix] = numericValue;
            const target = Number(numberText.replaceAll(",", ""));
            const fractionDigits = numberText.split(".")[1]?.length ?? 0;
            if (!Number.isFinite(target)) {
                return;
            }

            const formatter = new Intl.NumberFormat(undefined, {
                minimumFractionDigits: fractionDigits,
                maximumFractionDigits: fractionDigits,
            });
            const startTime = performance.now();
            const updateCount = (time) => {
                const progress = Math.min(1, (time - startTime) / 1000);
                const easedProgress = 1 - (1 - progress) ** 3;
                valueElement.textContent = progress === 1
                    ? originalValue
                    : `${prefix}${formatter.format(target * easedProgress)}${suffix}`;
                if (progress < 1) {
                    window.requestAnimationFrame(updateCount);
                }
            };

            window.requestAnimationFrame(updateCount);
        });
    }

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

    const assignmentSectionSelect = document.querySelector("[data-assignment-section]");
    const assignmentSubjectSelect = document.querySelector("[data-assignment-subject]");
    const assignmentSemesterSelect = document.querySelector("[data-assignment-semester]");
    const assignmentRelationshipStatus = document.getElementById("admin-teaching-assignments-relationship-status");
    if (
        assignmentSectionSelect instanceof HTMLSelectElement
        && assignmentSubjectSelect instanceof HTMLSelectElement
        && assignmentSemesterSelect instanceof HTMLSelectElement
        && assignmentRelationshipStatus instanceof HTMLElement
    ) {
        const updateAssignmentSubjectOptions = () => {
            const sectionOption = assignmentSectionSelect.selectedOptions[0];
            const semesterOption = assignmentSemesterSelect.selectedOptions[0];
            const sectionYearId = sectionOption?.dataset.academicYearId || "";
            const semesterYearId = semesterOption?.dataset.academicYearId || "";
            const semesterNumber = semesterOption?.dataset.semesterNumber || "";
            const hasMatchingContext = Boolean(
                sectionOption?.value
                && semesterOption?.value
                && sectionYearId === semesterYearId,
            );

            Array.from(assignmentSectionSelect.options).forEach((option) => {
                option.disabled = Boolean(
                    option.value
                    && semesterOption?.value
                    && option.dataset.academicYearId !== semesterYearId,
                );
            });
            Array.from(assignmentSemesterSelect.options).forEach((option) => {
                option.disabled = Boolean(
                    option.value
                    && sectionOption?.value
                    && option.dataset.academicYearId !== sectionYearId,
                );
            });

            Array.from(assignmentSubjectSelect.options).forEach((option) => {
                if (!option.value) {
                    option.disabled = false;
                    return;
                }

                let combinations = [];
                try {
                    combinations = JSON.parse(option.dataset.curriculumCombinations || "[]");
                } catch (error) {
                    console.error("Unable to read curriculum options for a teaching assignment.", error);
                }
                const matchingCombination = hasMatchingContext && combinations.find((combination) => (
                    String(combination.programId) === sectionOption.dataset.programId
                    && String(combination.academicYearId) === sectionYearId
                    && String(combination.yearLevel) === sectionOption.dataset.yearLevel
                    && (
                        combination.semesterNumber === null
                        || String(combination.semesterNumber) === semesterNumber
                    )
                ));
                option.disabled = !matchingCombination;
                if (!matchingCombination) {
                    option.textContent = option.dataset.defaultLabel || option.textContent;
                    return;
                }
                const unitsLabel = matchingCombination.units === null
                    ? ""
                    : ` · ${matchingCombination.units} units`;
                option.textContent = `${matchingCombination.subjectCode} - ${matchingCombination.subjectName}${unitsLabel}`;
            });

            const subjectWasCleared = Boolean(assignmentSubjectSelect.selectedOptions[0]?.disabled);
            if (subjectWasCleared) {
                assignmentSubjectSelect.value = "";
            }

            if (!sectionOption?.value || !semesterOption?.value) {
                assignmentRelationshipStatus.textContent = "";
                return;
            }
            if (sectionYearId !== semesterYearId) {
                assignmentRelationshipStatus.textContent = "Choose a semester in this class section’s academic year.";
                return;
            }
            if (subjectWasCleared) {
                assignmentRelationshipStatus.textContent = "The selected subject is no longer available for this section and semester. Choose another subject.";
                return;
            }
            const hasAvailableSubjects = Array.from(assignmentSubjectSelect.options).some(
                (option) => option.value && !option.disabled,
            );
            if (!hasAvailableSubjects) {
                assignmentRelationshipStatus.textContent = "No curriculum subjects match this section and semester. Add the subject to the curriculum before assigning it.";
                return;
            }
            const availableSubjectCount = Array.from(assignmentSubjectSelect.options).filter(
                (option) => option.value && !option.disabled,
            ).length;
            const selectedSubjectLabel = assignmentSubjectSelect.selectedOptions[0]?.value
                ? ` Selected ${assignmentSubjectSelect.selectedOptions[0].textContent}.`
                : "";
            assignmentRelationshipStatus.textContent = `${availableSubjectCount} curriculum subject${availableSubjectCount === 1 ? "" : "s"} available.${selectedSubjectLabel}`;
        };

        assignmentSectionSelect.addEventListener("change", updateAssignmentSubjectOptions);
        assignmentSemesterSelect.addEventListener("change", updateAssignmentSubjectOptions);
        assignmentSubjectSelect.addEventListener("change", updateAssignmentSubjectOptions);
        updateAssignmentSubjectOptions();
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
