document.addEventListener("DOMContentLoaded", () => {
  console.log("admin_dashboard.js loaded");
  loadAllData();
});

let currentAdminId = null; // Global variable to hold logged-in admin ID

// --- Utility fetch wrapper ---
function fetchData(action, callback) {
  fetch(`admin.php?action=${action}`)
    .then(res => res.json())
    .then(data => {
      if (data.success) callback(data.data);
      else console.error(data.message);
    })
    .catch(err => console.error("Fetch error:", err));
}

// --- POST Action handler ---
function postAction(action, data) {
  const formData = new FormData();
  formData.append("action", action);
  for (const key in data) {
    formData.append(key, data[key]);
  }

  fetch("admin.php", {
    method: "POST",
    body: formData
  })
    .then(() => location.reload())
    .catch(err => alert("Action failed: " + err));
}

// --- Load everything at once ---
function loadAllData() {
  fetchData("get_all_data", data => {
    loadAdminProfile(data.admin);
    loadAdminUsers(data.admin_users);
    loadAllowedInstitutions(data.allowed_institutions);
    loadNonAdminUsers(data.non_admin_users);
    loadPendingDatasets(data.pending_datasets);
    loadApprovedDatasets(data.approved_datasets);
  });
}

// --- Admin Info ---
function loadAdminProfile(admin) {
  currentAdminId = admin.id; // Save the current admin ID
  document.getElementById("adminName").textContent = admin.name;
  document.getElementById("adminEmail").textContent = admin.email;
  document.getElementById("adminStaffId").textContent = admin.adminid;
}

// --- Admin Users ---
function loadAdminUsers(admins) {
  const table = document.getElementById("adminUsersTable");
  table.innerHTML = "";

  admins.forEach(admin => {
    const row = `<tr>
      <td>${admin.id}</td>
      <td>${admin.name}</td>
      <td>${admin.email}</td>
      <td>${admin.adminid}</td>
      <td>
        ${admin.id === currentAdminId
          ? "<span class='text-muted'>Can't remove self</span>"
          : `<button class='btn btn-sm btn-danger' onclick='removeAdmin(${admin.id})'>Remove</button>`}
      </td>
    </tr>`;
    table.insertAdjacentHTML("beforeend", row);
  });
}

// --- Allowed Institutions ---
function loadAllowedInstitutions(institutions) {
  const table = document.getElementById("institutionsTable");
  table.innerHTML = "";
  institutions.forEach(inst => {
    const row = `<tr>
      <td>${inst.id}</td>
      <td>${inst.institution_name}</td>
      <td><button class='btn btn-sm btn-danger' onclick='removeInstitution(${inst.id})'>Remove</button></td>
    </tr>`;
    table.insertAdjacentHTML("beforeend", row);
  });
}

// --- Non-admin Users ---
function loadNonAdminUsers(users) {
  const table = document.getElementById("nonAdminUsersTable");
  table.innerHTML = "";
  users.forEach(user => {
    const row = `<tr>
      <td>${user.id}</td>
      <td>${user.name}</td>
      <td>${user.email}</td>
      <td>${user.role}</td>
      <td>${user.is_approved ? "Yes" : "No"}</td>
      <td>
        <button class='btn btn-sm btn-primary' onclick='approveUser(${user.id})'>Approve</button>
        <button class='btn btn-sm btn-danger' onclick='deleteUser(${user.id})'>Delete</button>
      </td>
    </tr>`;
    table.insertAdjacentHTML("beforeend", row);
  });
}

// --- Pending Datasets ---
function loadPendingDatasets(datasets) {
  const tbody = document.getElementById("pending-datasets-body");
  tbody.innerHTML = "";

  if (datasets.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">No pending datasets.</td></tr>`;
    return;
  }

  datasets.forEach(dataset => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${dataset.title}</td>
      <td>${dataset.uploaded_by_name}</td>
      <td>${dataset.file_format}</td>
      <td>${dataset.uploaded_at}</td>
      <td><a href="${dataset.file_path}" target="_blank">Preview</a></td>
      <td>
        <form method="POST" action="admin.php" style="display:inline">
          <input type="hidden" name="action" value="approve_dataset">
          <input type="hidden" name="dataset_id" value="${dataset.id}">
          <button type="submit" class="btn btn-success btn-sm">Approve</button>
        </form>
        <form method="POST" action="admin.php" style="display:inline">
          <input type="hidden" name="action" value="disapprove_dataset">
          <input type="hidden" name="dataset_id" value="${dataset.id}">
          <button type="submit" class="btn btn-danger btn-sm">Disapprove</button>
        </form>
      </td>
    `;
    tbody.appendChild(row);
  });
}

// --- Approved Datasets ---
function loadApprovedDatasets(datasets) {
  const tbody = document.getElementById("approved-datasets-body");
  tbody.innerHTML = "";

  if (datasets.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">No approved datasets.</td></tr>`;
    return;
  }

  datasets.forEach(dataset => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${dataset.title}</td>
      <td>${dataset.uploaded_by_name}</td>
      <td>${dataset.file_format}</td>
      <td>${dataset.uploaded_at}</td>
     <td><button class="btn btn-sm btn-info" onclick="previewDataset('${dataset.file_path}', '${dataset.file_format}')">Preview</button></td>

        <form method="POST" action="admin.php" style="display:inline">
          <input type="hidden" name="action" value="remove_dataset">
          <input type="hidden" name="dataset_id" value="${dataset.id}">
          <button type="submit" class="btn btn-danger btn-sm">Delete</button>
        </form>
      </td>
    `;
    tbody.appendChild(row);
  });
}

// --- User & Dataset Actions ---
function approveUser(id) {
  postAction("approve_user", { user_id: id });
}

function deleteUser(id) {
  if (confirm("Are you sure you want to delete this user?")) {
    postAction("delete_user", { user_id: id });
  }
}

function removeAdmin(id) {
  if (confirm("Remove admin rights from this user?")) {
    postAction("remove_admin_role", { user_id: id });
  }
}

function removeInstitution(id) {
  if (confirm("Remove this institution from the allowed list?")) {
    postAction("remove_allowed_institution", { institution_id: id });
  }
}

// --- Inline Add Admin Form ---
function toggleAdminForm() {
  const formDiv = document.getElementById('adminFormDiv');
  formDiv.style.display = formDiv.style.display === 'none' ? 'block' : 'none';
  document.getElementById('inlineAddAdminForm').reset();
}

document.getElementById('inlineAddAdminForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const email = document.getElementById('inlineAdminEmail').value;
  postAction("add_admin", { email });
});
function previewDataset(filePath, fileFormat) {
  const previewElement = document.getElementById("previewContent");

  // Simple file fetch & display
  fetch(filePath)
    .then(response => {
      if (!response.ok) throw new Error("File not found");
      if (fileFormat === 'txt' || fileFormat === 'csv' || fileFormat === 'json') {
        return response.text();
      } else {
        // For PDFs/images, embed them directly
        return filePath;
      }
    })
    .then(data => {
      if (fileFormat === 'txt' || fileFormat === 'csv' || fileFormat === 'json') {
        previewElement.innerHTML = `<pre style="max-height: 500px; overflow-y: auto;">${escapeHtml(data)}</pre>`;
      } else if (fileFormat === 'pdf') {
        previewElement.innerHTML = `<iframe src="${filePath}" width="100%" height="500px"></iframe>`;
      } else if (fileFormat === 'png' || fileFormat === 'jpg' || fileFormat === 'jpeg') {
        previewElement.innerHTML = `<img src="${filePath}" class="img-fluid" alt="Image Preview">`;
      } else {
        previewElement.innerHTML = `<a href="${filePath}" target="_blank">Open file in new tab</a>`;
      }

      new bootstrap.Modal(document.getElementById('previewModal')).show();
    })
    .catch(err => {
      previewElement.innerHTML = `<div class="text-danger">Error loading file: ${err.message}</div>`;
      new bootstrap.Modal(document.getElementById('previewModal')).show();
    });
}

// Helper to prevent HTML injection
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
