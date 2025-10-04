<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Accepted Projects</title>
  <link rel="stylesheet" href="{{ asset("css/supervisor.css") }}" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .note-row .note-form { 
      display: none; 
      margin-top: 12px; 
      padding: 16px; 
      background-color: #f8f9fa; 
      border: 1px solid #e9ecef; 
      border-radius: 8px; 
    }
    .note-row.show .note-form {
      display: block;
    }
    .note-form textarea { 
      width: 100%; 
      min-height: 100px; 
      padding: 12px; 
      border: 1px solid #d1d5db; 
      border-radius: 6px; 
      font-family: inherit;
      font-size: 14px;
      resize: vertical;
    }
    .note-form .form-actions { 
      margin-top: 12px; 
      display: flex; 
      gap: 8px; 
      justify-content: flex-end;
    }
    .note-indicator {
      position: relative;
    }
    .note-indicator::after {
      content: '';
      position: absolute;
      top: -2px;
      right: -2px;
      width: 8px;
      height: 8px;
      background-color: #28a745;
      border-radius: 50%;
      display: none;
    }
    .note-indicator.has-note::after {
      display: block;
    }
    .note-row {
      display: none;
    }
    .note-row.show {
      display: table-row;
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="brand">
      <i class="fa-solid fa-gauge-high"></i>
      <span class="title">Supervisor Dashboard</span>
    </div>
    <div class="nav-actions">
      <a class="link" href="{{ route("supervisor.dashboard") }}">
        <i class="fa-solid fa-chart-line"></i> Dashboard
      </a>
      <a class="link" href="{{ route("supervisor.requests.pending") }}">
        <i class="fa-solid fa-hourglass-half"></i> Pending Requests
      </a>
      <a class="link" href="{{ route("supervisor.students.verify.index") }}">
        <i class="fa-solid fa-user-check"></i> Student Verification
      </a>
      <a class="link" href="{{ route("supervisor.profile.edit") }}">
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
          <i class="fa-solid fa-circle-check"></i>
          <h1>Accepted Projects</h1>
        </div>
        <span class="subtitle">List of all approved projects</span>
      </div>

      @if(session("success"))
        <div class="alert success">{{ session("success") }}</div>
      @endif
      @if(session("error"))
        <div class="alert error">{{ session("error") }}</div>
      @endif

      @if(($projects ?? collect())->count() === 0)
        <p class="muted">No accepted projects yet.</p>
      @else
        <table class="table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Student(s)</th>
              <th>Description</th>
              <th>GitHub URL</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($projects as $project)
              @php
                $repoUrl = optional($project->repository)->github_url;
                $hasAnalysis = (bool) $project->codeAnalysisReport;
                $hasEvaluation = (bool) $project->evaluation;
                $plagCount = (optional($project->plagiarismChecks)->count() ?? 0) + (optional($project->plagiarismChecksAsProject2)->count() ?? 0);
                $hasPlagiarism = $plagCount > 0;
                $ready = $hasAnalysis && $hasPlagiarism && $hasEvaluation;
              @endphp
              <tr>
                <td>{{ $project->title }}</td>
                <td>
                  @foreach($project->students as $st)
                    @php
                      $u = $st->user;
                      $name = ($u->name
                        ?? trim(($u->first_name ?? "")." ".($u->last_name ?? ""))
                        ?? trim(($st->first_name ?? "")." ".($st->last_name ?? ""))) ?: "";
                    @endphp
                    <div>{{ $name }}</div>
                  @endforeach
                </td>
                <td>{{ $project->description }}</td>
                <td>
                  @if($repoUrl)
                    <a href="{{ $repoUrl }}" target="_blank" rel="noopener">View Repo</a>
                  @else
                    <span class="muted"></span>
                  @endif
                </td>
                <td class="actions" style="display:flex; gap:8px; flex-wrap:wrap">
                  {{-- Code Analysis (Sonar) --}}
                  <form method="POST" action="{{ route("supervisor.projects.analyze", $project->id) }}">
                    @csrf
                    <button type="submit" class="btn info">
                      <i class="fa-solid fa-magnifying-glass"></i> Code Analysis
                    </button>
                  </form>

                  {{-- Plagiarism --}}
                  <form method="GET" action="{{ route("supervisor.projects.plagiarism", $project->id) }}">
                    @csrf
                    <button type="submit" class="btn warning">
                      <i class="fa-solid fa-copy"></i> Plagiarism
                    </button>
                  </form>

                  {{-- Evaluate / View Evaluation --}}
                  <a class="btn success" href="{{ route("supervisor.projects.evaluation.show", $project->id) }}" title="View Evaluation">
                    <i class="fa-solid fa-star"></i> Evaluation
                  </a>

                  {{-- Supervisor Note --}}
                  <button type="button" class="btn info note-indicator {{ $project->supervisor_note ? 'has-note' : '' }}" onclick="toggleNoteForm({{ $project->id }})" title="{{ $project->supervisor_note ? 'Edit Supervisor Note' : 'Add Supervisor Note' }}">
                    <i class="fa-solid fa-sticky-note"></i> {{ $project->supervisor_note ? 'Edit Note' : 'Add Note' }}
                  </button>

                  {{-- Final Report always visible as requested --}}
                  <a class="btn" href="{{ route("projects.report", $project) }}" title="Open Final Report">
                    <i class="fa-solid fa-file"></i> Final Report
                  </a>
                </td>
              </tr>
              <tr id="note-row-{{ $project->id }}" class="note-row">
                <td colspan="5">
                  <div class="note-form" id="note-form-{{ $project->id }}">
                    <h4 style="margin: 0 0 12px 0; color: #495057;">
                      <i class="fa-solid fa-sticky-note"></i> 
                      {{ $project->supervisor_note ? 'Edit Supervisor Note' : 'Add Supervisor Note' }}
                    </h4>
                    <form method="POST" action="{{ route("supervisor.projects.note", $project->id) }}">
                      @csrf
                      <textarea 
                        name="supervisor_note" 
                        placeholder="Write your supervisor note for this project... (e.g., feedback, suggestions, concerns, etc.)"
                        maxlength="1000"
                      >{{ $project->supervisor_note }}</textarea>
                      <div style="font-size: 12px; color: #6c757d; margin-top: 4px;">
                        <span id="char-count-{{ $project->id }}">{{ strlen($project->supervisor_note ?? '') }}</span>/1000 characters
                      </div>
                      <div class="form-actions">
                        <button type="submit" class="btn success">
                          <i class="fa-solid fa-save"></i> Save Note
                        </button>
                        <button type="button" class="btn" onclick="toggleNoteForm({{ $project->id }})">
                          <i class="fa-solid fa-times"></i> Cancel
                        </button>
                      </div>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif

      <!-- Navigation Actions -->
      <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="{{ route('supervisor.dashboard') }}" class="btn">
          <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
        <a href="{{ route('supervisor.requests.pending') }}" class="btn">
          <i class="fa-solid fa-hourglass-half"></i> View Pending Requests
        </a>
      </div>
    </section>
  </main>

  <script>
    function toggleNoteForm(projectId) {
      const formRow = document.getElementById("note-row-" + projectId);
      if (!formRow) return;

      const isShown = formRow.classList.contains('show');
      if (isShown) {
        formRow.classList.remove('show');
      } else {
        formRow.classList.add('show');
        setTimeout(() => {
          const textarea = formRow.querySelector('textarea[name="supervisor_note"]');
          if (textarea) textarea.focus();
        }, 50);
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('textarea[name="supervisor_note"]').forEach(function(textarea) {
        const tr = textarea.closest('tr');
        const projectId = tr ? tr.id.replace('note-row-', '') : '';
        textarea.addEventListener('input', function() {
          const charCount = document.getElementById('char-count-' + projectId);
          if (charCount) charCount.textContent = this.value.length;
        });
      });
    });
  </script>
</body>
</html>
