<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Login Form</h2>

    <!-- عرض رسائل الخطأ إن وجدت -->
    @if ($errors->any())
        <div style="color:red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('auth.login.submit') }}">
        @csrf
        <input type="email" name="email" placeholder="Email" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit">Login</button>
    </form>

    <br>
    <!-- رابط إلى صفحة التسجيل -->
    <p>Don't have an account?</p>
    <a href="{{ route('auth.register.form') }}">
        <button>Register</button>
    </a>
</body>
</html>
