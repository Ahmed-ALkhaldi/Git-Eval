<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  {{-- CSRF for AJAX --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <style>
    :root {
      --success: #28a745;
      --danger: #dc3545;
      --text: #343a40;
      --primary: #007bff;
    }

    body {
      font-family: Arial, sans-serif;
      background: #d8f0ff;
      text-align: center;
      padding: 20px;
      margin: 0;
    }

    h2 { margin-bottom: 10px; }

    /* بطاقة اللوج اوت */
    .logout-card {
      background: white;
      padding: 15px;
      border-radius: 8px;
      width: 200px;
      margin: 0 auto 30px auto;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .logout-card button {
      background: var(--danger);
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
    }
    .logout-card button:hover { opacity: 0.9; }

    /* الحاوية الرئيسية */
    .dashboard-container {
      display: flex;
      justify-content: center;
      gap: 20px;
      flex-wrap: wrap;
    }

    .section {
      background: white;
      padding: 20px;
      border-radius: 8px;
      width: 45%;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      display: flex;
      flex-direction: column;
      min-height: 350px;
    }

    .section h3 {
      margin-top: 0;
      margin-bottom: 15px;
      color: #333;
      text-align: center;
    }

    /* الأزرار */
    .btn {
      color: white;
      padding: 8px 14px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 13px;
      transition: 0.2s;
    }
    .btn-success { background: var(--success); }
    .btn-danger { background: var(--danger); }
    .btn-dark { background: var(--text); }
    .btn-primary { background: var(--primary); }
    .btn:hover { opacity: 0.85; }

    .button-group {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-bottom: 15px;
    }

    /* الجدول */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: auto;
      flex-grow: 1;
      display: none;
    }
    table, th, td { border: 1px solid #ccc; }
    th, td { padding: 10px; text-align: center; }
    th { background: #f5f5f5; }

    tbody:empty::after {
      content: "No data available";
      display: block;
      padding: 40px;
      color: #777;
    }

    /* Popup */
    .popup {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.6);
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .popup-content {
      background: white;
      padding: 25px;
      border-radius: 8px;
      width: 90%;
      max-width: 420px;
    }

    .popup-content h3 { margin-top: 0; text-align: center; }
    .popup-content label { font-weight: bold; display: block; margin-top: 10px; text-align: left; }

    .popup-content input,
    .popup-content select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 14px;
    }

    .close-btn {
      float: right;
      font-size: 20px;
      font-weight: bold;
      cursor: pointer;
      color: var(--danger);
    }

    /* Responsive */
    @media (max-width: 900px) {
      .section { width: 100%; }
    }
  </style>
</head>
<body>

  <h2>Admin Dashboard</h2>
  <p>Manage Students and Supervisors</p>

  <!-- بطاقة اللوج اوت (خروج حقيقي) -->
  <div class="logout-card">
    <form id="logoutForm" action="{{ route('logout') }}" method="POST">
      @csrf
      <button type="submit">Logout</button>
    </form>
  </div>

  <!-- الحاوية الرئيسية -->
  <div class="dashboard-container">
    <!-- Students Section -->
    <div class="section">
      <h3>👩‍🎓 Students</h3>
      <div class="button-group">
        <button class="btn btn-success" onclick="openPopup('student')">Add Student</button>
        <button class="btn btn-dark" onclick="toggleTable('studentsTable')">View Students</button>
      </div>

      <table id="studentsTable">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>University</th>
            <th>Student Number</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="studentsList"></tbody>
      </table>
    </div>

    <!-- Supervisors Section -->
    <div class="section">
      <h3>👨‍🏫 Supervisors</h3>
      <div class="button-group">
        <button class="btn btn-success" onclick="openPopup('supervisor')">Add Supervisor</button>
        <button class="btn btn-dark" onclick="toggleTable('supervisorsTable')">View Supervisors</button>
      </div>

      <table id="supervisorsTable">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="supervisorsList"></tbody>
      </table>
    </div>
  </div>

  <!-- Popup Form -->
  <div class="popup" id="popupBox">
    <div class="popup-content">
      <span class="close-btn" id="closePopup">&times;</span>
      <h3 id="popupTitle"></h3>
      <form id="popupForm">
        <div id="formFields"></div>
        <button type="submit" class="btn btn-primary" style="margin-top:15px;width:100%;">Save</button>
      </form>
    </div>
  </div>

  <script>
    // ========= State =========
    let editRow = null;
    let currentType = "";

    // ========= Elements =========
    const popup       = document.getElementById("popupBox");
    const closeBtn    = document.getElementById("closePopup");
    const popupTitle  = document.getElementById("popupTitle");
    const formFields  = document.getElementById("formFields");
    const popupForm   = document.getElementById("popupForm");

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ========= Helpers =========
    function api(path, method = 'GET', body = null) {
      const opts = {
        method,
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken,
          ...(body ? {'Content-Type': 'application/json'} : {})
        },
        ...(body ? { body: JSON.stringify(body) } : {})
      };
      return fetch(path, opts).then(async res => {
        if (!res.ok) {
          const txt = await res.text();
          throw new Error(txt || (res.status + ' ' + res.statusText));
        }
        const ct = res.headers.get('content-type') || '';
        return ct.includes('application/json') ? res.json() : null;
      });
    }

    function toggleTable(tableId) {
      const table = document.getElementById(tableId);
      const isHidden = (table.style.display === "none" || table.style.display === "");
      table.style.display = isHidden ? "table" : "none";
      if (isHidden) {
        if (tableId === 'studentsTable') loadStudents();
        if (tableId === 'supervisorsTable') loadSupervisors();
      }
    }

    // ========= Loaders =========
    async function loadStudents() {
      const tbody = document.getElementById('studentsList');
      tbody.innerHTML = '';
      const rows = await api('/admin/students', 'GET');
      rows.forEach(r => addRow('studentsList', [r.name, r.email, (r.university_name||''), (r.university_num||'')], 'student', r.id));
    }

    async function loadSupervisors() {
      const tbody = document.getElementById('supervisorsList');
      tbody.innerHTML = '';
      const rows = await api('/admin/supervisors', 'GET');
      rows.forEach(r => addRow('supervisorsList', [r.name, r.email, r.status], 'supervisor', r.id));
    }

    // ========= Popup =========
    function openPopup(type, row = null) {
      currentType = type;
      editRow = row;
      popup.style.display = "flex";
      formFields.innerHTML = "";

      if (type === "student") {
        popupTitle.textContent = editRow ? "Edit Student" : "Add Student";
        formFields.innerHTML = `
          <label for="sName">Name</label>
          <input type="text" id="sName" required value="${editRow ? (row.dataset.name || '') : ''}">
          
          <label for="sEmail">Email</label>
          <input type="email" id="sEmail" required value="${editRow ? (row.dataset.email || '') : ''}">
          
          <label for="sPass">Password</label>
          <input type="password" id="sPass" ${editRow ? '' : 'required'}>
          
          <label for="sUni">University</label>
          <input type="text" id="sUni" value="${editRow ? (row.dataset.university_name||'') : ''}">
          
          <label for="sNum">Student Number</label>
          <input type="text" id="sNum" value="${editRow ? (row.dataset.university_num||'') : ''}">
        `;
      } else {
        popupTitle.textContent = editRow ? "Edit Supervisor" : "Add Supervisor";
        formFields.innerHTML = `
          <label for="supName">Name</label>
          <input type="text" id="supName" required value="${editRow ? (row.dataset.name || '') : ''}">
          
          <label for="supEmail">Email</label>
          <input type="email" id="supEmail" required value="${editRow ? (row.dataset.email || '') : ''}">
          
          <label for="supPass">Password</label>
          <input type="password" id="supPass" ${editRow ? '' : 'required'}>
          
          <label for="supStatus">Status</label>
          <select id="supStatus" required>
            <option value="Active" ${editRow && row.dataset.status === "Active" ? "selected" : ""}>Active</option>
            <option value="Inactive" ${editRow && row.dataset.status === "Inactive" ? "selected" : ""}>Inactive</option>
          </select>
          
          <label for="supUni">University (optional)</label>
          <input type="text" id="supUni" value="${editRow ? (row.dataset.university_name||'') : ''}">
        `;
      }
    }

    closeBtn.onclick = () => { popup.style.display = "none"; };
    window.onclick = (e) => { if (e.target == popup) popup.style.display = "none"; };

    popupForm.onsubmit = async (e) => {
      e.preventDefault();
      try {
        if (currentType === 'student') {
          const payload = {
            name: document.getElementById('sName').value,
            email: document.getElementById('sEmail').value,
            password: document.getElementById('sPass').value || undefined,
            university_name: document.getElementById('sUni').value || undefined,
            university_num: document.getElementById('sNum').value || undefined,
          };

          if (editRow) {
            await api(`/admin/students/${editRow.dataset.id}`, 'PUT', payload);
            await loadStudents();
          } else {
            await api('/admin/students', 'POST', payload);
            await loadStudents();
          }
        } else {
          const payload = {
            name: document.getElementById('supName').value,
            email: document.getElementById('supEmail').value,
            password: document.getElementById('supPass').value || undefined,
            status: document.getElementById('supStatus').value,
            university_name: document.getElementById('supUni').value || undefined,
          };

          if (editRow) {
            await api(`/admin/supervisors/${editRow.dataset.id}`, 'PUT', payload);
            await loadSupervisors();
          } else {
            await api('/admin/supervisors', 'POST', payload);
            await loadSupervisors();
          }
        }

        popup.style.display = "none";
        popupForm.reset();
        editRow = null;
      } catch (err) {
        alert('Error: ' + err.message);
      }
    };

    // ========= Table Row Builder =========
    function addRow(tbodyId, values, type, id) {
      const tbody = document.getElementById(tbodyId);
      const row = tbody.insertRow();

      // cells
      for (let i = 0; i < values.length; i++) {
        const cell = row.insertCell(i);
        cell.innerText = values[i] ?? '';
      }

      // dataset for edit
      row.dataset.id = id;
      if (type === 'student') {
        row.dataset.name = values[0] || '';
        row.dataset.email = values[1] || '';
        row.dataset.university_name = values[2] || '';
        row.dataset.university_num  = values[3] || '';
      } else {
        row.dataset.name = values[0] || '';
        row.dataset.email = values[1] || '';
        row.dataset.status = values[2] || '';
        // optional university for supervisors (not shown in table)
        row.dataset.university_name = values[3] || '';
      }

      const actions = row.insertCell(values.length);
      actions.innerHTML = `
        <button class="btn btn-dark">Edit</button>
        <button class="btn btn-danger">Delete</button>
      `;
      const [editBtn, delBtn] = actions.querySelectorAll('button');
      editBtn.onclick = () => openPopup(type, row);
      delBtn.onclick = async () => {
        if (!confirm('Delete this record?')) return;
        try {
          if (type === 'student') await api(`/admin/students/${id}`, 'DELETE');
          else await api(`/admin/supervisors/${id}`, 'DELETE');
          row.remove();
        } catch (e) { alert('Error: ' + e.message); }
      };
    }
  </script>
</body>
</html>
