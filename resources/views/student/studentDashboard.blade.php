<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3>Welcome, {{ Auth::user()->name }}</h3>
            </div>
            <div class="card-body">
                <p><strong>Email:</strong> {{ Auth::user()->email }}</p>
                <p><strong>Role:</strong> {{ Auth::user()->role }}</p>

                <hr>

                <h5>Your Project Info</h5>
                <p>Here you can view and manage your graduation project, commits, and evaluations.</p>

                <a href="#" class="btn btn-success">View My Project</a>
                <a href="#" class="btn btn-outline-primary">My Commits</a>
                <a href="#" class="btn btn-outline-secondary">Evaluation Report</a>
                <a href="{{ route('projects.create') }}" class="btn btn-primary">+ Add New Project</a>
            </div>
        </div>

        <div class="text-center mt-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-danger">Logout</button>
            </form>
        </div>
    </div>
</body>
</html>
