<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GitEval — لوحة الطالب</title>

  {{-- أنماطك من public/css --}}
  <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/app.css') }}" />

  <style>
    /* نفس التنسيق الذي أرسلته */
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

    /* Popup */
    .popup { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); justify-content: center; align-items: center; z-index: 1000; }
    .popup-content { background: white; padding: 25px; border-radius: 8px; width: 500px; text-align: left; max-height: 80vh; overflow-y: auto; position: relative; }
    .popup-content h3 { margin-top: 0; }
    .popup-content label { font-weight: bold; display: block; margin-top: 12px; }
    .popup-content input, .popup-content textarea, .popup-content select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; }
    textarea { resize: vertical; min-height: 80px; }
    .close-btn { position: absolute; right: 15px; top: 10px; font-size: 20px; font-weight: bold; cursor: pointer; color: red; }

    /* Buttons Accept/Reject */
    .joining-btn { padding: 5px 10px; margin-left: 5px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; }
    .acceptBtn { background-color: var(--success); color: white; }
    .rejectBtn { background-color: var(--danger); color: white; }
    .detailsBtn { background-color: var(--primary); color: white; }
    .acceptBtn:hover, .rejectBtn:hover, .detailsBtn:hover { opacity: 0.8; }

    ul li { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }

    .action-buttons { margin-top: 10px; }
    .action-buttons button { background: var(--primary-700); color: #fff; border: none; padding: 8px 12px; border-radius: 6px; margin-right: 8px; cursor: pointer; }
    .action-buttons button:hover { opacity: 0.7; }
    .action-buttons .danger { background: #dc3545; }

    @media (max-width: 768px) {
      .buttons-container { display: grid; grid-template-columns: 1fr; margin: 0px; }
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
            <button class="logout" type="submit" title="Logout">تسجيل الخروج</button>
          </form>
        </div>
      </div>
    </div>
  </header>

  <div class="container">
    <header>
      <h1>
        مرحبًا،
        @php
          $u = auth()->user();
          $displayName = $u?->first_name ? ($u->first_name.' '.$u->last_name) : ($u->name ?? 'طالب');
        @endphp
        {{ $displayName }}
      </h1>
      <div class="user-info">
        <p><strong>البريد :</strong> {{ auth()->user()->email ?? '-' }}</p>
        <p><strong>الدور :</strong> {{ auth()->user()->role ?? 'student' }}</p>
      </div>
    </header>

    <div class="divider"></div>

    <section>
      <h2>مشروع التخرج</h2>
      <p>هنا يمكنك إدارة مشروع التخرج، متابعة المساهمات والتقارير.</p>

      <div class="buttons-container">
        {{-- غيّر هذه الروابط للراوتات الحقيقية عندك --}}
        <a href="{{ route('projects.index') }}" class="btn" id="viewProjectBtn">عرض مشروعي</a>
        <a href="{{ route('requests.pending') }}" class="btn" id="joiningRequestsBtn">طلبات الانضمام</a>
        <button class="btn" id="openPopup" type="button">إضافة مشروع جديد</button>
      </div>
    </section>
  </div>

  <!-- نافذة إضافة مشروع جديد -->
  <div class="popup" id="popupBox">
    <div class="popup-content">
      <span class="close-btn">&times;</span>
      <h3>إضافة مشروع تخرج</h3>

      {{-- إن أردت الإرسال للباك-إند، ضع action إلى route('projects.store') واحذف e.preventDefault من السكربت --}}
      <form id="projectForm" method="POST" action="{{ route('projects.store') }}">
        @csrf
        <label for="title">عنوان المشروع</label>
        <input type="text" id="title" name="title" required />

        <label for="description">الوصف</label>
        <textarea id="description" name="description" required></textarea>

        <label for="supervisor">المشرف</label>
        <select id="supervisor" name="supervisor_id" required>
          <option value="">اختر المشرف</option>
          {{-- إذا مرّرت $supervisors من الكونترولر --}}
          @isset($supervisors)
            @foreach($supervisors as $sup)
              <option value="{{ $sup->id }}">{{ $sup->name ?? ($sup->first_name.' '.$sup->last_name) }} - {{ $sup->email }}</option>
            @endforeach
          @endisset
        </select>

        <label for="team">أعضاء الفريق</label>
        <select id="team" name="team_members[]" multiple required>
          {{-- إذا مرّرت $students من الكونترولر --}}
          @isset($students)
            @foreach($students as $student)
              <option value="{{ $student->id }}">
                {{ $student->name ?? ($student->first_name.' '.$student->last_name) }}
                - {{ $student->university_num }}
              </option>
            @endforeach
          @endisset
        </select>

        <label for="repoUrl">رابط مستودع GitHub</label>
        <input type="url" id="repoUrl" name="repository_url" required />

        <button type="submit" class="btn btn-success" style="margin-top: 15px">
          حفظ المشروع
        </button>
      </form>
    </div>
  </div>

  <!-- نافذة عرض مشاريعي (يمكنك لاحقًا تعبئتها من الـ Controller) -->
  <div class="popup" id="viewProjectPopup">
    <div class="popup-content">
      <span class="close-btn">&times;</span>
      <h3>مشاريعي</h3>

      <div id="projectList">
        {{-- إن كنت تمُرّر $projects من الكونترولر --}}
        @isset($projects)
          @forelse($projects as $proj)
            <div style="border-bottom:1px solid #ccc; padding:10px 0">
              <h4>العنوان: {{ $proj->title }}</h4>
              <p>الوصف: {{ $proj->description }}</p>
              <p><strong>المشرف: </strong>{{ $proj->supervisor?->name ?? '-' }}</p>
              <p><strong>الحالة: </strong>{{ $proj->status ?? 'Pending' }}</p>
              <div class="action-buttons">
                <a class="btn" href="{{ route('projects.show', $proj->id) }}">عرض التقرير</a>
                <form method="POST" action="{{ route('projects.destroy', $proj->id) }}" style="display:inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn danger">حذف</button>
                </form>
              </div>
            </div>
          @empty
            <p>لا توجد مشاريع بعد.</p>
          @endforelse
        @else
          <p>لا توجد بيانات لعرضها الآن.</p>
        @endisset
      </div>
    </div>
  </div>

  <!-- نافذة طلبات الانضمام -->
  <div class="popup" id="joiningRequestsPopup">
    <div class="popup-content">
      <span class="close-btn">&times;</span>
      <h3>طلبات الانضمام</h3>
      <div id="joiningList"></div>
    </div>
  </div>

  <!-- نافذة تفاصيل المشروع -->
  <div class="popup" id="detailsPopup">
    <div class="popup-content">
      <span class="close-btn">&times;</span>
      <h3 id="detailsTitle"></h3>
      <p id="detailsDescription"></p>
      <p><strong>المشرف:</strong> <span id="detailsSupervisor"></span></p>
      <p><strong>أعضاء الفريق:</strong></p>
      <ul id="detailsTeam"></ul>
    </div>
  </div>

  {{-- سكربتات الواجهة (تبقى كما هي؛ وإن أردت ربطًا حقيقيًا بالباك-إند احذف preventDefault) --}}
  <script>
    const openBtn = document.getElementById("openPopup");
    const form = document.getElementById("projectForm");
    const viewBtn = document.getElementById("viewProjectBtn");
    const viewPopup = document.getElementById("viewProjectPopup");
    const projectListDiv = document.getElementById("projectList");
    const joiningBtn = document.getElementById("joiningRequestsBtn");
    const joiningPopup = document.getElementById("joiningRequestsPopup");
    const joiningList = document.getElementById("joiningList");
    const detailsPopup = document.getElementById("detailsPopup");

    let projects = [];

    // إغلاق النوافذ
    document.querySelectorAll(".close-btn").forEach(btn => {
      btn.addEventListener("click", e => {
        e.target.closest(".popup").style.display = "none";
      });
    });
    window.addEventListener("click", e => {
      if (e.target.classList.contains("popup")) {
        e.target.style.display = "none";
      }
    });

    // إضافة مشروع (للديمو فقط). إذا تريد الإرسال للباك-إند، احذف preventDefault.
    openBtn.onclick = () => {
      if (projects.length >= 1) {
        alert("❌ لا يمكنك إضافة أكثر من مشروع.");
        return;
      }
      document.getElementById("popupBox").style.display = "flex";
    };

    form.onsubmit = (e) => {
      // علّق السطر التالي إذا أردت إرسال النموذج فعليًا للباك-إند عبر الراوت.
      e.preventDefault();

      const projectData = {
        title: document.getElementById("title").value,
        description: document.getElementById("description").value,
        supervisor: document.getElementById("supervisor").value,
        team: Array.from(document.getElementById("team").selectedOptions).map(opt => opt.value),
        repoUrl: document.getElementById("repoUrl").value,
        reportStatus: "Pending",
      };
      projects.push(projectData);
      alert("✅ تم إضافة المشروع!");
      form.reset();
      document.getElementById("popupBox").style.display = "none";
    };

    // عرض مشروعي (للديمو)
    viewBtn.onclick = () => {
      renderProjects();
      viewPopup.style.display = "flex";
    };

    function renderProjects() {
      // إذا تسحب من السيرفر، تجاهل هذا واستعمل Blade بالأعلى
      projectListDiv.innerHTML = "";
      if (projects.length === 0) {
        projectListDiv.innerHTML = "<p>لا توجد مشاريع بعد.</p>";
      } else {
        projects.forEach((proj, index) => {
          const projDiv = document.createElement("div");
          projDiv.style.borderBottom = "1px solid #ccc";
          projDiv.style.padding = "10px 0";
          projDiv.innerHTML = `
            <h4>العنوان : ${proj.title}</h4>
            <p>الوصف : ${proj.description}</p>
            <p><strong>المشرف : </strong> ${proj.supervisor}</p>
            <p><strong>الحالة : </strong> ${proj.reportStatus}</p>
            <div class="action-buttons">
              <button onclick="alert('View report for ${proj.title}')">عرض التقرير</button>
              <button class="danger" onclick="deleteProject(${index})">حذف</button>
            </div>
          `;
          projectListDiv.appendChild(projDiv);
        });
      }
    }

    function deleteProject(index) {
      if (confirm("هل أنت متأكد من حذف المشروع؟")) {
        projects.splice(index, 1);
        renderProjects();
      }
    }

    // طلبات الانضمام (للديمو)
    joiningBtn.onclick = () => {
      joiningList.innerHTML = "";
      if (projects.length === 0) {
        joiningList.innerHTML = "<p>لا توجد مشاريع بعد.</p>";
      } else {
        projects.forEach((proj, projIndex) => {
          const projDiv = document.createElement("div");
          projDiv.style.borderBottom = "1px solid #ccc";
          projDiv.style.padding = "10px 0";
          let membersHtml = (proj.team || []).map((member, index) => `
            <li>
              <span>${member} <br> الحالة : <span id="status-${projIndex}-${index}">Pending</span></span>
              <div class='d-flex'>
                <button class="joining-btn acceptBtn" id="accept-${projIndex}-${index}">قبول</button>
                <button class="joining-btn rejectBtn" id="reject-${projIndex}-${index}">رفض</button>
                <button class="joining-btn detailsBtn" onclick="openDetails(${projIndex})">تفاصيل</button>
              </div>
            </li>
          `).join("");
          projDiv.innerHTML = `<ul>${membersHtml}</ul>`;
          joiningList.appendChild(projDiv);

          (proj.team || []).forEach((_, index) => {
            const acceptBtn = document.getElementById(`accept-${projIndex}-${index}`);
            const rejectBtn = document.getElementById(`reject-${projIndex}-${index}`);
            const statusSpan = document.getElementById(`status-${projIndex}-${index}`);

            acceptBtn.onclick = () => {
              const alreadyAccepted = (proj.team || []).some((_, i) =>
                document.getElementById(`status-${projIndex}-${i}`)?.innerText === "Accepted"
              );
              if (alreadyAccepted) {
                alert("❌ يُقبل طالب واحد لكل مشروع.");
                return;
              }
              statusSpan.innerText = "Accepted";
            };
            rejectBtn.onclick = () => { statusSpan.innerText = "Rejected"; };
          });
        });
      }
      joiningPopup.style.display = "flex";
    };

    function openDetails(projIndex) {
      const proj = projects[projIndex];
      document.getElementById("detailsTitle").innerText = `العنوان : ${proj.title}`;
      document.getElementById("detailsDescription").innerText = `الوصف : ${proj.description}`;
      document.getElementById("detailsSupervisor").innerText = proj.supervisor;
      const teamList = document.getElementById("detailsTeam");
      teamList.innerHTML = "";
      (proj.team || []).forEach((member) => {
        let li = document.createElement("li");
        li.textContent = member;
        teamList.appendChild(li);
      });
      document.getElementById("detailsPopup").style.display = "flex";
    }
  </script>
</body>
</html>
