<div class="container">
    <h3>📂 Accepted Projects</h3>

    @if($projects->isEmpty())
        <p>No accepted projects yet.</p>
    @else
        <table class="table table-bordered">
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
                @foreach($projects as $project)
                    <tr>
                        <td>{{ $project->title }}</td>
                        <td>{{ $project->student->name ?? '-' }}</td>
                        <td>{{ $project->description }}</td>
                        <td>
                            <a href="{{ $project->repository->github_url ?? '#' }}" target="_blank">
                                View Repo
                            </a>
                        </td>
                        <td>
                            <form action="{{ route('projects.analyze', $project->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">🔍 Code Analysis</button>
                            </form>
                            <form action="{{ route('projects.plagiarism.form', $project->id) }}" method="GET" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">🔎 Plagiarism</button>
                            </form>
                            <form action="{{ route('projects.evaluate', $project->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">📝 Evaluate</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>
    @endif
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-danger">Logout</button>
    </form>
</div>