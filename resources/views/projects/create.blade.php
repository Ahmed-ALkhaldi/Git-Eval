<div class="container mt-4">
    <h3>Add New Project</h3>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('projects.store') }}">
        @csrf

        <div class="mb-3">
            <label for="title" class="form-label">Project Title</label>
            <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Project Description</label>
            <textarea name="description" id="description" class="form-control" rows="4">{{ old('description') }}</textarea>
        </div>

        <div class="mb-3">
            <label for="github_url" class="form-label">GitHub Repository URL</label>
            <input type="url" name="github_url" id="github_url" class="form-control" value="{{ old('github_url') }}" required>
        </div>

        <div class="mb-3">
            <label for="students" class="form-label">Add Team Members (Students)</label>
            <select name="students[]" id="students" class="form-control" multiple>
                @foreach($students as $student)
                    @if($student->id !== Auth::id())
                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->email }})</option>
                    @endif
                @endforeach
            </select>
            <small class="text-muted">Hold CTRL (or ⌘ on Mac) to select multiple students.</small>
        </div>

        <button type="submit" class="btn btn-success">Create Project</button>
    </form>
</div>
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="btn btn-danger">Logout</button>
</form>

