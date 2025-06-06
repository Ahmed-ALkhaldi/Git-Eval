<div class="container mt-4">
    <h3>Add New Project</h3>
    <form method="POST" action="{{ route('projects.store') }}">
        @csrf
        <div class="mb-3">
            <label for="title" class="form-label">Project Title</label>
            <input type="text" name="title" id="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Project Description</label>
            <textarea name="description" id="description" class="form-control" rows="4"></textarea>
        </div>
        <div class="mb-3">
            <label for="repository_url" class="form-label">GitHub Repository URL</label>
            <input type="url" name="repository_url" id="repository_url" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Create Project</button>
    </form>
</div>