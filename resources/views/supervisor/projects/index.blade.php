<h3>Projects Waiting Approval</h3>
@foreach ($projects as $project)
    <div class="card mb-3">
        <div class="card-body">
            <h5>{{ $project->title }}</h5>
            <p>{{ $project->description }}</p>
            <form method="POST" action="{{ route('supervisor.approve', $project->id) }}">
                @csrf
                <button class="btn btn-success">Approve</button>
            </form>
        </div>
    </div>
@endforeach
