(() => {
    const timer = document.querySelector("[data-exam-timer]");
    const form = document.querySelector("[data-exam-form]");
    const value = timer?.querySelector("[data-timer-value]");
    const announcement = document.querySelector("[data-timer-announcement]");

    if (!timer || !form || !value) {
        return;
    }

    let remaining = Number.parseInt(timer.dataset.seconds ?? "", 10);
    if (!Number.isFinite(remaining) || remaining < 0) {
        return;
    }

    const deadline = Date.now() + remaining * 1000;
    let submitted = false;
    let lastAnnouncement = "";
    const saveStatus = document.querySelector("[data-answer-save-status]");
    const answerQueues = new Map();
    const render = () => {
        remaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        value.textContent = `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;

        const announcementText = remaining === 0
            ? "Time is up. Submitting your exam."
            : remaining === 300
                ? "Five minutes remain."
                : remaining === 60
                    ? "One minute remains."
                    : "";
        if (announcementText && announcementText !== lastAnnouncement && announcement) {
            announcement.textContent = announcementText;
            lastAnnouncement = announcementText;
        }

        if (remaining === 0 && !submitted) {
            submitted = true;
            form.requestSubmit();
        }
    };

    form.addEventListener("submit", () => {
        submitted = true;
    });

    form.addEventListener("change", async (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || input.type !== "radio" || !input.checked || submitted) {
            return;
        }

        const questionId = input.name.match(/^answers\[(\d+)\]$/)?.[1];
        const token = form.querySelector('input[name="csrf_token"]')?.value;
        if (!questionId || !token) {
            if (saveStatus) {
                saveStatus.textContent = "Your answer could not be saved. Reload the exam page and try again.";
            }
            return;
        }

        if (saveStatus) {
            saveStatus.textContent = "Saving your answer.";
        }
        const body = new URLSearchParams({
            action: "save_exam_answer",
            csrf_token: token,
            question_id: questionId,
            answer: input.value,
        });
        const previousSave = answerQueues.get(questionId) ?? Promise.resolve();
        const currentSave = previousSave.catch(() => {}).then(async () => {
            const response = await fetch(form.action, {
                method: "POST",
                body,
                credentials: "same-origin",
                headers: { Accept: "application/json" },
            });
            const result = await response.json();
            if (!response.ok || result.saved !== true) {
                throw new Error("The answer save request was rejected.");
            }
        });
        answerQueues.set(questionId, currentSave);
        try {
            await currentSave;
            if (saveStatus && answerQueues.get(questionId) === currentSave) {
                saveStatus.textContent = "Answer saved.";
            }
        } catch {
            if (saveStatus) {
                saveStatus.textContent = "Your answer could not be saved. Stay on this page and try selecting it again.";
            }
        } finally {
            if (answerQueues.get(questionId) === currentSave) {
                answerQueues.delete(questionId);
            }
        }
    });

    render();
    const intervalId = window.setInterval(() => {
        if (submitted) {
            window.clearInterval(intervalId);
            return;
        }
        remaining = Math.max(0, remaining - 1);
        render();
        if (remaining === 0) {
            window.clearInterval(intervalId);
        }
    }, 1000);
})();
