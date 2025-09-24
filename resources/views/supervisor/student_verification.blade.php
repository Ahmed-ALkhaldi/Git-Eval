<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Verification</title>
  <link rel="stylesheet" href="{{ asset('css/supervisor.css') }}" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <nav class="navbar">
    <div class="brand"><i class="fa-solid fa-user-check"></i><span class="title">Student Verification</span></div>
    <div class="nav-actions">
      <a class="link" href="dashboard.html">Dashboard</a>
      <a class="link" href="logout.html">Logout</a>
    </div>
  </nav>

  <main class="container">
    <section class="page">
      <div class="header">
        <div class="title"><i class="fa-solid fa-users"></i><h1>New Students</h1></div>
        <span class="subtitle">Verify or reject newly registered students</span>
      </div>

      <table class="student-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>University ID</th>
            <th>University</th>
            <th>Enrollment Certificate</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Younis Alagha</td>
            <td>120212152</td>
            <td>Islamic University of Gaza</td>
            <td><a href="uploads/certificate.pdf" target="_blank" class="btn small"><i class="fa-solid fa-file-pdf"></i> View</a></td>
            <td>
              <button class="btn success"><i class="fa-solid fa-check"></i> Verify</button>
              <button class="btn danger"><i class="fa-solid fa-xmark"></i> Reject</button>
            </td>
          </tr>
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
