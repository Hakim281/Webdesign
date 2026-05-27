const state = {
    students: [],
    editingStudentId: null,
    stats: null,
};

const apiUrl = (route) => `api.php?route=${encodeURIComponent(route)}`;

const elements = {
    form: document.getElementById("student-form"),
    studentId: document.getElementById("student-id"),
    admissionNumber: document.getElementById("admission-number"),
    firstName: document.getElementById("first-name"),
    lastName: document.getElementById("last-name"),
    email: document.getElementById("email"),
    phone: document.getElementById("phone"),
    program: document.getElementById("program"),
    yearLevel: document.getElementById("year-level"),
    status: document.getElementById("status"),
    submitButton: document.getElementById("submit-button"),
    resetButton: document.getElementById("reset-button"),
    formTitle: document.getElementById("form-title"),
    formMessage: document.getElementById("form-message"),
    tableBody: document.getElementById("student-table-body"),
    dbStatus: document.getElementById("db-status"),
    recordCount: document.getElementById("record-count"),
    totalStudents: document.getElementById("total-students"),
    activeStudents: document.getElementById("active-students"),
    graduatedStudents: document.getElementById("graduated-students"),
    programCount: document.getElementById("program-count"),
};

async function fetchJson(url, options = {}) {
    const response = await fetch(url, {
        headers: {
            "Content-Type": "application/json",
        },
        ...options,
    });

    const data = await response.json();
    if (!response.ok) {
        throw new Error(data.error || "Request failed.");
    }

    return data;
}

function renderSelectOptions(selectElement, options) {
    selectElement.innerHTML = options
        .map((option) => `<option value="${option}">${option}</option>`)
        .join("");
}

function escapeHtml(value) {
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");
}

function renderStudents() {
    elements.recordCount.textContent = `${state.students.length} students loaded`;

    if (state.students.length === 0) {
        elements.tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-state">No students found in the database yet.</td>
            </tr>
        `;
        return;
    }

    elements.tableBody.innerHTML = state.students
        .map(
            (student) => `
                <tr>
                    <td>${escapeHtml(student.admissionNumber)}</td>
                    <td>
                        <div class="student-name">${escapeHtml(student.firstName)} ${escapeHtml(student.lastName)}</div>
                        <div class="student-email">${escapeHtml(student.email)}</div>
                    </td>
                    <td>${escapeHtml(student.program)}</td>
                    <td>Year ${escapeHtml(student.yearLevel)}</td>
                    <td><span class="status-chip">${escapeHtml(student.status)}</span></td>
                    <td>${escapeHtml(student.phone)}</td>
                    <td>
                        <div class="table-actions">
                            <button class="table-action edit" type="button" data-action="edit" data-student-id="${student.id}">
                                Edit
                            </button>
                            <button class="table-action delete" type="button" data-action="delete" data-student-id="${student.id}">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
            `
        )
        .join("");
}

function renderStats() {
    if (!state.stats) {
        return;
    }

    elements.totalStudents.textContent = state.stats.totalStudents;
    elements.activeStudents.textContent = state.stats.activeStudents;
    elements.graduatedStudents.textContent = state.stats.graduatedStudents;
    elements.programCount.textContent = state.stats.programCount;
}

function setFormMessage(message, isError = false) {
    elements.formMessage.textContent = message;
    elements.formMessage.style.color = isError ? "#8b3f20" : "#0a4f4a";
}

function resetForm() {
    state.editingStudentId = null;
    elements.studentId.value = "";
    elements.form.reset();
    elements.yearLevel.value = "1";
    if (state.stats?.programOptions?.length) {
        elements.program.value = state.stats.programOptions[0];
    }
    if (state.stats?.statusOptions?.length) {
        elements.status.value = "Active";
    }
    elements.formTitle.textContent = "Add a student";
    elements.submitButton.textContent = "Save student";
    setFormMessage("");
}

function fillForm(student) {
    state.editingStudentId = student.id;
    elements.studentId.value = String(student.id);
    elements.admissionNumber.value = student.admissionNumber;
    elements.firstName.value = student.firstName;
    elements.lastName.value = student.lastName;
    elements.email.value = student.email;
    elements.phone.value = student.phone;
    elements.program.value = student.program;
    elements.yearLevel.value = String(student.yearLevel);
    elements.status.value = student.status;
    elements.formTitle.textContent = `Edit ${student.firstName} ${student.lastName}`;
    elements.submitButton.textContent = "Update student";
    setFormMessage("Editing selected student.");
    elements.form.scrollIntoView({ behavior: "smooth", block: "start" });
}

function collectFormPayload() {
    return {
        admissionNumber: elements.admissionNumber.value.trim(),
        firstName: elements.firstName.value.trim(),
        lastName: elements.lastName.value.trim(),
        email: elements.email.value.trim(),
        phone: elements.phone.value.trim(),
        program: elements.program.value,
        yearLevel: Number(elements.yearLevel.value),
        status: elements.status.value,
    };
}

async function loadDashboard() {
    const [health, students, stats] = await Promise.all([
        fetchJson(apiUrl("health")),
        fetchJson(apiUrl("students")),
        fetchJson(apiUrl("stats")),
    ]);

    state.students = students;
    state.stats = stats;

    renderSelectOptions(elements.program, stats.programOptions);
    renderSelectOptions(elements.status, stats.statusOptions);
    elements.dbStatus.textContent = `Connected: ${health.databaseUrl}`;
    renderStudents();
    renderStats();
    resetForm();
}

async function refreshData() {
    const [students, stats] = await Promise.all([
        fetchJson(apiUrl("students")),
        fetchJson(apiUrl("stats")),
    ]);

    state.students = students;
    state.stats = stats;
    renderStudents();
    renderStats();
}

async function handleSubmit(event) {
    event.preventDefault();
    const payload = collectFormPayload();
    const isEditing = Boolean(state.editingStudentId);

    try {
        setFormMessage(isEditing ? "Updating student..." : "Saving student...");
        await fetchJson(
            isEditing ? apiUrl(`students/${state.editingStudentId}`) : apiUrl("students"),
            {
                method: isEditing ? "PUT" : "POST",
                body: JSON.stringify(payload),
            }
        );
        await refreshData();
        resetForm();
        setFormMessage(isEditing ? "Student updated successfully." : "Student saved successfully.");
    } catch (error) {
        setFormMessage(error.message, true);
    }
}

async function handleTableClick(event) {
    const button = event.target.closest("button[data-action]");
    if (!button) {
        return;
    }

    const studentId = Number(button.dataset.studentId);
    const action = button.dataset.action;
    const student = state.students.find((item) => item.id === studentId);
    if (!student) {
        return;
    }

    if (action === "edit") {
        fillForm(student);
        return;
    }

    if (action === "delete") {
        const confirmed = window.confirm(`Delete ${student.firstName} ${student.lastName}?`);
        if (!confirmed) {
            return;
        }

        try {
            setFormMessage("Deleting student...");
            await fetchJson(apiUrl(`students/${studentId}`), { method: "DELETE" });
            await refreshData();
            if (state.editingStudentId === studentId) {
                resetForm();
            }
            setFormMessage("Student deleted successfully.");
        } catch (error) {
            setFormMessage(error.message, true);
        }
    }
}

elements.form.addEventListener("submit", handleSubmit);
elements.resetButton.addEventListener("click", resetForm);
elements.tableBody.addEventListener("click", handleTableClick);

loadDashboard().catch((error) => {
    elements.dbStatus.textContent = "Database connection failed";
    elements.tableBody.innerHTML = `
        <tr>
            <td colspan="7" class="empty-state">${escapeHtml(error.message)}</td>
        </tr>
    `;
    setFormMessage(error.message, true);
});
