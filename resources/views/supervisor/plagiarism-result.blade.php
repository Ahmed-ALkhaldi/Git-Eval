<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Plagiarism Result</title>
  <link rel="stylesheet" href="{{ asset('css/supervisor.css') }}" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <nav class="navbar">
    <div class="brand">
      <i class="fa-solid fa-gauge-high"></i>
      <span class="title">Supervisor Dashboard</span>
    </div>
    <div class="nav-actions">
      <a class="link" href="{{ route('supervisor.dashboard') }}">Dashboard</a>
      <a class="link" href="{{ route('supervisor.requests.pending') }}">Pending Requests</a>
      <a class="link" href="{{ route('supervisor.projects.accepted') }}">Accepted Projects</a>
      <form method="POST" action="{{ route('logout') }}" style="display:inline">
        @csrf
        <button class="btn danger" type="submit" title="Logout">Logout</button>
      </form>
    </div>
  </nav>

  <main class="container">
    <section class="page">
      <div class="header">
        <div class="title">
          <i class="fa-solid fa-copy"></i>
          <h1>Plagiarism Result</h1>
        </div>
        <span class="subtitle">Latest comparison details</span>
      </div>

      @if(session('error'))
        <div class="alert error">{{ session('error') }}</div>
      @endif
      @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
      @endif

      <div class="card" style="padding:16px; margin-bottom:16px;">
        @if(!empty($report->report_url))
          <p style="margin:0 0 8px">
            <i class="fa-regular fa-file-lines"></i>
            <strong>Full MOSS report:</strong>
            <a href="{{ $report->report_url }}" target="_blank" rel="noopener">Open</a>
          </p>
        @endif
        <p style="margin:0">
          <strong>Similarity:</strong> {{ round($report->similarity_percentage, 2) }}%
        </p>
      </div>

      <div class="card" style="padding:16px;">
        <h2 style="margin:0 0 12px; font-size:18px">Detailed matches</h2>
        
        <!-- Project Names Header -->
        <div style="margin-bottom: 16px; padding: 12px; background: rgba(33, 150, 243, 0.1); border-radius: 8px; border: 1px solid rgba(33, 150, 243, 0.2);">
          <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div style="text-align: center; flex: 1;">
              <strong style="color: #1976d2;">Project 1:</strong>
              <div style="font-weight: 600; color: #0d1b2a;">{{ $report->project1->title ?? 'Unknown Project' }}</div>
            </div>
            <div style="text-align: center; flex: 1;">
              <strong style="color: #1976d2;">Project 2:</strong>
              <div style="font-weight: 600; color: #0d1b2a;">{{ $report->project2->title ?? 'Unknown Project' }}</div>
            </div>
          </div>
        </div>

        @php($matches = is_string($report->matches ?? null) ? json_decode($report->matches, true) : ($report->matches ?? []))
        @if(is_array($matches) && count($matches))
          <div style="overflow:auto">
            <table class="table">
              <thead>
                <tr>
                  <th>{{ $report->project1->title ?? 'Project 1' }}</th>
                  <th>%</th>
                  <th>{{ $report->project2->title ?? 'Project 2' }}</th>
                  <th>%</th>
                  <th>Lines</th>
                </tr>
              </thead>
              <tbody>
                @foreach($matches as $m)
                  <tr>
                    <td>
                      @if(!empty($m['file1_link']))
                        <a href="{{ $m['file1_link'] }}" target="_blank" rel="noopener" title="{{ $m['file1'] ?? '' }}">
                          <i class="fa-solid fa-file-code"></i> View File
                        </a>
                      @else
                        <span class="muted">No link available</span>
                      @endif
                    </td>
                    <td><strong>{{ $m['p1'] ?? '' }}%</strong></td>
                    <td>
                      @if(!empty($m['file2_link']))
                        <a href="{{ $m['file2_link'] }}" target="_blank" rel="noopener" title="{{ $m['file2'] ?? '' }}">
                          <i class="fa-solid fa-file-code"></i> View Link
                        </a>
                      @else
                        <span class="muted">No link available</span>
                      @endif
                    </td>
                    <td><strong>{{ $m['p2'] ?? '' }}%</strong></td>
                    <td><strong>{{ $m['lines'] ?? '' }}</strong></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="muted" style="margin:0">No detailed matches were extracted. Try opening the full report above.</p>
        @endif
      </div>

      <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap">
        <a href="{{ route('supervisor.projects.accepted') }}" class="btn">
          <i class="fa-solid fa-arrow-left"></i> Back to Accepted Projects
        </a>
        <a href="{{ route('supervisor.projects.plagiarism', $report->project1_id) }}" class="btn" style="background: linear-gradient(90deg, #ff9800, #ffb74d);">
          <i class="fa-solid fa-refresh"></i> Run New Check
        </a>
      </div>
    </section>
  </main>
</body>
</html>