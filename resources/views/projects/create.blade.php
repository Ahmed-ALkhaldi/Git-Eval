<div class="container mt-4">
    <h3>Add New Project</h3>

    {{-- عرض الأخطاء إن وُجدت --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- عرض رسالة النجاح إن وُجدت --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
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
            <label for="repository_url" class="form-label">GitHub Repository URL</label>
            <input type="url" name="github_url" id="github_url" class="form-control" value="{{ old('repository_url') }}" required>
        </div>

        <button type="submit" class="btn btn-success">Create Project</button>
    </form>
</div>
