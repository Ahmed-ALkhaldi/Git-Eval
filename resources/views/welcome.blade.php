<!DOCTYPE html>
<html lang="ar" dir="ltr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GitEval</title>

    {{-- CSS من public/css --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    {{-- Bootstrap CSS --}}
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
      integrity="sha384-rbsA2VBkQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65"
      crossorigin="anonymous"
    />

    <style>
      .logo { width: 50px; }
      .logo img { width: inherit; }
      .navbar { background-color: var(--primary-700); padding: 15px; }
      .navbar-brand, .contact, .navbar-brand:hover, .contact:hover { color: #fff; }
      .login-btn, .register-btn, .login-btn:hover { background-color: var(--light); color: var(--primary); }
      .register-btn:hover { background-color: var(--success); color: var(--light); }
      .navbar-collapse { flex-grow: 0; }
      .accordion-item { border-left: none; border-right: none; }
      .accordion-button::after { display: none; }
      .btn-ghost { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.4); }
      .btn-ghost:hover { background: rgba(255,255,255,.1); }
    </style>
  </head>
  <body>
    {{-- شريط التنقل --}}
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
      <div class="container">
        <div class="logo d-flex align-items-center gap-2">
          <img class="rounded-pill" src="{{ asset('css/assets/logo-final.png') }}" alt="GitEval Logo" />
          <a class="navbar-brand" href="{{ url('/') }}">GitEval</a>
        </div>

        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarSupportedContent"
          aria-controls="navbarSupportedContent"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0 d-flex align-items-center gap-3">
            {{-- لو عندك روابط عامة ضعها هنا --}}
          </ul>

          {{-- أزرار بحسب حالة المستخدم --}}
          <div class="d-flex align-items-center gap-2">
            @auth
              @php $role = auth()->user()->role ?? 'student'; @endphp

              @if ($role === 'student')
                <a class="btn btn-light px-4" href="{{ route('student.dashboard') }}">Student Dashboard</a>
              @elseif ($role === 'supervisor')
                {{-- عدّل إلى route('supervisor.dashboard') إذا كان هذا اسم الراوت عندك --}}
                <a class="btn btn-light px-4" href="{{ route('supervisor.requests') }}">Supervisor Dashboard</a>
              @elseif ($role === 'admin')
                <a class="btn btn-light px-4" href="{{ route('admin.panel') }}">Admin Panel</a>
              @else
                {{-- دور غير معروف --}}
                <a class="btn btn-light px-4" href="{{ route('welcome') }}">Home</a>
              @endif

              <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-ghost px-4">Logout</button>
              </form>
            @else
              <a class="nav-link login-btn rounded px-4" href="{{ route('login') }}">Login</a>
              <a class="nav-link register-btn rounded px-4" href="{{ route('register') }}">Register</a>
            @endauth
          </div>
        </div>
      </div>
    </nav>

    {{-- المحتوى الرئيسي --}}
    <section>
      <div class="container">
        <div class="text-center m-5">
          <h1>Welcome to GitEval</h1>
          <p>
            It is an integrated tool for project evaluation, fraud and theft detection, and team tasks.
          </p>
          @auth
            @php $role = auth()->user()->role ?? 'student'; @endphp
            <div class="mt-3 d-flex justify-content-center gap-2">
              @if ($role === 'student')
                <a class="btn btn-primary" href="{{ route('student.dashboard') }}">Go to Student Dashboard</a>
              @elseif ($role === 'supervisor')
                <a class="btn btn-primary" href="{{ route('supervisor.dashboard') }}">Go to Supervisor Dashboard</a>
              @elseif ($role === 'admin')
                <a class="btn btn-primary" href="{{ route('admin.panel') }}">Go to Admin Panel</a>
              @endif
            </div>
          @else
            <div class="mt-3 d-flex justify-content-center gap-2">
              <a class="btn btn-primary" href="{{ route('login') }}">Login</a>
              <a class="btn btn-outline-primary" href="{{ route('register') }}">Register</a>
            </div>
          @endauth
        </div>

        <div class="accordion w-75 m-auto" id="Faqs">
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                What is GitEval ?!
              </button>
            </h2>
            <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#Faqs">
              <div class="accordion-body">
                <strong>This is the first item's accordion body.</strong>
                Lorem ipsum dolor sit amet consectetur adipisicing elit...
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                How can I request a new browser ?!
              </button>
            </h2>
            <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#Faqs">
              <div class="accordion-body">
                <strong>This is the second item's accordion body.</strong>
                Lorem ipsum dolor sit amet consectetur adipisicing elit...
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                Is there a mobile app ?!
              </button>
            </h2>
            <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#Faqs">
              <div class="accordion-body">
                <strong>This is the third item's accordion body.</strong>
                Lorem ipsum dolor sit amet consectetur adipisicing elit...
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                What about other Chromium browsers ?!
              </button>
            </h2>
            <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#Faqs">
              <div class="accordion-body">
                <strong>This is the fourth item's accordion body.</strong>
                Lorem ipsum dolor sit amet consectetur adipisicing elit...
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
