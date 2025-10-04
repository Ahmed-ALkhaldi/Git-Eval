<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  {{-- CSRF for AJAX --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Poppins:wght@400;500;600;700&display=swap">

  <style>
    :root {
      --primary: #2196f3;
      --primary-700: #1976d2;
      --accent: #00e5ff;
      --text: #0d1b2a;
      --muted: #5c6b73;
      --danger: #f44336;
      --warning: #ff9800;
      --success: #4caf50;
      --card-bg: rgba(255, 255, 255, 0.25);
      --radius: 16px;
      --shadow: 0 6px 24px rgba(31, 38, 135, 0.2);
    }

    body {
      font-family: 'Cairo', 'Poppins', sans-serif;
      background: linear-gradient(-45deg, #e3f2fd, #bbdefb, #90caf9, #e1f5fe);
      background-size: 400% 400%;
      animation: gradientBG 15s ease infinite;
      text-align: center;
      padding: 20px;
      margin: 0;
      min-height: 100vh;
    }

    @keyframes gradientBG {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    h2 { 
      margin-bottom: 20px; 
      color: var(--text);
      text-align: center;
      font-weight: 700;
    }

    /* Navigation Bar - Matching Supervisor Colors */
    .navbar {
      background: rgba(255, 255, 255, 0.25);
      backdrop-filter: blur(20px) saturate(180%);
      border: 1px solid rgba(255, 255, 255, 0.4);
      padding: 15px 30px;
      margin-bottom: 30px;
      border-radius: 18px;
      box-shadow: 0 6px 24px rgba(31, 38, 135, 0.2);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .navbar-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      color: #0d1b2a;
      font-size: 1.2rem;
      font-weight: 700;
    }

    .navbar-brand i {
      font-size: 22px;
      padding: 8px;
      border-radius: 12px;
      background: linear-gradient(135deg, #2196f3, #00e5ff);
      color: white;
    }

    .navbar-actions {
      display: flex;
      gap: 15px;
      align-items: center;
    }

    .btn-home {
      background: rgba(255, 255, 255, 0.55);
      color: #1976d2;
      padding: 8px 16px;
      border-radius: 10px;
      text-decoration: none;
      font-size: 14px;
      font-weight: 600;
      transition: all 0.2s ease;
      border: 1px solid rgba(33, 150, 243, 0.25);
    }

    .btn-home:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(33, 150, 243, 0.15);
      background: rgba(255, 255, 255, 0.7);
    }

    .btn-logout {
      background: linear-gradient(90deg, #f44336, #ff7b6e);
      color: white;
      padding: 8px 16px;
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 0 6px 14px rgba(244, 67, 54, 0.25);
    }

    .btn-logout:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(244, 67, 54, 0.25);
    }

    /* الحاوية الرئيسية */
    .dashboard-container {
      display: flex;
      justify-content: center;
      gap: 20px;
      flex-wrap: wrap;
    }

    .section {
      background: var(--card-bg);
      backdrop-filter: blur(22px) saturate(180%);
      border: 1px solid rgba(255, 255, 255, 0.45);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 18px;
      width: 45%;
      display: flex;
      flex-direction: column;
      min-height: 350px;
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    
    .section:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 30px rgba(31, 38, 135, 0.18);
    }

    .section h3 {
      margin-top: 0;
      margin-bottom: 15px;
      color: var(--text);
      text-align: center;
      font-weight: 700;
    }

    /* الأزرار - Matching Supervisor Style */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      border-radius: 12px;
      border: none;
      cursor: pointer;
      font-weight: 700;
      font-size: 13px;
      transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.2s;
      color: white;
      background: linear-gradient(90deg, var(--primary), var(--accent));
      box-shadow: 0 6px 14px rgba(33, 150, 243, 0.25);
    }
    .btn:hover { 
      transform: translateY(-2px); 
      box-shadow: 0 10px 20px rgba(33, 150, 243, 0.25); 
    }
    .btn:active { transform: translateY(0); }
    .btn i { font-size: 14px; }
    
    .btn-success { background: linear-gradient(90deg, var(--success), #6fdc8c); }
    .btn-danger { background: linear-gradient(90deg, var(--danger), #ff7b6e); }
    .btn-dark { background: linear-gradient(90deg, var(--text), #5c6b73); }
    .btn-primary { background: linear-gradient(90deg, var(--primary), var(--accent)); }

    .button-group {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-bottom: 15px;
    }

    /* الجدول - Matching Supervisor Style */
    table {
      width: 100%;
      border-collapse: collapse;
      border-spacing: 0;
      overflow: hidden;
      border-radius: 14px;
      box-shadow: 0 8px 24px rgba(31, 38, 135, 0.12);
      margin-top: auto;
      flex-grow: 1;
      display: none;
    }
    
    table thead th {
      font-weight: 700;
      font-size: 14px;
      text-align: left;
      padding: 12px;
      background: rgba(33, 150, 243, 0.1);
      border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    }
    
    table tbody td {
      padding: 12px;
      font-size: 14px;
      border-bottom: 1px dashed rgba(0, 0, 0, 0.06);
      background: rgba(255, 255, 255, 0.7);
    }
    
    table tbody tr:hover td {
      background: rgba(255, 255, 255, 0.9);
    }

    tbody:empty::after {
      content: "No data available";
      display: block;
      padding: 40px;
      color: var(--muted);
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
      
      .navbar {
        flex-direction: column;
        gap: 15px;
        padding: 20px;
      }
      
      .navbar-brand {
        font-size: 1.1rem;
      }
      
      .navbar-actions {
        flex-wrap: wrap;
        justify-content: center;
      }
    }
  </style>
</head>
<body>

  <!-- Navigation Bar -->
  <div class="navbar">
    <div class="navbar-brand">
      <i class="fa-solid fa-shield-halved"></i>
      <span>Admin Dashboard</span>
    </div>
    <div class="navbar-actions">
      <a href="{{ route('welcome') }}" class="btn btn-home">
        <i class="fa-solid fa-home"></i> Home
      </a>
      <form method="POST" action="{{ route('logout') }}" style="display: inline;">
        @csrf
        <button type="submit" class="btn btn-logout">
          <i class="fa-solid fa-sign-out-alt"></i> Logout
        </button>
      </form>
    </div>
  </div>

  <h2>Manage Students and Supervisors</h2>

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
