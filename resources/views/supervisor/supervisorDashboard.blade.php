<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard</title>
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
      <a class="link" href="{{ route('supervisor.requests.pending') }}">
        <i class="fa-solid fa-hourglass-half"></i> Pending Requests
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
        <div class="title"><i class="fa-solid fa-chart-line"></i><h1>Dashboard</h1></div>
        <span class="subtitle">Overview of project statistics</span>
      </div>

      <div class="grid">
        <div class="card">
          <h3 class="card-title"><i class="fa-solid fa-hourglass-half"></i> Pending Requests</h3>
          <p class="card-text">View and manage student project requests.</p>
          <a href="{{ route('supervisor.requests.pending') }}" class="btn">Go to Requests</a>
        </div>

        <div class="card">
          <h3 class="card-title"><i class="fa-solid fa-circle-check"></i> Accepted Projects</h3>
          <p class="card-text">Track accepted projects and details.</p>
          <a href="{{ route('supervisor.projects.accepted') }}" class="btn">View Projects</a>
        </div>

        <div class="card">
          <h3 class="card-title"><i class="fa-solid fa-user-check"></i> Student Verification</h3>
          <p class="card-text">Review and verify newly registered students.</p>
          <a href="{{ route('supervisor.students.verify.index') }}" class="btn">Go to Verification</a>
        </div>

        <div class="card">
          <h3 class="card-title"><i class="fa-solid fa-user-edit"></i> Edit Supervisor Info</h3>
          <p class="card-text">Update your email and password securely.</p>
          <a href="{{ route('supervisor.profile.edit') }}" class="btn">Edit Info</a>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
