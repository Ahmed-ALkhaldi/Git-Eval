<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GitEval — Student Dashboard</title>

  {{-- CSS from public/css --}}
  <link rel="stylesheet" href="{{ asset('css/app.css') }}" />

  <style>
    .appbar { background-color: var(--primary-700); border-bottom: 1px solid var(--border); width: 100%; }
    header > .container { padding: 18px; }
    .brand { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .logo { width: 50px; display: grid; place-items: center; border-radius: 10px; font-weight: 900; }
    .logo img { width: inherit; }
    .brand h1 { color: var(--light); margin: 0; font-size: 20px; letter-spacing: 0.3px; font-weight: 700; }
    .actions-inline { margin-inline-start: auto; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .logout { border: 1px solid var(--border); background: var(--danger); color: var(--light); padding: 15px; border-radius: 10px; cursor: pointer; transition: 0.15s ease; font-weight: 700; }
    .logout:hover { box-shadow: var(--focus); }

    body > .container { background: #b9e9ff; border-radius: 10px; box-shadow: 0 0 15px rgba(0, 0, 0, 0.2); padding: 30px; margin-top: 20px; }
    header > h1 { color: white; border-radius: 10px; padding: 20px; background-color: var(--primary); }
    .user-info { padding: 15px; }
    .user-info p { margin-bottom: 5px; }
    h2 { color: var(--text); padding-bottom: 10px; border-bottom: 2px solid var(--primary); }
    .buttons-container { display: flex; gap: 20px; margin-bottom: 30px; }
    .buttons-container .btn { background-color: var(--primary-700); color: white; text-align: center; padding: 15px; border-radius: 10px; text-decoration: none; font-weight: bold; }
    .buttons-container .btn:hover { color: var(--light); background-color: var(--primary-700); opacity: 0.5; transform: translateY(-2px); }
    #viewProjectBtn { background-color: var(--success); }
    #joiningRequestsBtn { border: 1px solid var(--text); color: var(--text); background-color: var(--light); }
    .divider { height: 1px; background: #ddd; margin: 30px 0; }

    .popup { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); justify-content: center; align-items: center; z-index: 1000; }
    .popup.open { display: flex; }
    .popup-content { background: white; padding: 25px; border-radius: 8px; width: 500px; text-align: left; max-height: 80vh; overflow-y: auto; position: relative; }
    .popup-content h3 { margin-top: 0; }
    .popup-content label { font-weight: bold; display: block; margin-top: 12px; }
    .popup-content input, .popup-content textarea, .popup-content select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; }
    textarea { resize: vertical; min-height: 80px; }
    .close-btn { position: absolute; right: 15px; top: 10px; font-size: 20px; font-weight: bold; cursor: pointer; color: red; }

    .joining-btn { padding: 5px 10px; margin-left: 5px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; }
    .acceptBtn { background-color: var(--success); color: white; }
    .rejectBtn { background-color: var(--danger); color: white; }
    .detailsBtn { background-color: var(--primary); color: white; }
    .acceptBtn:hover, .rejectBtn:hover, .detailsBtn:hover { opacity: 0.8; }

    ul li { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }

    .action-buttons { margin-top: 10px; }
    .action-buttons .btn { background: var(--primary-700); color: #fff; border: none; padding: 8px 12px; border-radius: 6px; margin-right: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items:center; }
    .action-buttons .btn:hover { opacity: 0.7; }
    .action-buttons .danger { background: #dc3545; }

    @media (max-width: 768px) {
      .buttons-container { display: grid; grid-template-columns: 1fr; margin: 0px; }
      .popup-content { width: 92vw; }
    }
  </style>
</head>
<body>

  <!-- App Bar -->
  <header class="appbar">
    <div class="container">
      <div class="brand">
        <div class="logo" aria-hidden="true">
          <img src="{{ asset('css/assets/logo-final.png') }}" alt="GitEval Logo" />
        </div>
        <h1>GitEval</h1>
        <div class="actions-inline">
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="logout" type="submit" title="Logout">Logout</button>
          </form>
        </div>
      </div>
    </div>
  </header>

  <div class="container">
    <header>
      @php
        $u = auth()->user();
        $displayName = $u?->first_name ? ($u->first_name.' '.$u->last_name) : ($u->name ?? 'student');
      @endphp
      <h1>Welcome, {{ $displayName }}</h1>
      <div class="user-info">
        <p><strong>Email :</strong> {{ $u?->email ?? '-' }}</p>
        <p><strong>Role  :</strong> {{ $u?->role ?? 'student' }}</p>
      </div>
    </header>

    <div class="divider"></div>

    <section>
      <h2>Your Project Info</h2>
      <p>Here you can view and manage your graduation project, commits, and evaluations.</p>

      <div class="buttons-container">
        <a href="#" class="btn" id="viewProjectBtn">View My Project</a>
        <a href="#" class="btn" id="joiningRequestsBtn">Joining Requests</a>
        <button
          class="btn"
          id="openPopup"
          type="button"
          data-has-project="{{ ($projects ?? collect())->count() >= 1 ? '1':'0' }}"
        >
          Add New Project
        </button>
      </div>
    </section>
  </div>

  <!-- Add Project Popup -->
  <div class="popup" id="popupBox">
    <div class="popup-content">
      <span class="close-btn">&times;</span>
      <h3>Add Graduation Project</h3>

      <form id="projectForm" method="POST" action="{{ route('projects.store') }}">
        @csrf

        <label for="title">Project Title</label>
        <input type="text" id="title" name="title" required />

        <label for="description">Description</label>
        <textarea id="description" name="description"></textarea>

        <!-- عرض فقط مؤقتًا (الحفظ يعتمد على سير العمل لديك) -->
        <label for="supervisor">Supervisor</label>
        <select id="supervisor" name="supervisor_id">
          <option value="">Select Supervisor</option>
          @foreach($supervisors as $sup)
            <option value="{{ $sup->id }}">
              {{ $sup->name }} - {{ $sup->email }}
            </option>
          @endforeach
        </select>

        <label for="team">Team Members</label>
        <select id="team" name="invite_student_ids[]" multiple required>
          @foreach($students as $student)
            <option value="{{ $student->id }}">
              {{ $student->name }} - {{ $student->university_num }}
            </option>
          @endforeach
        </select>
        <small>اختر من 1 إلى 4 طلاب (المالك يُضاف تلقائيًا).</small>

        <label for="repoUrl">GitHub Repository URL</label>
        <input type="url" id="repoUrl" name="github_url" required />

        <button type="submit" class="btn btn-success" style="margin-top: 15px">Save Project</button>
      </form>
    </div>
  </div>

  <!-- View Project Popup -->
  <div class="popup" id="viewProjectPopup">
    <div class="popup-content">
      <span class="close-btn">&times;</span>
      <h3>My Projects</h3>

      <div id="projectList">
        @isset($projects)
          @forelse($projects as $proj)
            @php
              $supName = $proj->supervisor->user->name
                ?? trim(($proj->supervisor->first_name ?? '').' '.($proj->supervisor->last_name ?? ''));
            @endphp

            <div style="border-bottom:1px solid #ccc; padding:10px 0">
              <h4>Title: {{ $proj->title }}</h4>
              <p>Description: {{ $proj->description }}</p>
              <p><strong>Supervisor: </strong>{{ $supName ?: '-' }}</p>
              <p><strong>Status: </strong>{{ $proj->status ?? 'Pending' }}</p>

              <div class="action-buttons" style="display:flex; gap:8px; align-items:center">
                <a class="btn" href="{{ route('projects.report', $proj->id) }}">View Report</a>

                <form method="POST" action="{{ route('projects.destroy', $proj->id) }}" onsubmit="return confirm('Are you sure?')" style="display:inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn danger">Delete</button>
                </form>
              </div>
            </div>
          @empty
            <p>No projects yet.</p>
          @endforelse
        @else
          <p>No data available now.</p>
        @endisset
      </div>
    </div>
  </div>

  <!-- Joining Requests Popup -->
  <div class="popup" id="joiningRequestsPopup">
    <div class="popup-content">
      <span class="close-btn">&times;</span>
      <h3>Joining Requests</h3>
      <div id="joiningList">
        @isset($joiningRequests)
          @forelse($joiningRequests as $inv)
            @php
              $proj = $inv->project;
              $ownerName = optional($proj->owner->user)->name
                ?? trim(($proj->owner->first_name ?? '').' '.($proj->owner->last_name ?? ''));
              $supName = optional($proj->supervisor->user)->name
                ?? trim(($proj->supervisor->first_name ?? '').' '.($proj->supervisor->last_name ?? ''));
            @endphp

            <div style="border-bottom:1px solid #ccc; padding:10px 0; display:flex; justify-content:space-between; gap:12px">
              <div>
                <h4 style="margin:0 0 6px">{{ $proj->title }}</h4>
                <div style="font-size:14px; color:#555">
                  <div><strong>Owner:</strong> {{ $ownerName ?: '-' }}</div>
                  <div><strong>Supervisor:</strong> {{ $supName ?: '-' }}</div>
                  <div><strong>Invited at:</strong> {{ $inv->created_at?->format('Y-m-d H:i') }}</div>
                  <div><strong>Invited by:</strong> {{ $inv->invitedBy->name ?? $inv->invitedBy->email ?? '-' }}</div>
                </div>
              </div>

              <div style="display:flex; align-items:center; gap:8px">
                <form method="POST" action="{{ route('invitations.accept', $inv->id) }}">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="btn btn-success">Accept</button>
                </form>

                <form method="POST" action="{{ route('invitations.decline', $inv->id) }}">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="btn danger">Decline</button>
                </form>

                <button class="btn" data-open-details data-project-id="{{ $proj->id }}">Details</button>
              </div>
            </div>
          @empty
            <p>No joining requests.</p>
          @endforelse
        @else
          <p>No data available now.</p>
        @endisset
      </div>
    </div>
  </div>

  <!-- Details Popup -->
  <div class="popup" id="detailsPopup">
    <div class="popup-content">
      <span class="close-btn">&times;</span>
      <h3 id="detailsTitle"></h3>
      <p id="detailsDescription"></p>
      <p><strong>Supervisor:</strong> <span id="detailsSupervisor"></span></p>
      <p><strong>Team Members:</strong></p>
      <ul id="detailsTeam"></ul>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const openBtn = document.getElementById('openPopup');
    const viewBtn = document.getElementById('viewProjectBtn');
    const joiningBtn = document.getElementById('joiningRequestsBtn');

    const addPopup = document.getElementById('popupBox');
    const viewPopup = document.getElementById('viewProjectPopup');
    const joiningPopup = document.getElementById('joiningRequestsPopup');
    const detailsPopup = document.getElementById('detailsPopup');

    // إغلاق عبر زر X
    document.querySelectorAll('.close-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.target.closest('.popup')?.classList.remove('open');
      });
    });

    // إغلاق عند الضغط خارج المحتوى
    document.addEventListener('click', (e) => {
      const p = e.target;
      if (p.classList && p.classList.contains('popup')) {
        p.classList.remove('open');
      }
    });

    // فتح نافذة إضافة مشروع (مع تحقق بسيط: مشروع واحد فقط)
    if (openBtn) {
      openBtn.addEventListener('click', () => {
        const hasProject = openBtn.getAttribute('data-has-project') === '1';
        if (hasProject) {
          alert('❌ You cannot add more than one project.');
          return;
        }
        addPopup.classList.add('open');
      });
    }

    // فتح نافذة عرض مشاريعي
    if (viewBtn) {
      viewBtn.addEventListener('click', () => {
        viewPopup.classList.add('open');
      });
    }

    // فتح نافذة طلبات الانضمام
    if (joiningBtn) {
      joiningBtn.addEventListener('click', () => {
        joiningPopup.classList.add('open');
      });
    }

    // ========= جزء تفاصيل المشروع (كما طلبت إضافته) =========
    // فتح تفاصيل مشروع عبر الأزرار
    document.querySelectorAll('[data-open-details]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const projectId = btn.getAttribute('data-project-id');
        if (!projectId) return;

        try {
          const res = await fetch(`{{ url('/projects') }}/${projectId}/details`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });
          if (!res.ok) throw new Error('Failed to load project details');
          const data = await res.json();

          // تعبئة الـPopup
          document.getElementById('detailsTitle').textContent = data.title || '—';
          document.getElementById('detailsDescription').textContent = data.description || '—';
          document.getElementById('detailsSupervisor').textContent = data.supervisor || '—';

          const ul = document.getElementById('detailsTeam');
          ul.innerHTML = '';
          (data.team || []).forEach(member => {
            const li = document.createElement('li');
            li.textContent = member.name || '—';
            ul.appendChild(li);
          });

          // فتح الـPopup
          detailsPopup?.classList.add('open');
        } catch (e) {
          alert('Error loading details.');
          console.error(e);
        }
      });
    });

    // إغلاق الـpopups عبر أزرار X (تفاصيل/الطلبات)
    document.querySelectorAll('#detailsPopup .close-btn, #joiningRequestsPopup .close-btn').forEach(btn => {
      btn.addEventListener('click', () => btn.closest('.popup')?.classList.remove('open'));
    });
  });
  </script>
</body>
</html>
