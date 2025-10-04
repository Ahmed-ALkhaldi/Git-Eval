<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Evaluation - {{ $project->title }}</title>
  <link rel="stylesheet" href="{{ asset('css/supervisor.css') }}" />
</head>
<body>
  <nav class="navbar">
    <div class="brand">
      <i class="fa-solid fa-gauge-high"></i>
      <span class="title">Supervisor Dashboard</span>
    </div>
    <div class="nav-actions">
      <a class="link" href="{{ route('supervisor.dashboard') }}">Dashboard</a>
      <a class="link" href="{{ route('supervisor.projects.accepted') }}">Accepted Projects</a>
    </div>
  </nav>

  <main class="container">
    <section class="page">
      <div class="header">
        <div class="title">
          <h1>Evaluation Results</h1>
        </div>
        <span class="subtitle">Project: {{ $project->title }}</span>
      </div>

      @php
        // قيم جاهزة من التقييم
        $repoName     = $project->repository->name ?? '—';
        $periodLabel  = 'Last 30 days';
        $sourceLabel  = env('GITHUB_TOKEN') ? 'GitHub API (Enhanced)' : 'Public GitHub API';

        // حساب المجاميع من تقييمات الطلاب
        $totalCommits = $project->studentEvaluations->sum('commits');
        $totalPrsOpened = $project->studentEvaluations->sum('prs_opened');
        $totalPrsMerged = $project->studentEvaluations->sum('prs_merged');
        $totalReviews = $project->studentEvaluations->sum('reviews');
        $totalIssues = $project->studentEvaluations->sum('issues');

        // متوسط الدرجة
        $avgScore = $project->studentEvaluations->avg('score') ?? 0;
        $score = round($avgScore, 1);
        
        $weights = ['commits'=>0.6,'prs'=>0.25,'issues'=>0.1,'reviews'=>0.05];

        // حالة لونية للسكور
        $scoreClass = 'neutral';
        $scoreNote  = 'Needs review';
        if ($score >= 80) { $scoreClass = 'good';   $scoreNote = 'Excellent'; }
        elseif ($score >= 60) { $scoreClass = 'ok'; $scoreNote = 'Good'; }
        elseif ($score <= 30) { $scoreClass = 'low'; $scoreNote = 'Low activity'; }

        // تنبيه إن بعض الطلاب بلا usernames
        $missingUsernames = $project->students->filter(fn($s) => empty($s->github_username))->count();
        $notePublicOnly   = !env('GITHUB_TOKEN');
      @endphp

      <div class="card summary">
        <div class="summary__header">
          <div>
            <div class="muted">Project repo</div>
            <div class="repo">{{ $repoName }}</div>
          </div>

          <div class="score {{ $scoreClass }}" aria-label="Overall score">
            <div class="score__value">{{ $score }}</div>
            <div class="score__note">{{ $scoreNote }}</div>
          </div>
        </div>

        <div class="summary__row">
          <div class="kpis">
            <div class="kpi"><div class="kpi__label" title="Saved code changes">Commits</div><div class="kpi__value">{{ $totalCommits }}</div></div>
            <div class="kpi"><div class="kpi__label" title="Pull Requests opened by students">PRs opened</div><div class="kpi__value">{{ $totalPrsOpened }}</div></div>
            <div class="kpi"><div class="kpi__label" title="Pull Requests merged (accepted)">PRs merged</div><div class="kpi__value">{{ $totalPrsMerged }}</div></div>
            <div class="kpi"><div class="kpi__label" title="Code review comments written">Reviews</div><div class="kpi__value">{{ $totalReviews }}</div></div>
            <div class="kpi"><div class="kpi__label" title="Issues opened (tasks/bugs)">Issues</div><div class="kpi__value">{{ $totalIssues }}</div></div>
          </div>
        </div>

        <div class="summary__row">
          <div class="line" aria-label="Activity snapshot">
            {{ $totalCommits }} Commits · {{ $totalPrsOpened }} PRs opened · {{ $totalPrsMerged }} merged · {{ $totalReviews }} reviews · {{ $totalIssues }} issues
          </div>
        </div>

        <div class="summary__row">
          <div class="chips">
            <span class="chip" title="Weight of commits in the score">Commits {{ (int)($weights['commits']*100) }}%</span>
            <span class="chip" title="Weight of PRs in the score">PRs {{ (int)($weights['prs']*100) }}%</span>
            <span class="chip" title="Weight of issues in the score">Issues {{ (int)($weights['issues']*100) }}%</span>
            <span class="chip" title="Weight of reviews in the score">Reviews {{ (int)($weights['reviews']*100) }}%</span>
            @if($notePublicOnly)
              <span class="chip warn" title="Public data only (no token). Private activity may be missing.">Public data only</span>
            @endif
            @if($missingUsernames > 0)
              <span class="chip warn" title="Some students have no GitHub username set">Missing usernames: {{ $missingUsernames }}</span>
            @endif
          </div>
        </div>

        <div class="summary__footer">
          <span>Period: <b>{{ $periodLabel }}</b></span>
          <span>Source: <b>{{ $sourceLabel }}</b></span>
          @if($project->evaluation)
            <span>Computed: <b>{{ optional($project->evaluation->computed_at)->format('Y-m-d H:i') }}</b></span>
          @endif
        </div>
      </div>

      <!-- زر إعادة التقييم -->
      <div style="margin-bottom:16px; text-align:right;">
        <form method="POST" action="{{ route('supervisor.projects.evaluate', $project->id) }}" style="display:inline;">
          @csrf
          <button type="submit" class="btn success">
            <i class="fa-solid fa-rotate"></i>
            {{ $project->evaluation ? 'Re-run Evaluation' : 'Run Evaluation' }}
          </button>
        </form>
      </div>

      <!-- Legend/Glossary -->
      <details class="legend">
        <summary>How to read this / كيف تقرأ النتائج؟</summary>
        <ul>
          <li><b>Commit:</b> حفظ تغيّرات في المستودع (تقدم فعلي في الكود).</li>
          <li><b>Pull Request (PR):</b> طلب دمج تغيّرات إلى الفرع الرئيسي بعد المراجعة.</li>
          <li><b>PRs Opened:</b> عدد الـ PRs التي فتحها الطالب.</li>
          <li><b>PRs Merged:</b> عدد الـ PRs التي تم قبولها ودمجها.</li>
          <li><b>Code Reviews:</b> مراجعات كتبها الطالب على PRs (تعليقات مراجعة).</li>
          <li><b>Issues:</b> بلاغات مهام/أخطاء فتحها الطالب.</li>
          <li><b>Score:</b> درجة مركبة (0–100) محسوبة باستخدام الأوزان: Commits 60% · PRs 25% · Issues 10% · Reviews 5%</li>
        </ul>
      </details>

      <!-- Period and Source Info -->
      <div class="info-bar">
        <span><i class="fa-solid fa-calendar"></i> Period: Last 30 days</span>
        <span><i class="fa-solid fa-code-branch"></i> Source: Public GitHub API</span>
        @if(env('GITHUB_TOKEN'))
          <span><i class="fa-solid fa-key"></i> Enhanced with GitHub Token</span>
        @else
          <span><i class="fa-solid fa-info-circle"></i> Public data only (no token)</span>
        @endif
      </div>

      <div class="card" style="padding:16px;">
        <h2 style="margin-top:0;">
          Per-student metrics
          @include('components.help', [
            'text' => 'مقاييس GitHub لكل طالب في المشروع'
          ])
        </h2>
        
        @php
          $missingUsers = $project->students->filter(fn($s) => empty($s->github_username))->count();
        @endphp
        @if($missingUsers > 0)
          <div class="alert error" style="margin-bottom:12px;">
            <i class="fa-solid fa-exclamation-triangle"></i>
            {{ $missingUsers }} student(s) missing GitHub usernames. Their metrics will be zeros.
          </div>
        @endif
        @if(($project->studentEvaluations ?? collect())->count())
          <table class="table">
            <thead>
              <tr>
                <th>Student</th>
                <th>
                  <abbr title="Commits = حفظ تغييرات في المستودع">Commits</abbr>
                  @include('components.help', [
                    'text' => 'عدد التغييرات المحفوظة في المستودع'
                  ])
                </th>
                <th>
                  <abbr title="Pull Requests Opened = طلبات الدمج المفتوحة">PRs Opened</abbr>
                  @include('components.help', [
                    'text' => 'عدد طلبات الدمج التي فتحها الطالب'
                  ])
                </th>
                <th>
                  <abbr title="Pull Requests Merged = طلبات الدمج المقبولة">PRs Merged</abbr>
                  @include('components.help', [
                    'text' => 'عدد طلبات الدمج التي تم قبولها ودمجها'
                  ])
                </th>
                <th>
                  <abbr title="Code Reviews = مراجعات الكود">Reviews</abbr>
                  @include('components.help', [
                    'text' => 'عدد مراجعات الكود التي كتبها الطالب'
                  ])
                </th>
                <th>
                  <abbr title="Overall Score = الدرجة الإجمالية">Score</abbr>
                  @include('components.help', [
                    'text' => 'الدرجة الإجمالية المحسوبة (0-100)'
                  ])
                </th>
              </tr>
            </thead>
            <tbody>
              @foreach($project->studentEvaluations as $se)
                <tr>
                  <td>
                    {{ optional($se->student->user)->name }}
                    @if(optional($se->student)->github_username)
                      <span class="muted">({{ $se->student->github_username }})</span>
                    @else
                      <span class="badge warn">No GitHub username</span>
                    @endif
                  </td>
                  <td>
                    @php
                      $commitsBadge = $se->commits >= 20 ? 'badge-good' : ($se->commits > 0 ? 'badge-okay' : 'badge-none');
                    @endphp
                    <span class="badge {{ $commitsBadge }}">{{ $se->commits }}</span>
                  </td>
                  <td>
                    @php
                      $prsOpenedBadge = $se->prs_opened >= 3 ? 'badge-good' : ($se->prs_opened > 0 ? 'badge-okay' : 'badge-none');
                    @endphp
                    <span class="badge {{ $prsOpenedBadge }}">{{ $se->prs_opened }}</span>
                  </td>
                  <td>
                    @php
                      $prsMergedBadge = $se->prs_merged >= 3 ? 'badge-good' : ($se->prs_merged > 0 ? 'badge-okay' : 'badge-none');
                    @endphp
                    <span class="badge {{ $prsMergedBadge }}">{{ $se->prs_merged }}</span>
                  </td>
                  <td>
                    @php
                      $reviewsBadge = $se->reviews >= 5 ? 'badge-good' : ($se->reviews > 0 ? 'badge-okay' : 'badge-none');
                    @endphp
                    <span class="badge {{ $reviewsBadge }}">{{ $se->reviews }}</span>
                  </td>
                  <td>
                    @php
                      $scoreBadge = $se->score >= 80 ? 'badge-excellent' : ($se->score >= 60 ? 'badge-good' : ($se->score >= 40 ? 'badge-okay' : 'badge-poor'));
                    @endphp
                    <span class="badge {{ $scoreBadge }}">{{ round($se->score, 1) }}</span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <p class="muted">No per-student data yet.</p>
        @endif
      </div>

      <div style="margin-top:16px; display:flex; gap:8px; flex-wrap:wrap;">
        <a class="btn" href="{{ route('supervisor.projects.accepted') }}">Back to Accepted</a>
        <a class="btn" href="{{ route('projects.report', $project) }}">Final Report</a>
      </div>
    </section>
  </main>

  <!-- Enhanced Styles -->
  <style>
    /* Summary Card Styles */
    .card.summary {
      background: #eaf5ff;
      border: 1px solid #dbeafe;
      border-radius: 14px;
      padding: 16px;
      margin-bottom: 16px;
    }
    .summary__header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 8px;
    }
    .repo {
      font-weight: 700;
      font-size: 16px;
    }
    .muted {
      color: #6b7280;
      font-size: 12px;
    }
    .kpis {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 10px;
    }
    .kpi {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 10px;
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    .kpi__label {
      font-size: 12px;
      color: #6b7280;
    }
    .kpi__value {
      font-size: 20px;
      font-weight: 700;
    }
    .line {
      color: #111827;
      font-size: 14px;
    }
    .chips {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    .chip {
      background: #f3f4f6;
      color: #111827;
      border: 1px solid #e5e7eb;
      border-radius: 999px;
      padding: 3px 10px;
      font-size: 12px;
    }
    .chip.warn {
      background: #FEF3C7;
      color: #92400E;
      border-color: #FDE68A;
    }
    .summary__footer {
      margin-top: 10px;
      display: flex;
      gap: 14px;
      color: #374151;
      font-size: 12px;
    }
    .score {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
    }
    .score__value {
      font-size: 36px;
      font-weight: 800;
      line-height: 1;
    }
    .score__note {
      font-size: 12px;
      margin-top: 2px;
    }
    .score.good .score__value {
      color: #166534;
    }
    .score.ok .score__value {
      color: #065f46;
    }
    .score.neutral .score__value {
      color: #1f2937;
    }
    .score.low .score__value {
      color: #991b1b;
    }

    /* Legend/Glossary Styles */
    .legend {
      margin: 8px 0 16px 0;
      padding: 12px;
      background: #f8fafc;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
    }
    .legend summary {
      cursor: pointer;
      color: #111827;
      font-weight: 600;
      font-size: 14px;
    }
    .legend ul {
      margin: 8px 0 0 16px;
      padding: 0;
    }
    .legend li {
      margin: 4px 0;
      font-size: 13px;
      line-height: 1.4;
    }

    /* Info Bar */
    .info-bar {
      display: flex;
      gap: 16px;
      margin: 8px 0 16px 0;
      padding: 8px 12px;
      background: #f0f9ff;
      border: 1px solid #bae6fd;
      border-radius: 6px;
      font-size: 13px;
      color: #0369a1;
      flex-wrap: wrap;
    }
    .info-bar span {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    /* Badge Styles */
    .badge {
      padding: 4px 8px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 600;
      display: inline-block;
    }
    .badge-excellent {
      background: #dcfce7;
      color: #166534;
      border: 1px solid #bbf7d0;
    }
    .badge-good {
      background: #dbeafe;
      color: #1e40af;
      border: 1px solid #bfdbfe;
    }
    .badge-okay {
      background: #fef3c7;
      color: #d97706;
      border: 1px solid #fed7aa;
    }
    .badge-poor {
      background: #fef2f2;
      color: #dc2626;
      border: 1px solid #fecaca;
    }
    .badge-none {
      background: #f3f4f6;
      color: #6b7280;
      border: 1px solid #d1d5db;
    }
    .badge.warn {
      background: #fef3c7;
      color: #92400e;
      padding: 2px 6px;
      border-radius: 6px;
      font-size: 11px;
    }

    /* Table Enhancements */
    .table th {
      background: #f9fafb;
      font-weight: 600;
      font-size: 13px;
      padding: 12px 8px;
      border-bottom: 2px solid #e5e7eb;
      text-align: left;
    }
    .table td {
      padding: 10px 8px;
      font-size: 13px;
      border-bottom: 1px solid #f3f4f6;
    }
    .table td:first-child {
      font-weight: 500;
    }

    /* Alert enhancements */
    .alert.error {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .info-bar {
        flex-direction: column;
        gap: 8px;
      }
      .legend {
        margin: 8px 0;
      }
      .table {
        font-size: 12px;
      }
      .table th,
      .table td {
        padding: 8px 4px;
      }
    }
  </style>
</body>
</html> 