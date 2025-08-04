<h1>Hello Supervisor</h1>
<a href="{{ route('supervisor.requests') }}" class="btn btn-warning">📥 View Supervision Requests</a> <br> <br>
<a href="{{ route('supervisor.accepted-projects') }}" class="btn btn-primary"> View Accepted Projects </a>
<br> <br>
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="btn btn-danger">Logout</button>
</form>