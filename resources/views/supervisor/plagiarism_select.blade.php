<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Plagiarism Check</title>
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
          <h1>Plagiarism Check</h1>
        </div>
        <span class="subtitle">Compare the selected project against another project</span>
      </div>

      @if(session('error'))
        <div class="alert error">{{ session('error') }}</div>
      @endif
      @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
      @endif
      @if ($errors->any())
        <div class="alert error">
          <ul style="margin:0; padding-inline-start:18px;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="card" style="padding:16px;">
        <p style="margin:0 0 12px">📌 Base Project: <strong>{{ $project1->title }}</strong></p>

        <form action="{{ route('supervisor.plagiarism.check') }}" method="POST" class="form">
          @csrf
          <input type="hidden" name="project1_id" value="{{ $project1->id }}">

          <div class="form-row">
            <label for="project2_id" class="label">Compare With</label>
            <select name="project2_id" id="project2_id" class="input" required>
              @foreach($otherProjects as $proj)
                <option value="{{ $proj->id }}">{{ $proj->title }}</option>
              @endforeach
            </select>
          </div>

          <div class="actions" style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap">
            <button type="submit" class="btn warning">
              <i class="fa-solid fa-play"></i> Start Comparison
            </button>
            <a href="{{ route('supervisor.projects.accepted') }}" class="btn">
              <i class="fa-solid fa-arrow-left"></i> Back to Accepted Projects
            </a>
          </div>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
