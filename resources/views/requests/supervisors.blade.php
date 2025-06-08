<h3>Available Supervisors</h3>
@foreach ($supervisors as $supervisor)
    <div>
        {{ $supervisor->name }} ({{ $supervisor->email }})
        <form method="POST" action="{{ route('supervisors.request', $supervisor->id) }}">
            @csrf
            <button class="btn btn-primary btn-sm">Send Request</button>
        </form>
    </div>
@endforeach
