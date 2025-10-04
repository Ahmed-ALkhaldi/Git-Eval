{{-- resources/views/student/studentDashboard.blade.php --}}
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

    /* Verification Status Styles */
    .verification-status {
      margin: 20px 0;
      padding: 15px;
      border-radius: 10px;
      border-left: 5px solid;
    }
    .verification-status.approved {
      background-color: #d4edda;
      border-color: #28a745;
      color: #155724;
    }
    .verification-status.pending {
      background-color: #fff3cd;
      border-color: #ffc107;
      color: #856404;
    }
    .verification-status.rejected {
      background-color: #f8d7da;
      border-color: #dc3545;
      color: #721c24;
    }
    .resubmit-btn {
      background-color: #dc3545;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      text-decoration: none;
      display: inline-block;
      margin-top: 10px;
      font-weight: bold;
    }
    .resubmit-btn:hover {
      background-color: #c82333;
      color: white;
    }

    /* Add Project Button States */
    .btn[data-verification-status="pending"],
    .btn[data-verification-status="rejected"],
    .btn[data-verification-status="unknown"] {
      opacity: 0.6;
      cursor: not-allowed;
      position: relative;
    }
    
    .btn[data-verification-status="pending"]:hover::after,
    .btn[data-verification-status="rejected"]:hover::after,
    .btn[data-verification-status="unknown"]:hover::after {
      content: "Verification required";
      position: absolute;
      bottom: 100%;
      left: 50%;
      transform: translateX(-50%);
      background: #333;
      color: white;
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 12px;
      white-space: nowrap;
      z-index: 1000;
      margin-bottom: 5px;
    }
    
    .btn[data-verification-status="approved"] {
      opacity: 1;
      cursor: pointer;
    }

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

    .alert { padding: 12px 14px; border-radius: 8px; margin: 12px 0; }
    .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

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
        @if($u->student)
          <p><strong>University :</strong> {{ $u->student->university_name ?? '-' }}</p>
          <p><strong>Student Number :</strong> {{ $u->student->university_num ?? '-' }}</p>
        @endif
        <div class="action-buttons" style="margin-top: 15px;">
          <button class="btn" id="editProfileBtn">✏️ Edit Profile</button>
        </div>
      </div>

      {{-- Verification Status --}}
      @php
        $student = $u?->student;
        $verificationStatus = $student?->verification_status ?? 'unknown';
      @endphp
      
      @if($student)
        <div class="verification-status {{ $verificationStatus }}">
          @if($verificationStatus === 'approved')
            <strong>✅ Verification Status: Approved</strong>
            <p>You have been approved by the supervisor. You can now create projects and join teams.</p>
          @elseif($verificationStatus === 'pending')
            <strong>⏳ Verification Status: Under Review</strong>
            <p>Your request is under review by the supervisor. You cannot create projects until you are approved.</p>
          @elseif($verificationStatus === 'rejected')
            <strong>❌ Verification Status: Rejected</strong>
            <p>Your request has been rejected. Please resubmit a clear and valid enrollment certificate.</p>
            <a href="{{ route('student.resubmit-certificate') }}" class="resubmit-btn">
              📄 Resubmit Enrollment Certificate
            </a>
          @else
            <strong>⚠️ Verification Status: Undefined</strong>
            <p>Please contact the administration to resolve this issue.</p>
          @endif
        </div>
      @endif

      {{-- فلاش رسائل --}}
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="alert alert-error">
          <ul style="margin:0; padding-inline-start:18px;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif
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
          data-verification-status="{{ $student?->verification_status ?? 'unknown' }}"
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

      {{-- يرسل إلى projects.store (المتحكم ينشئ طلب إشراف pending إذا اخترت مشرف) --}}
      <form id="projectForm" method="POST" action="{{ route('projects.store') }}">
        @csrf

        <label for="title">Project Title</label>
        <input type="text" id="title" name="title" value="{{ old('title') }}" required />

        <label for="description">Description</label>
        <textarea id="description" name="description">{{ old('description') }}</textarea>

        <label for="supervisor">Supervisor</label>
        <select id="supervisor" name="supervisor_id">
          <option value="">Select Supervisor</option>
          @foreach($supervisors as $sup)
            @php
              $supName = trim((string)($sup->name ?? ''));
              $supEmail = trim((string)($sup->email ?? ''));
              if ($supName === '' && $supEmail !== '') {
                $supName = strstr($supEmail, '@', true) ?: $supEmail; // fallback to email local-part
              }
              if ($supName === '') { $supName = 'Supervisor #'.$sup->id; }
            @endphp
            <option value="{{ $sup->id }}" @selected(old('supervisor_id')==$sup->id)>
              {{ $supName }} - {{ $supEmail ?: 'no-email' }}
            </option>
          @endforeach
        </select>
        <small>Choosing a supervisor will send a <strong>pending request</strong> to them.</small>

        <label for="team">Team Members</label>
        <select id="team" name="invite_student_ids[]" multiple required>
          @foreach($students as $student)
            @php
              $stName = trim((string)($student->name ?? ''));
              $stUni  = trim((string)($student->university_num ?? ''));
              if ($stName === '' && $stUni !== '') { $stName ; }
              if ($stName === '') { $stName = 'Student '.$student->id; }
            @endphp
            <option value="{{ $student->id }}" @selected(collect(old('invite_student_ids',[]))->contains($student->id))>
              {{ $stName }} - {{ $stUni ?: '—' }}
            </option>
          @endforeach
        </select>
        <small>The owner is added automatically. Choose 1–4 members.</small>

        <label for="repoUrl">GitHub Repository URL</label>
        <input type="url" id="repoUrl" name="github_url" value="{{ old('github_url') }}" required />

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
              $supName = data_get($proj, 'supervisor.user.name')
                ?? trim((data_get($proj, 'supervisor.first_name', '')).' '.(data_get($proj, 'supervisor.last_name', '')));
            @endphp

            <div style="border-bottom:1px solid #ccc; padding:10px 0">
              <h4>Title: {{ data_get($proj, 'title', '(No title)') }}</h4>
              <p>Description: {{ data_get($proj, 'description', '—') }}</p>
              <p><strong>Supervisor: </strong>{{ $supName ?: '-' }}</p>
              <p><strong>Project Status: </strong>{{ data_get($proj, 'status', 'Pending') }}</p>

              <div class="action-buttons" style="display:flex; gap:8px; align-items:center">
                <a class="btn" href="{{ route('projects.report', $proj->id) }}">View Report</a>

                @php
                  $isOwner = optional(auth()->user()->student)->id === $proj->owner_student_id;
                @endphp

                @if($isOwner)
                  <form method="POST" action="{{ route('projects.destroy', $proj->id) }}" onsubmit="return confirm('Are you sure?')" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn danger">Delete</button>
                  </form>
                @endif
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
              $proj = $inv->project; // قد تكون null لو المشروع محذوف
              $ownerName = data_get($proj, 'owner.user.name')
                  ?? trim((data_get($proj, 'owner.first_name', '')).' '.(data_get($proj, 'owner.last_name', '')));
              $supName = data_get($proj, 'supervisor.user.name')
                  ?? trim((data_get($proj, 'supervisor.first_name', '')).' '.(data_get($proj, 'supervisor.last_name', '')));
              $projId = data_get($proj, 'id');
            @endphp

            <div style="border-bottom:1px solid #ccc; padding:10px 0; display:flex; justify-content:space-between; gap:12px">
              <div>
                <h4 style="margin:0 0 6px">{{ data_get($proj, 'title', '(Project missing/deleted)') }}</h4>
                <div style="font-size:14px; color:#555">
                  <div><strong>Owner:</strong> {{ $ownerName ?: '-' }}</div>
                  <div><strong>Supervisor:</strong> {{ $supName ?: '-' }}</div>
                  <div><strong>Invited at:</strong> {{ $inv->created_at?->format('Y-m-d H:i') }}</div>
                  <div><strong>Invited by:</strong> {{ data_get($inv, 'invitedBy.name') ?? data_get($inv, 'invitedBy.email') ?? '-' }}</div>
                </div>
              </div>

              <div style="display:flex; align-items:center; gap:8px">
                <form method="POST" action="{{ route('invitations.accept', $inv->id) }}">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="btn btn-success" {{ $proj ? '' : 'disabled' }}>Accept</button>
                </form>

                <form method="POST" action="{{ route('invitations.decline', $inv->id) }}">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="btn danger">Decline</button>
                </form>

                @if($projId)
                  <button class="btn" data-open-details data-project-id="{{ $projId }}">Details</button>
                @else
                  <span style="color:#b00; font-size:13px">No details (project missing)</span>
                @endif
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

  <!-- Edit Profile Popup -->
  <div class="popup" id="editProfilePopup">
    <div class="popup-content">
      <span class="close-btn">&times;</span>
      <h3>Edit Profile</h3>
      
      <form id="editProfileForm" method="POST" action="{{ route('student.profile.update') }}">
        @csrf
        @method('PUT')
        
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ auth()->user()->email }}" required />
        
        <label for="password">New Password (leave blank to keep current)</label>
        <input type="password" id="password" name="password" />
        
        <label for="password_confirmation">Confirm New Password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" />
        
        <label for="university_name">University Name</label>
        <input type="text" id="university_name" name="university_name" value="{{ auth()->user()->student->university_name ?? '' }}" />
        
        <label for="university_num">Student Number</label>
        <input type="text" id="university_num" name="university_num" value="{{ auth()->user()->student->university_num ?? '' }}" />
        
        <div class="action-buttons" style="margin-top: 20px;">
          <button type="submit" class="btn">💾 Save Changes</button>
          <button type="button" class="btn danger" onclick="closeEditProfileModal()">❌ Cancel</button>
        </div>
      </form>
    </div>
  </div>


  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const openBtn = document.getElementById('openPopup');
    const viewBtn = document.getElementById('viewProjectBtn');
    const joiningBtn = document.getElementById('joiningRequestsBtn');
    const editProfileBtn = document.getElementById('editProfileBtn');

    const addPopup = document.getElementById('popupBox');
    const viewPopup = document.getElementById('viewProjectPopup');
    const joiningPopup = document.getElementById('joiningRequestsPopup');
    const detailsPopup = document.getElementById('detailsPopup');
    const editProfilePopup = document.getElementById('editProfilePopup');

    document.querySelectorAll('.close-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.target.closest('.popup')?.classList.remove('open');
      });
    });

    document.addEventListener('click', (e) => {
      const p = e.target;
      if (p.classList && p.classList.contains('popup')) {
        p.classList.remove('open');
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        document.querySelectorAll('.popup.open').forEach(p => p.classList.remove('open'));
      }
    });

    if (openBtn) {
      openBtn.addEventListener('click', () => {
        const hasProject = openBtn.getAttribute('data-has-project') === '1';
        const verificationStatus = openBtn.getAttribute('data-verification-status');
        
        // Check if student is verified first
        if (verificationStatus !== 'approved') {
          let message = '';
          switch(verificationStatus) {
            case 'pending':
              message = '⏳ You cannot create a project until you are approved by the supervisor.';
              break;
            case 'rejected':
              message = '❌ Your request was rejected. Please resubmit your enrollment certificate from the dashboard.';
              break;
            default:
              message = '⚠️ Invalid verification status. Please contact administration.';
          }
          alert(message);
          return;
        }
        
        // Check if already has project
        if (hasProject) {
          alert('❌ You cannot add more than one project.');
          return;
        }
        
        addPopup.classList.add('open');
      });
    }

    if (viewBtn) {
      viewBtn.addEventListener('click', () => {
        viewPopup.classList.add('open');
      });
    }

    if (joiningBtn) {
      joiningBtn.addEventListener('click', () => {
        joiningPopup.classList.add('open');
      });
    }

    // تفاصيل مشروع
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

          detailsPopup?.classList.add('open');
        } catch (e) {
          alert('Error loading details.');
          console.error(e);
        }
      });
    });

    // Edit Profile Button
    if (editProfileBtn) {
      editProfileBtn.addEventListener('click', () => {
        editProfilePopup.classList.add('open');
      });
    }

    // Form validation for edit profile
    const editProfileForm = document.getElementById('editProfileForm');
    if (editProfileForm) {
      editProfileForm.addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const passwordConfirmation = document.getElementById('password_confirmation').value;
        
        if (password && password !== passwordConfirmation) {
          e.preventDefault();
          alert('Passwords do not match!');
          return false;
        }
      });
    }

  });

  function closeEditProfileModal() {
    document.getElementById('editProfilePopup').classList.remove('open');
  }
  </script>
</body>
</html>
