<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GitEval AI — إنشاء حساب</title>

  {{-- استدعاء ملفات CSS من public/css --}}
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">

  {{-- Bootstrap CSS --}}
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
    integrity="sha384-rbsA2VBkQhggwzxX7YhE7q6HgMHqYjgJv1nP9Wcv16O3Q9b6jzrWeNseyX2VINqbod"
    crossorigin="anonymous"
  />

  <style>
    .card { display: grid; grid-template-columns: 1fr 1fr; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,.08);}
    .panel-visual { background: var(--primary-700, #1f5eff); color:#fff; padding:32px; display:flex; flex-direction:column; justify-content:center; text-align:center; }
    .brand { display:flex; flex-direction:column; align-items:center; gap:12px; margin-bottom:8px;}
    .brand img { width:56px; height:56px; border-radius:12px;}
    .brand .title { font-weight:800; font-size:20px; letter-spacing:.3px;}
    .tag { opacity:.9; margin-bottom:16px; display:inline-block;}
    .visual-copy h2 { margin:0 0 8px;}
    .visual-copy .subtitle { opacity:.85;}
    .panel-form { padding:32px; background:#fff;}
    .label { font-weight:600; margin-top:12px; display:block;}
    .input { width:100%; border:1px solid var(--border,#e5e7eb); border-radius:10px; padding:10px 12px; font-size:14px;}
    .input:focus { outline:none; border-color:var(--primary-700,#1f5eff); box-shadow:0 0 0 3px rgba(31,94,255,.2);}
    .btn { display:inline-block; border-radius:10px; padding:12px 16px; text-align:center; font-weight:700; border:1px solid transparent; cursor:pointer;}
    .btn.primary { background:var(--primary-700,#1f5eff); color:#fff;}
    .btn.primary:hover { opacity:.9;}
    .muted { color:#6b7280;}
    .alert.error { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; padding:10px 12px; border-radius:10px; margin-top:10px;}
    @media (max-width:880px){ .card{grid-template-columns:1fr;} .panel-visual{display:none;} }
  </style>
</head>
<body>
  <main class="container">
    <section class="card" role="region" aria-labelledby="register-title">
      <aside class="panel-visual">
        <div class="brand">
          <img src="{{ asset('css/assets/logo-final.png') }}" alt="GitEval AI Logo">
          <div class="title">GitEval AI</div>
        </div>
        <span class="tag">For Supervisors & Students</span>
        <div class="visual-copy">
          <h2>ابدأ رحلتك مع GitEval AI</h2>
          <p class="subtitle">سجّل لتتبع مساهمات الفريق وجودة الشيفرة وكشف التشابه بسهولة.</p>
        </div>
      </aside>

      <div class="panel-form">
        <h2 id="register-title">إنشاء حساب</h2>
        <p class="subtitle">أكمل البيانات التالية لبدء الاستخدام.</p>

        @if ($errors->any())
          <div class="alert error">
            <ul>
              @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form class="form" method="POST" action="{{ route('register.post') }}" enctype="multipart/form-data">
          @csrf

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="label" for="first_name">First Name</label>
              <input class="input form-control" id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="label" for="last_name">Last Name</label>
              <input class="input form-control" id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" required>
            </div>

            <div class="col-md-6 mb-3">
              <label class="label" for="email">البريد الإلكتروني</label>
              <input class="input form-control" id="email" name="email" type="email" value="{{ old('email') }}" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="label" for="university_name">اسم الجامعة</label>
              <select class="input form-control" id="university_name" name="university_name" required>
                <option value="">-- اختر الجامعة --</option>
                <option value="IUG" {{ old('university_name')=='IUG' ? 'selected' : '' }}>IUG</option>
                <option value="AUG" {{ old('university_name')=='AUG' ? 'selected' : '' }}>AUG</option>
                <option value="UCAS" {{ old('university_name')=='UCAS' ? 'selected' : '' }}>UCAS</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="label" for="university_num">الرقم الجامعي</label>
              <input class="input form-control" id="university_num" name="university_num" type="text" value="{{ old('university_num') }}" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="label" for="enrollment_certificate">شهادة القيد</label>
              <input class="input form-control" id="enrollment_certificate" name="enrollment_certificate" type="file" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>

            <div class="col-md-6 mb-3">
              <label class="label" for="password">كلمة المرور</label>
              <input class="input form-control" id="password" name="password" type="password" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="label" for="password_confirmation">تأكيد كلمة المرور</label>
              <input class="input form-control" id="password_confirmation" name="password_confirmation" type="password" required>
            </div>
          </div>

          <button type="submit" class="btn primary w-100 mt-3">تسجيل</button>
        </form>

        <p class="mt-4 text-center">
          لديك حساب مسبقًا؟ <a class="text-blue-600" href="{{ route('login') }}">سجّل دخول</a>
        </p>
      </div>
    </section>
  </main>

  {{-- Bootstrap JS --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
