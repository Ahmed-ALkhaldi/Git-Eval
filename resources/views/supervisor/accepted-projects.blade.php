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
                            <a href="{{ route('projects.analyze', $project->id) }}" class="btn btn-sm btn-outline-primary">🔍 Code Analysis</a>
                            <a href="{{ route('projects.plagiarism', $project->id) }}" class="btn btn-sm btn-outline-warning">🔎 Plagiarism</a>
                            <a href="{{ route('projects.evaluate', $project->id) }}" class="btn btn-sm btn-outline-success">📝 Evaluate</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>
    @endif
</div>