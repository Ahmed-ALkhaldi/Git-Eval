<h3>Pending Requests</h3>
@foreach ($requests as $request)
    <div>
        Request from: {{ $request->student->name }} <br>
        <form method="POST" action="{{ route('supervisor.request.respond', ['id' => $request->id, 'action' => 'accept']) }}">
            @csrf
            <button class="btn btn-success btn-sm">Accept</button>
        </form>
        <form method="POST" action="{{ route('supervisor.request.respond', ['id' => $request->id, 'action' => 'reject']) }}">
            @csrf
            <button class="btn btn-danger btn-sm">Reject</button>
        </form>
    </div>
@endforeach
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="btn btn-danger">Logout</button>
</form> 
