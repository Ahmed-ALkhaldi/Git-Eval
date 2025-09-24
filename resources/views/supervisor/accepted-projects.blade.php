<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Accepted Projects</title>
  <link rel="stylesheet" href="{{ asset('css/supervisor.css') }}" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <nav class="navbar">
    <div class="brand"><i class="fa-solid fa-gauge-high"></i><span class="title">Supervisor Dashboard</span></div>
    <div class="nav-actions">
      <a class="link" href="dashboard.html">Dashboard</a>
      <a class="link" href="pending_requests.html">Pending Requests</a>
    </div>
  </nav>
  <main class="container">
    <section class="page">
      <div class="header">
        <div class="title"><i class="fa-solid fa-circle-check"></i><h1>Accepted Projects</h1></div>
        <span class="subtitle">List of all approved projects</span>
      </div>
      <table class="table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Student</th>
            <th>Description</th>
            <th>GitHub URL</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Review Book</td>
            <td>Ali</td>
            <td>AI-powered review system</td>
            <td><a href="#">View Repo</a></td>
            <td class="actions">
              <button class="btn info"><i class="fa-solid fa-magnifying-glass"></i> Code Analysis</button>
              <button class="btn warning"><i class="fa-solid fa-copy"></i> Plagiarism</button>
              <button class="btn success"><i class="fa-solid fa-star"></i> Evaluate</button>
            </td>
          </tr>
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
