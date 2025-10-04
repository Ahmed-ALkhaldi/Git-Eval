<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pending Requests</title>
  <link rel="stylesheet" href="{{ asset('css/supervisor.css') }}" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    /* Popup */
    .popup { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.6); z-index: 1000; justify-content: center; align-items: center; }
    .popup.open { display: flex; }
    .popup-content { background: #fff; width: 560px; max-width: 92vw; max-height: 80vh; overflow: auto; border-radius: 10px; padding: 20px; position: relative; }
    .popup-content h3 { margin: 0 0 10px; }
    .close-btn { position: absolute; right: 12px; top: 8px; font-size: 20px; font-weight: bold; cursor: pointer; color: #c00; }
    .muted { color: #6b7280; }

    /* Table & Buttons */
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { border-bottom: 1px solid #e5e7eb; padding: 10px 8px; text-align: left; }
    .actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .btn { display: inline-flex; align-items: center; gap: 6px; border: 0; border-radius: 8px; padding: 8px 12px; cursor: pointer; text-decoration: none; }
    .btn.info { background: #0ea5e9; color: #fff; }
    .btn.success { background: #16a34a; color: #fff; }
    .btn.danger { background: #dc2626; color: #fff; }
    .btn:disabled { opacity: .6; cursor: not-allowed; }
    ul#detailsTeam { padding-left: 18px; }
    ul#detailsTeam li { margin: 4px 0; }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="brand">
      <i class="fa-solid fa-gauge-high"></i>
      <span class="title">Supervisor Dashboard</span>
    </div>
    <div class="nav-actions">
      <a class="link" href="{{ route('supervisor.dashboard') }}">
        <i class="fa-solid fa-chart-line"></i> Dashboard
      </a>
      <a class="link" href="{{ route('supervisor.projects.accepted') }}">
        <i class="fa-solid fa-circle-check"></i> Accepted Projects
      </a>
      <a class="link" href="{{ route('supervisor.students.verify.index') }}">
        <i class="fa-solid fa-user-check"></i> Student Verification
      </a>
      <a class="link" href="{{ route('supervisor.profile.edit') }}">
        <i class="fa-solid fa-user-edit"></i> Edit Profile
      </a>
      <form method="POST" action="{{ route('logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="btn danger" title="Logout">
          <i class="fa-solid fa-sign-out-alt"></i> Logout
        </button>
      </form>
    </div>
  </nav>

  <main class="container">
    <section class="page">
      <div class="header">
        <div class="title">
          <i class="fa-solid fa-hourglass-half"></i><h1>Pending Requests</h1>
        </div>
        <span class="subtitle">Review and approve project requests</span>
      </div>

      {{-- Flash messages --}}
      @if (session('success'))
        <div class="alert success">{{ session('success') }}</div>
      @endif
      @if (session('error'))
        <div class="alert error">{{ session('error') }}</div>
      @endif
      @if ($errors->any())
        <div class="alert error">{{ $errors->first() }}</div>
      @endif

      @if(($requests ?? collect())->count() === 0)
        <p class="muted">No pending requests.</p>
      @else
        <table class="table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Project Title</th>
              <th>Submitted At</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          @foreach($requests as $req)
            @php
              $student = $req->student;

              // اسم الطالب من users أولاً ثم من students
              $u = optional($student)->user;
              $studentName =
                  ($u->name
                  ?? trim(($u->first_name ?? '').' '.($u->last_name ?? ''))
                  ?? trim(($student->first_name ?? '').' '.($student->last_name ?? '')))
                  ?: '—';

              // مشروع الطالب المالك
              $project = optional($student)->ownedProject;
              $projectTitle = optional($project)->title ?? '—';
              $projectId = optional($project)->id;

              $submittedAt = optional($req->created_at)->format('Y-m-d H:i') ?? '—';
            @endphp

            <tr>
              <td>{{ $studentName }}</td>
              <td>{{ $projectTitle }}</td>
              <td>{{ $submittedAt }}</td>
              <td class="actions">
                {{-- View Info (Popup) --}}
                @if($projectId)
                  <button class="btn info" type="button" data-open-details data-project-id="{{ $projectId }}">
                    <i class="fa-solid fa-info-circle"></i> View Info
                  </button>
                @else
                  <button class="btn info" type="button" disabled>
                    <i class="fa-solid fa-info-circle"></i> View Info
                  </button>
                @endif

                {{-- Accept --}}
                <form method="POST" action="{{ route('supervisor.requests.respond', ['id' => $req->id, 'action' => 'accept']) }}" style="display:inline-block;">
                  @csrf
                  @method('PATCH')
                  <button class="btn success" type="submit">
                    <i class="fa-solid fa-check"></i> Accept
                  </button>
                </form>

                {{-- Reject --}}
                <form method="POST" action="{{ route('supervisor.requests.respond', ['id' => $req->id, 'action' => 'reject']) }}" style="display:inline-block;">
                  @csrf
                  @method('PATCH')
                  <button class="btn danger" type="submit">
                    <i class="fa-solid fa-xmark"></i> Reject
                  </button>
                </form>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>

        {{-- Pagination (لو رجّعت paginate() من الكنترولر) --}}
        @if(method_exists($requests, 'links'))
          <div class="pagination">
            {{ $requests->links() }}
          </div>
        @endif
      @endif

      <!-- Navigation Actions -->
      <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="{{ route('supervisor.dashboard') }}" class="btn">
          <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
        <a href="{{ route('supervisor.projects.accepted') }}" class="btn">
          <i class="fa-solid fa-circle-check"></i> View Accepted Projects
        </a>
      </div>
    </section>
  </main>

  {{-- Project Details Popup --}}
  <div class="popup" id="detailsPopup" aria-hidden="true">
    <div class="popup-content" role="dialog" aria-modal="true" aria-labelledby="detailsTitle">
      <span class="close-btn" aria-label="Close">&times;</span>
      <h3 id="detailsTitle"></h3>
      <p id="detailsDescription" class="muted" style="white-space: pre-line;"></p>
      <p><strong>Repository:</strong> <a id="detailsRepo" href="#" target="_blank" rel="noopener">—</a></p>
      <p><strong>Team Members:</strong></p>
      <ul id="detailsTeam"></ul>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const detailsPopup = document.getElementById('detailsPopup');
    const detailsTitle = document.getElementById('detailsTitle');
    const detailsDescription = document.getElementById('detailsDescription');
    const detailsTeam = document.getElementById('detailsTeam');
    const detailsRepo = document.getElementById('detailsRepo');

    // فتح الـ Popup عبر الأزرار
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

          detailsTitle.textContent = data.title || '—';
          detailsDescription.textContent = data.description || '—';

          // Repository link
          const repoUrl = data.repository || '';
          detailsRepo.textContent = repoUrl || '—';
          if (repoUrl) {
            detailsRepo.href = repoUrl;
            detailsRepo.style.pointerEvents = 'auto';
          } else {
            detailsRepo.removeAttribute('href');
            detailsRepo.style.pointerEvents = 'none';
          }

          // Team
          detailsTeam.innerHTML = '';
          (data.team || []).forEach(member => {
            const li = document.createElement('li');
            li.textContent = member.name || '—';
            detailsTeam.appendChild(li);
          });

          detailsPopup.classList.add('open');
          detailsPopup.setAttribute('aria-hidden', 'false');
        } catch (e) {
          alert('Error loading details.');
          console.error(e);
        }
      });
    });

    // إغلاق الـ Popup
    detailsPopup.addEventListener('click', (e) => {
      if (e.target === detailsPopup) {
        detailsPopup.classList.remove('open');
        detailsPopup.setAttribute('aria-hidden', 'true');
      }
    });
    detailsPopup.querySelector('.close-btn').addEventListener('click', () => {
      detailsPopup.classList.remove('open');
      detailsPopup.setAttribute('aria-hidden', 'true');
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        detailsPopup.classList.remove('open');
        detailsPopup.setAttribute('aria-hidden', 'true');
      }
    });
  });
  </script>
</body>
</html>
