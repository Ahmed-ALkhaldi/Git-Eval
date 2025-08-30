<div class="container">
    <h3>🔍 Plagiarism Check</h3>

    {{-- ✅ رسائل التنبيه --}}
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <p>📌 المشروع الأساسي: <strong>{{ $project1->title }}</strong></p>

    {{-- ✅ الفورم يرسل إلى Route الصحيح --}}
    <form action="{{ route('projects.plagiarism.check') }}" method="POST">
        @csrf
        <input type="hidden" name="project1_id" value="{{ $project1->id }}">

        <div class="form-group">
            <label for="project2_id">🔸 اختر المشروع الثاني للمقارنة:</label>
            <select name="project2_id" class="form-control" required>
                @foreach($otherProjects as $proj)
                    <option value="{{ $proj->id }}">{{ $proj->title }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary mt-3">✅ Start Comparison</button>
    </form>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-danger">Logout</button>
    </form>
</div>
