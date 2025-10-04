<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Final Report - {{ $project->title }}</title>
  <link rel="stylesheet" href="{{ asset('css/supervisor.css') }}" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .container{max-width:1100px;margin:24px auto;padding:0 12px}
    .page{background:#e6f4ff;border:1px solid #cfe8ff;border-radius:10px;padding:18px}
    .header{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:12px}
    .title{display:flex;align-items:center;gap:8px}
    .title h1{margin:0}
    .subtitle{color:#6b7280}
    .section{margin:16px 0;padding:16px;border:1px solid #e5e7eb;border-radius:8px;background:#fff}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
    .metric{padding:12px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px}
    .muted{color:#6b7280}
    .table{width:100%;border-collapse:collapse}
    .table th,.table td{border:1px solid #e5e7eb;padding:8px;text-align:left}
  </style>
</head>
<body>
  <main class="container">
    <div class="page">
      <div class="header">
        <div class="title">
          <h1>Final Project Report</h1>
        </div>
        <span class="subtitle">Project: {{ $project->title }}</span>
      </div>

      <!-- Navigation Actions -->
      <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        @auth
          @if(auth()->user()->role === 'supervisor')
            <a href="{{ route('supervisor.projects.accepted') }}" class="btn" style="background: #007bff; color: white; text-decoration: none; padding: 8px 16px; border-radius: 5px;">
              <i class="fa-solid fa-arrow-left"></i> Back to Projects
            </a>
            <a href="{{ route('supervisor.dashboard') }}" class="btn" style="background: #6b7280; color: white; text-decoration: none; padding: 8px 16px; border-radius: 5px;">
              <i class="fa-solid fa-chart-line"></i> Dashboard
            </a>
          @elseif(auth()->user()->role === 'student')
            <a href="{{ route('student.dashboard') }}" class="btn" style="background: #007bff; color: white; text-decoration: none; padding: 8px 16px; border-radius: 5px;">
              <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
          @endif
        @else
          <a href="{{ route('welcome') }}" class="btn" style="background: #6b7280; color: white; text-decoration: none; padding: 8px 16px; border-radius: 5px;">
            <i class="fa-solid fa-home"></i> Home
          </a>
        @endauth
      </div>

      @php
        // دالة محلية لإظهار اسم المستخدم بشكل مرن
        $displayUser = function($user) {
            if (!$user) return null;
            if (!empty($user->name)) return $user->name;
            $fn = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            if ($fn !== '') return $fn;
            return $user->email ?? null;
        };

        // فورمات نسبة مئوية/أرقام
        $fmt = function($v, $suffix = '') {
            if ($v === null || $v === '' ) return '—';
            // حاول تحويله لعدد
            if (is_numeric($v)) {
                // لو كان عشري خليه حتى رقمين
                $v = (floor($v) != $v) ? number_format((float)$v, 2) : (string)(int)$v;
            }
            return $v . $suffix;
        };

        // جلب آخر فحص سرقة وتوحيد الحقول
        $plag = $latestPlagiarism;
        $sim  = $plag ? ($plag->similarity_percentage ?? $plag->similarity ?? null) : null;
        $rep  = $plag ? ($plag->report_url ?? null) : null;

        // تقرير التحليل
        $ca = $project->codeAnalysisReport;

        // تحديد نوع المستخدم
        $isStudent = auth()->check() && auth()->user()->role === 'student';
        $isSupervisor = auth()->check() && auth()->user()->role === 'supervisor';
      @endphp

      @if($isStudent)
        <!-- Student View - Simplified Overview -->
        <div class="section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
          <div class="title">
            <h2 style="color: white; margin: 0;">
              <i class="fa-solid fa-graduation-cap"></i> Your Project Results
            </h2>
          </div>
          <p style="margin: 8px 0; font-size: 16px;"><strong>Project:</strong> {{ $project->title }}</p>
          <p style="margin: 8px 0;"><strong>Supervisor:</strong> {{ $displayUser(optional($project->supervisor)->user) ?? '—' }}</p>
          <p style="margin: 8px 0;"><strong>Team:</strong>
            @if($project->students && $project->students->count())
              {{ $project->students->map(fn($st) => $displayUser(optional($st)->user))->filter()->values()->join(', ') }}
            @else
              —
            @endif
          </p>
        </div>
      @else
        <!-- Supervisor/Admin View - Full Overview -->
        <div class="section">
          <div class="title"><h2>Overview</h2></div>
          <p><strong>Description:</strong> {{ $project->description ?: '—' }}</p>

          <p><strong>Supervisor:</strong>
            {{ $displayUser(optional($project->supervisor)->user) ?? '—' }}
          </p>

          <p><strong>Team:</strong>
            @if($project->students && $project->students->count())
              {{ $project->students->map(fn($st) => $displayUser(optional($st)->user))
                                   ->filter()
                                   ->values()
                                   ->join(', ') }}
            @else
              <span class="muted">—</span>
            @endif
          </p>

          <p><strong>Repository:</strong>
            @if(optional($project->repository)->github_url)
              <a href="{{ $project->repository->github_url }}" target="_blank" rel="noopener">
                {{ $project->repository->github_url }}
              </a>
            @else
              <span class="muted">—</span>
            @endif
          </p>
        </div>
      @endif

      @if($isStudent)
        <!-- Student View - Simplified Code Analysis -->
        <div class="section">
          <div class="title">
            <h2><i class="fa-solid fa-code"></i> Code Quality Summary</h2>
          </div>
          @if($ca)
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
              <div style="text-align: center; padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb;">
                <div style="font-size: 28px; font-weight: 700; color: {{ $ca->bugs > 0 ? '#dc2626' : '#16a34a' }};">
                  {{ $fmt($ca->bugs) }}
                </div>
                <div style="font-size: 14px; color: #6b7280; margin-top: 4px;">Bugs</div>
              </div>
              <div style="text-align: center; padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb;">
                <div style="font-size: 28px; font-weight: 700; color: {{ $ca->vulnerabilities > 0 ? '#dc2626' : '#16a34a' }};">
                  {{ $fmt($ca->vulnerabilities) }}
                </div>
                <div style="font-size: 14px; color: #6b7280; margin-top: 4px;">Security Issues</div>
              </div>
              <div style="text-align: center; padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb;">
                <div style="font-size: 28px; font-weight: 700; color: {{ $ca->coverage >= 70 ? '#16a34a' : ($ca->coverage >= 50 ? '#f59e0b' : '#dc2626') }};">
                  {{ $fmt($ca->coverage, '%') }}
                </div>
                <div style="font-size: 14px; color: #6b7280; margin-top: 4px;">Test Coverage</div>
              </div>
              <div style="text-align: center; padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb;">
                <div style="font-size: 28px; font-weight: 700; color: {{ $ca->quality_gate === 'OK' ? '#16a34a' : '#dc2626' }};">
                  @if($ca->quality_gate === 'OK')
                    ✅ Pass
                  @elseif($ca->quality_gate === 'ERROR')
                    ❌ Fail
                  @else
                    —
                  @endif
                </div>
                <div style="font-size: 14px; color: #6b7280; margin-top: 4px;">Quality Gate</div>
              </div>
            </div>
            
            <!-- Overall Assessment -->
            <div style="margin-top: 20px; padding: 16px; background: {{ $ca->quality_gate === 'OK' ? '#dcfce7' : '#fee2e2' }}; border: 1px solid {{ $ca->quality_gate === 'OK' ? '#bbf7d0' : '#fecaca' }}; border-radius: 8px;">
              <h4 style="margin: 0 0 8px 0; color: {{ $ca->quality_gate === 'OK' ? '#166534' : '#991b1b' }};">
                @if($ca->quality_gate === 'OK')
                  <i class="fa-solid fa-check-circle"></i> Excellent Code Quality!
                @else
                  <i class="fa-solid fa-exclamation-triangle"></i> Code Quality Needs Improvement
                @endif
              </h4>
              <p style="margin: 0; color: {{ $ca->quality_gate === 'OK' ? '#166534' : '#991b1b' }};">
                @if($ca->quality_gate === 'OK')
                  Your code meets all quality standards. Great job!
                @else
                  Consider addressing the issues above to improve your code quality.
                @endif
              </p>
            </div>
          @else
            <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb;">
              <i class="fa-solid fa-hourglass-half" style="font-size: 48px; color: #6b7280; margin-bottom: 16px;"></i>
              <h4 style="margin: 0 0 8px 0; color: #6b7280;">Code Analysis Pending</h4>
              <p style="margin: 0; color: #6b7280;">Your supervisor will run code analysis soon.</p>
            </div>
          @endif
        </div>
      @else
        <!-- Supervisor/Admin View - Full Code Analysis -->
        <div class="section">
          <div class="title"><h2>Code Analysis (SonarQube)</h2></div>

          @if($ca)
            <div class="grid">
              <div class="metric"><strong>Bugs:</strong> {{ $fmt($ca->bugs) }}</div>
              <div class="metric"><strong>Vulnerabilities:</strong> {{ $fmt($ca->vulnerabilities) }}</div>
              <div class="metric"><strong>Code Smells:</strong> {{ $fmt($ca->code_smells) }}</div>
              <div class="metric"><strong>Coverage:</strong> {{ $fmt($ca->coverage, '%') }}</div>
              <div class="metric"><strong>Duplicated Lines:</strong> {{ $fmt($ca->duplicated_lines_density, '%') }}</div>
              <div class="metric"><strong>Lines of Code (ncloc):</strong> {{ $fmt($ca->ncloc) }}</div>
              <div class="metric"><strong>Security Hotspots:</strong> {{ $fmt($ca->security_hotspots) }}</div>
            </div>

            @if(($analysisResults ?? collect())->count())
              <details style="margin-top:12px">
                <summary>Show Detailed Issues ({{ $analysisResults->count() }})</summary>
                <ul>
                  @foreach($analysisResults as $res)
                    <li>
                      [{{ $res->issue_type }}] {{ $res->message }}
                      — {{ $res->component }}:{{ $res->line }}
                    </li>
                  @endforeach
                </ul>
              </details>
            @endif
          @else
            <p class="muted">No analysis report yet.</p>
          @endif
        </div>
      @endif

      @if($isStudent)
        <!-- Student View - Simplified Plagiarism -->
        <div class="section">
          <div class="title">
            <h2><i class="fa-solid fa-copy"></i> Originality Check</h2>
          </div>
          @if($plag)
            @php
              // تحديد المشروع الآخر في المقارنة
              $otherProject = null;
              if ($plag->project1_id == $project->id) {
                  $otherProject = $plag->project2;
              } elseif ($plag->project2_id == $project->id) {
                  $otherProject = $plag->project1;
              }
            @endphp
            
            <div style="text-align: center; padding: 20px;">
              <div style="font-size: 48px; margin-bottom: 16px;">
                @if($sim <= 10)
                  <i class="fa-solid fa-check-circle" style="color: #16a34a;"></i>
                @elseif($sim <= 30)
                  <i class="fa-solid fa-exclamation-triangle" style="color: #f59e0b;"></i>
                @else
                  <i class="fa-solid fa-times-circle" style="color: #dc2626;"></i>
                @endif
              </div>
              
              <h3 style="margin: 0 0 8px 0; color: {{ $sim <= 10 ? '#16a34a' : ($sim <= 30 ? '#f59e0b' : '#dc2626') }};">
                @if($sim <= 10)
                  Excellent Originality!
                @elseif($sim <= 30)
                  Good Originality
                @else
                  Originality Concerns
                @endif
              </h3>
              
              <div style="font-size: 32px; font-weight: 700; color: {{ $sim <= 10 ? '#16a34a' : ($sim <= 30 ? '#f59e0b' : '#dc2626') }}; margin: 16px 0;">
                {{ $fmt($sim, '%') }} Similarity
              </div>
              
              @if($otherProject)
                <p style="margin: 8px 0; color: #6b7280;">
                  Compared with: <strong>{{ $otherProject->title }}</strong>
                </p>
              @endif
              
              <div style="margin-top: 20px; padding: 16px; background: {{ $sim <= 10 ? '#dcfce7' : ($sim <= 30 ? '#fef3c7' : '#fee2e2') }}; border: 1px solid {{ $sim <= 10 ? '#bbf7d0' : ($sim <= 30 ? '#fed7aa' : '#fecaca') }}; border-radius: 8px;">
                <p style="margin: 0; color: {{ $sim <= 10 ? '#166534' : ($sim <= 30 ? '#92400e' : '#991b1b') }};">
                  @if($sim <= 10)
                    <i class="fa-solid fa-star"></i> Your work shows excellent originality!
                  @elseif($sim <= 30)
                    <i class="fa-solid fa-info-circle"></i> Your work shows good originality with minor similarities.
                  @else
                    <i class="fa-solid fa-exclamation-triangle"></i> Consider reviewing your work for originality.
                  @endif
                </p>
              </div>
            </div>
          @else
            <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb;">
              <i class="fa-solid fa-hourglass-half" style="font-size: 48px; color: #6b7280; margin-bottom: 16px;"></i>
              <h4 style="margin: 0 0 8px 0; color: #6b7280;">Originality Check Pending</h4>
              <p style="margin: 0; color: #6b7280;">Your supervisor will run plagiarism check soon.</p>
            </div>
          @endif
        </div>
      @else
        <!-- Supervisor/Admin View - Full Plagiarism -->
        <div class="section">
          <div class="title"><h2>Plagiarism</h2></div>
          @if($plag)
            <p><strong>Similarity:</strong> {{ $fmt($sim, '%') }}</p>
            @php
              // تحديد المشروع الآخر في المقارنة
              $otherProject = null;
              if ($plag->project1_id == $project->id) {
                  $otherProject = $plag->project2;
              } elseif ($plag->project2_id == $project->id) {
                  $otherProject = $plag->project1;
              }
            @endphp
            @if($otherProject)
              <p><strong>Compared with:</strong> {{ $otherProject->title }}</p>
            @endif
          @else
            <p class="muted">No plagiarism check found.</p>
          @endif
        </div>
      @endif

      @if($isStudent)
        <!-- Student View - Simplified Evaluation -->
        <div class="section">
          <div class="title">
            <h2><i class="fa-solid fa-star"></i> Your Performance</h2>
          </div>
          @if($project->evaluation)
            @if(($project->studentEvaluations ?? collect())->count())
              @php
                $studentEval = $project->studentEvaluations->where('student_id', auth()->user()->student->id)->first();
                $avgScore = $project->studentEvaluations->avg('score') ?? 0;
              @endphp
              
              @if($studentEval)
                <div style="text-align: center; padding: 20px;">
                  <div style="font-size: 48px; margin-bottom: 16px;">
                    @if($studentEval->score >= 80)
                      <i class="fa-solid fa-trophy" style="color: #f59e0b;"></i>
                    @elseif($studentEval->score >= 60)
                      <i class="fa-solid fa-medal" style="color: #6b7280;"></i>
                    @else
                      <i class="fa-solid fa-chart-line" style="color: #3b82f6;"></i>
                    @endif
                  </div>
                  
                  <h3 style="margin: 0 0 8px 0; color: {{ $studentEval->score >= 80 ? '#f59e0b' : ($studentEval->score >= 60 ? '#6b7280' : '#3b82f6') }};">
                    @if($studentEval->score >= 80)
                      Excellent Performance!
                    @elseif($studentEval->score >= 60)
                      Good Performance
                    @else
                      Keep Improving
                    @endif
                  </h3>
                  
                  <div style="font-size: 32px; font-weight: 700; color: {{ $studentEval->score >= 80 ? '#f59e0b' : ($studentEval->score >= 60 ? '#6b7280' : '#3b82f6') }}; margin: 16px 0;">
                    {{ round($studentEval->score, 1) }}/100
                  </div>
                  
                  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 16px; margin: 20px 0;">
                    <div style="text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb;">
                      <div style="font-size: 20px; font-weight: 700; color: #3b82f6;">{{ $studentEval->commits }}</div>
                      <div style="font-size: 12px; color: #6b7280;">Commits</div>
                    </div>
                    <div style="text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb;">
                      <div style="font-size: 20px; font-weight: 700; color: #059669;">{{ $studentEval->prs_opened }}</div>
                      <div style="font-size: 12px; color: #6b7280;">PRs Opened</div>
                    </div>
                    <div style="text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb;">
                      <div style="font-size: 20px; font-weight: 700; color: #16a34a;">{{ $studentEval->prs_merged }}</div>
                      <div style="font-size: 12px; color: #6b7280;">PRs Merged</div>
                    </div>
                    <div style="text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb;">
                      <div style="font-size: 20px; font-weight: 700; color: #7c3aed;">{{ $studentEval->reviews }}</div>
                      <div style="font-size: 12px; color: #6b7280;">Reviews</div>
                    </div>
                  </div>
                  
                  <div style="margin-top: 20px; padding: 16px; background: {{ $studentEval->score >= 80 ? '#dcfce7' : ($studentEval->score >= 60 ? '#fef3c7' : '#dbeafe') }}; border: 1px solid {{ $studentEval->score >= 80 ? '#bbf7d0' : ($studentEval->score >= 60 ? '#fed7aa' : '#bfdbfe') }}; border-radius: 8px;">
                    <p style="margin: 0; color: {{ $studentEval->score >= 80 ? '#166534' : ($studentEval->score >= 60 ? '#92400e' : '#1e40af') }};">
                      @if($studentEval->score >= 80)
                        <i class="fa-solid fa-star"></i> Outstanding work! You've demonstrated excellent development practices.
                      @elseif($studentEval->score >= 60)
                        <i class="fa-solid fa-thumbs-up"></i> Good work! Keep up the great development practices.
                      @else
                        <i class="fa-solid fa-lightbulb"></i> Keep practicing! Focus on more commits and pull requests.
                      @endif
                    </p>
                  </div>
                </div>
              @else
                <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb;">
                  <i class="fa-solid fa-user-slash" style="font-size: 48px; color: #6b7280; margin-bottom: 16px;"></i>
                  <h4 style="margin: 0 0 8px 0; color: #6b7280;">No Individual Evaluation</h4>
                  <p style="margin: 0; color: #6b7280;">Your individual performance hasn't been evaluated yet.</p>
                </div>
              @endif
            @else
              <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb;">
                <i class="fa-solid fa-hourglass-half" style="font-size: 48px; color: #6b7280; margin-bottom: 16px;"></i>
                <h4 style="margin: 0 0 8px 0; color: #6b7280;">Evaluation Pending</h4>
                <p style="margin: 0; color: #6b7280;">Your supervisor will evaluate your performance soon.</p>
              </div>
            @endif
          @else
            <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb;">
              <i class="fa-solid fa-hourglass-half" style="font-size: 48px; color: #6b7280; margin-bottom: 16px;"></i>
              <h4 style="margin: 0 0 8px 0; color: #6b7280;">Evaluation Pending</h4>
              <p style="margin: 0; color: #6b7280;">Your supervisor will evaluate your performance soon.</p>
            </div>
          @endif
        </div>
      @else
        <!-- Supervisor/Admin View - Full Evaluation -->
        <div class="section">
          <div class="title"><h2>Evaluation</h2></div>
          @if($project->evaluation)
            @if(($project->studentEvaluations ?? collect())->count())
              <table class="table" style="margin-top:8px">
                <thead>
                  <tr>
                    <th>Student</th><th>Commits</th><th>PRs Opened</th><th>PRs Merged</th><th>Reviews</th><th>Score</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($project->studentEvaluations as $se)
                    <tr>
                      <td>
                        {{ $displayUser(optional($se->student)->user) ?? '—' }}
                        @if(optional($se->student)->github_username)
                          <span class="muted">({{ $se->student->github_username }})</span>
                        @endif
                      </td>
                      <td>{{ $fmt($se->commits) }}</td>
                      <td>{{ $fmt($se->prs_opened) }}</td>
                      <td>{{ $fmt($se->prs_merged) }}</td>
                      <td>{{ $fmt($se->reviews) }}</td>
                      <td>{{ $fmt($se->score) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            @else
              <p class="muted">No per-student data yet.</p>
            @endif
          @else
            <p class="muted">No evaluation computed yet.</p>
          @endif
        </div>
      @endif

      @if($isStudent)
        <!-- Student View - Supervisor Note -->
        <div class="section">
          <div class="title">
            <h2><i class="fa-solid fa-comment"></i> Supervisor Feedback</h2>
          </div>
          @if($project->supervisor_note)
            <div style="padding: 20px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1px solid #bae6fd; border-radius: 12px;">
              <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <i class="fa-solid fa-user-tie" style="font-size: 24px; color: #0369a1;"></i>
                <h4 style="margin: 0; color: #0369a1;">From Your Supervisor</h4>
              </div>
              <p style="margin: 0; white-space: pre-wrap; color: #0c4a6e; font-size: 16px; line-height: 1.6;">{{ $project->supervisor_note }}</p>
            </div>
          @else
            <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb;">
              <i class="fa-solid fa-comment-slash" style="font-size: 48px; color: #6b7280; margin-bottom: 16px;"></i>
              <h4 style="margin: 0 0 8px 0; color: #6b7280;">No Feedback Yet</h4>
              <p style="margin: 0; color: #6b7280;">Your supervisor hasn't added feedback yet.</p>
            </div>
          @endif
        </div>
      @else
        <!-- Supervisor/Admin View - Supervisor Note -->
        <div class="section">
          <div class="title"><h2>Supervisor Note</h2></div>
          @if($project->supervisor_note)
            <p style="white-space: pre-wrap; padding: 12px; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px;">{{ $project->supervisor_note }}</p>
          @else
            <p class="muted">No supervisor note added yet.</p>
          @endif
        </div>
      @endif

      @php
        $hasAnalysis = (bool)$project->codeAnalysisReport;
        $hasPlagiarism = (bool)$latestPlagiarism;
        $hasEvaluation = (bool)$project->evaluation;
        $ready = $hasAnalysis && $hasPlagiarism && $hasEvaluation;
        
        $missing = [];
        if (!$hasAnalysis) $missing[] = 'analysis';
        if (!$hasPlagiarism) $missing[] = 'plagiarism';
        if (!$hasEvaluation) $missing[] = 'evaluation';
      @endphp
      @if($isStudent)
        <!-- Student View - Simplified Status -->
        <div class="section">
          <div class="title">
            <h2><i class="fa-solid fa-clipboard-check"></i> Report Status</h2>
          </div>
          @php
            $hasAnalysis = (bool)$project->codeAnalysisReport;
            $hasPlagiarism = (bool)$latestPlagiarism;
            $hasEvaluation = (bool)$project->evaluation;
            $ready = $hasAnalysis && $hasPlagiarism && $hasEvaluation;
            
            $missing = [];
            if (!$hasAnalysis) $missing[] = 'Code Analysis';
            if (!$hasPlagiarism) $missing[] = 'Originality Check';
            if (!$hasEvaluation) $missing[] = 'Performance Evaluation';
          @endphp
          
          <div style="text-align: center; padding: 20px;">
            <div style="font-size: 48px; margin-bottom: 16px;">
              @if($ready)
                <i class="fa-solid fa-check-circle" style="color: #16a34a;"></i>
              @else
                <i class="fa-solid fa-clock" style="color: #f59e0b;"></i>
              @endif
            </div>
            
            <h3 style="margin: 0 0 8px 0; color: {{ $ready ? '#16a34a' : '#f59e0b' }};">
              @if($ready)
                Report Complete!
              @else
                Report In Progress
              @endif
            </h3>
            
            <div style="margin: 20px 0;">
              <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
                <div style="text-align: center; padding: 16px; background: {{ $hasAnalysis ? '#dcfce7' : '#fef3c7' }}; border: 1px solid {{ $hasAnalysis ? '#bbf7d0' : '#fed7aa' }}; border-radius: 8px;">
                  <div style="font-size: 24px; margin-bottom: 8px;">
                    @if($hasAnalysis)
                      <i class="fa-solid fa-check" style="color: #16a34a;"></i>
                    @else
                      <i class="fa-solid fa-hourglass-half" style="color: #f59e0b;"></i>
                    @endif
                  </div>
                  <div style="font-size: 14px; font-weight: 600; color: {{ $hasAnalysis ? '#166534' : '#92400e' }};">Code Quality</div>
                </div>
                
                <div style="text-align: center; padding: 16px; background: {{ $hasPlagiarism ? '#dcfce7' : '#fef3c7' }}; border: 1px solid {{ $hasPlagiarism ? '#bbf7d0' : '#fed7aa' }}; border-radius: 8px;">
                  <div style="font-size: 24px; margin-bottom: 8px;">
                    @if($hasPlagiarism)
                      <i class="fa-solid fa-check" style="color: #16a34a;"></i>
                    @else
                      <i class="fa-solid fa-hourglass-half" style="color: #f59e0b;"></i>
                    @endif
                  </div>
                  <div style="font-size: 14px; font-weight: 600; color: {{ $hasPlagiarism ? '#166534' : '#92400e' }};">Originality</div>
                </div>
                
                <div style="text-align: center; padding: 16px; background: {{ $hasEvaluation ? '#dcfce7' : '#fef3c7' }}; border: 1px solid {{ $hasEvaluation ? '#bbf7d0' : '#fed7aa' }}; border-radius: 8px;">
                  <div style="font-size: 24px; margin-bottom: 8px;">
                    @if($hasEvaluation)
                      <i class="fa-solid fa-check" style="color: #16a34a;"></i>
                    @else
                      <i class="fa-solid fa-hourglass-half" style="color: #f59e0b;"></i>
                    @endif
                  </div>
                  <div style="font-size: 14px; font-weight: 600; color: {{ $hasEvaluation ? '#166534' : '#92400e' }};">Performance</div>
                </div>
              </div>
            </div>
            
            @if(!$ready)
              <div style="margin-top: 20px; padding: 16px; background: #fef3c7; border: 1px solid #fed7aa; border-radius: 8px;">
                <p style="margin: 0; color: #92400e;">
                  <i class="fa-solid fa-info-circle"></i> 
                  @if(count($missing) === 1)
                    {{ $missing[0] }} is still pending.
                  @else
                    {{ implode(', ', array_slice($missing, 0, -1)) }} and {{ end($missing) }} are still pending.
                  @endif
                </p>
              </div>
            @else
              <div style="margin-top: 20px; padding: 16px; background: #dcfce7; border: 1px solid #bbf7d0; border-radius: 8px;">
                <p style="margin: 0; color: #166534;">
                  <i class="fa-solid fa-star"></i> Congratulations! Your final report is complete and ready for review.
                </p>
              </div>
            @endif
          </div>
        </div>
      @else
        <!-- Supervisor/Admin View - Full Status -->
        <div class="section">
          <div class="title"><h2>Status</h2></div>
          <p>
            Final report status:
            @if($ready)
              <strong>Complete</strong>
            @else
              <span class="muted">Incomplete (missing: {{ implode(', ', $missing) }})</span>
            @endif
          </p>
          
          <div style="margin-top: 12px; padding: 12px; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px;">
            <strong>Requirements Status:</strong>
            <ul style="margin: 8px 0; padding-left: 20px;">
              <li>Code Analysis: {{ $hasAnalysis ? '✅ Complete' : '❌ Missing' }}</li>
              <li>Plagiarism Check: {{ $hasPlagiarism ? '✅ Complete' : '❌ Missing' }}</li>
              <li>Evaluation: {{ $hasEvaluation ? '✅ Complete' : '❌ Missing' }}</li>
            </ul>
          </div>
        </div>
      @endif

      <!-- Bottom Navigation -->
      <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; flex-wrap: wrap;">
        @auth
          @if(auth()->user()->role === 'supervisor')
            <a href="{{ route('supervisor.projects.accepted') }}" class="btn" style="background: #007bff; color: white; text-decoration: none; padding: 8px 16px; border-radius: 5px;">
              <i class="fa-solid fa-arrow-left"></i> Back to Projects
            </a>
            <a href="{{ route('supervisor.dashboard') }}" class="btn" style="background: #6b7280; color: white; text-decoration: none; padding: 8px 16px; border-radius: 5px;">
              <i class="fa-solid fa-chart-line"></i> Dashboard
            </a>
          @elseif(auth()->user()->role === 'student')
            <a href="{{ route('student.dashboard') }}" class="btn" style="background: #007bff; color: white; text-decoration: none; padding: 8px 16px; border-radius: 5px;">
              <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
          @endif
        @else
          <a href="{{ route('welcome') }}" class="btn" style="background: #6b7280; color: white; text-decoration: none; padding: 8px 16px; border-radius: 5px;">
            <i class="fa-solid fa-home"></i> Home
          </a>
        @endauth
      </div>
    </div>
  </main>
</body>
</html>
