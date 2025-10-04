<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Verification</title>

  {{-- اختر واحد فقط حسب مكان CSS عندك --}}
  {{-- إن كان CSS الأصلي اسمه style.css في جذر public --}}
  {{-- <link rel="stylesheet" href="{{ asset('style.css') }}" /> --}}

  {{-- أو إن كان عندك supervisor.css هو الذي يحتوي تنسيقات .student-table انسخ إليه القواعد أو استخدمه هنا --}}
  <link rel="stylesheet" href="{{ asset('css/supervisor.css') }}" />

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <nav class="navbar">
    <div class="brand">
      <i class="fa-solid fa-user-check"></i>
      <span class="title">Student Verification</span>
    </div>
    <div class="nav-actions">
      <a class="link" href="{{ route('supervisor.dashboard') }}">Dashboard</a>
      {{-- زر لوج آوت بنفس مظهر الرابط لكن عبر POST --}}
      <form method="POST" action="{{ route('logout') }}" style="display:inline-block; margin-left:.75rem;">
        @csrf
        <button type="submit" class="link" style="background:none;border:none;padding:0;cursor:pointer;">
          Logout
        </button>
      </form>
    </div>
  </nav>

  <main class="container">
    <section class="page">
      <div class="header">
        <div class="title"><i class="fa-solid fa-users"></i><h1>New Students</h1></div>
        <span class="subtitle">Verify or reject newly registered students</span>
      </div>

      @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="alert error">{{ session('error') }}</div>
      @endif
      @if($errors->any())
        <div class="alert error">{{ $errors->first() }}</div>
      @endif

      @if(($students ?? collect())->count() === 0)
        <p class="muted">No pending students for verification.</p>
      @else
        <table class="student-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>University ID</th>
              <th>University</th>
              <th>Enrollment Certificate</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          @foreach($students as $s)
            @php
              $u = optional($s)->user;
              $fullName = $u->name ?? trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?? '—';
              $uniId = $s->university_num ?? '—';
              $uniName = $s->university_name ?? '—';
              $cert = $s->enrollment_certificate_path ?? null;
            @endphp
            <tr>
              <td>{{ $fullName }}</td>
              <td>{{ $uniId }}</td>
              <td>{{ $uniName }}</td>
              <td>
                @if($cert)
                  <a href="{{ route('files.enrollment-certificate.view', $s->id) }}" target="_blank" class="btn small" rel="noopener">
                    <i class="fa-solid fa-file-pdf"></i> View
                  </a>
                  <a href="{{ route('files.enrollment-certificate.download', $s->id) }}" class="btn small" style="margin-left: 5px;">
                    <i class="fa-solid fa-download"></i> Download
                  </a>
                @else
                  <span class="muted">No certificate</span>
                @endif
              </td>
              <td>
                <form method="POST" action="{{ route('supervisor.students.verify.approve', $s) }}" style="display:inline-block;">
                  @csrf @method('PATCH')
                  <button class="btn success" type="submit">
                    <i class="fa-solid fa-check"></i> Verify
                  </button>
                </form>

                <form method="POST" action="{{ route('supervisor.students.verify.reject', $s) }}" style="display:inline-block; margin-inline-start:.5rem;">
                  @csrf @method('PATCH')
                  <button class="btn danger" type="submit">
                    <i class="fa-solid fa-xmark"></i> Reject
                  </button>
                </form>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>

        {{-- فعّل paginate() في الكنترولر لتظهر --}}
        @if(method_exists($students, 'links'))
          <div class="pagination">
            {{ $students->links() }}
          </div>
        @endif
      @endif
    </section>
  </main>
</body>
</html>
