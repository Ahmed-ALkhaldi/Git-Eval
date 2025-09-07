<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>GitEval AI — تسجيل الدخول</title>

  {{-- CSS من public/css --}}
  <link rel="stylesheet" href="{{ asset('css/Global.css') }}">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">

  <style>
    /* تحسينات طفيفة اختيارية للعرض المستقل */
    body { background: #f6f7fb; color: var(--text, #1f2937); }
    .container { max-width: 1100px; margin-inline: auto; padding: 20px; }
    .card { display: grid; grid-template-columns: 1fr 1fr; gap: 0; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,.06); }
    .panel-visual { background: var(--primary-700, #1f5eff); color: #fff; padding: 32px; display: flex; flex-direction: column; justify-content: center; }
    .panel-form { padding: 32px; background: #fff; }
    .brand { display:flex; align-items:center; gap:12px; margin-bottom: 8px; }
    .brand img { width: 56px; height: 56px; border-radius: 12px; display:block; }
    .brand .title { font-weight: 800; font-size: 20px; letter-spacing:.3px }
    .tag { opacity:.9; display:inline-block; margin-bottom: 16px; }
    .visual-copy h2 { margin: 0 0 8px; }
    .subtitle { opacity:.85; margin: 0 0 20px; }
    .form { display: grid; gap: 12px; }
    .label { font-weight: 600; font-size: 14px; }
    .input { border: 1px solid var(--border, #e5e7eb); border-radius: 10px; padding: 12px 14px; background: #fff; outline: none; }
    .input:focus { box-shadow: 0 0 0 3px rgba(31,94,255,.15); border-color: var(--primary-700, #1f5eff); }
    .label-inline { display: inline-flex; align-items: center; gap: 8px; margin-top: 6px; }
    .btn { display:inline-block; border-radius: 10px; padding: 12px 16px; text-align:center; font-weight:700; border: 1px solid transparent; cursor:pointer; }
    .btn.primary { background: var(--primary-700, #1f5eff); color:#fff; }
    .btn.primary:hover { opacity:.92; transform: translateY(-1px); transition: .15s ease; }
    .w-full { width: 100%; }
    .muted { color: #6b7280; }
    .alert.error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; padding: 10px 12px; border-radius: 10px; }
    .mt-4 { margin-top: 1rem; }

    @media (max-width: 880px) {
      .card { grid-template-columns: 1fr; }
      .panel-visual { display: none; }
    }
  </style>
</head>
<body>
  <main class="container">
    <section class="card" role="region" aria-labelledby="login-title">
      <aside class="panel-visual">
        <div class="brand">
          <img src="{{ asset('css/assets/logo-final.png') }}" alt="GitEval AI Logo">
          <div class="title">GitEval AI</div>
        </div>
        <span class="tag">Academic • GitHub • AI</span>
        <div class="visual-copy">
          <h2>تقييم عادل وذكي لمشاريع التخرج</h2>
          <p class="subtitle">تكامل مع GitHub وتحليل جودة الشيفرة وكشف التشابه — كل ذلك من مكان واحد.</p>
        </div>
      </aside>

      <div class="panel-form">
        <h2 id="login-title">تسجيل الدخول</h2>
        <p class="subtitle">سجّل دخولك للوصول إلى لوحة التحكم.</p>

        @if ($errors->any())
          <div class="alert error">
            {{ $errors->first() }}
          </div>
        @endif

        <form class="form" method="POST" action="{{ route('login.post') }}" novalidate>
          @csrf

          <label class="label" for="email">البريد الإلكتروني</label>
          <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>

          <label class="label" for="password">كلمة المرور</label>
          <input class="input" id="password" name="password" type="password" required>

          <label class="label-inline">
            <input type="checkbox" name="remember"> تذكرني
          </label>

          <button type="submit" class="btn primary w-full">دخول</button>
        </form>

        <p class="muted mt-4">
          ليس لديك حساب؟ <a href="{{ route('register') }}">أنشئ حساب</a>
        </p>
      </div>
    </section>
  </main>
</body>
</html>
