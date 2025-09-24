
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
    <div class="brand"><i class="fa-solid fa-gauge-high"></i><span class="title">Supervisor Dashboard</span></div>
    <div class="nav-actions">
      <a class="link" href="pending_requests.html">Pending Requests</a>
      <a class="link" href="accepted_projects.html">Accepted Projects</a>
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
  </div>
  <div class="card">
    <h3 class="card-title"><i class="fa-solid fa-circle-check"></i> Accepted Projects</h3>
    <p class="card-text">Track accepted projects and details.</p>
  </div>
  <!-- زر جديد -->
  <div class="card">
    <h3 class="card-title"><i class="fa-solid fa-user-check"></i> Student Verification</h3>
    <p class="card-text">Review and verify newly registered students.</p>
<a href="student_verification.html" class="btn">Go to Verification</a>
  </div>
</div>
    </section>
  
</main>
                <a href="logout.html" class="btn logout-btn">log out</a>

</body>
</html>
