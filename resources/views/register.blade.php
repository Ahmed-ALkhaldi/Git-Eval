<!-- resources/views/register.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register new User</title>
</head>
<body>
    <h2>Register new User</h2>

    @if($errors->any())
        <div style="color:red;">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <label>الاسم:</label><br>
        <input type="text" name="name" required><br><br>

        <label>البريد الإلكتروني:</label><br>
        <input type="email" name="email" required><br><br>

        <label>كلمة المرور:</label><br>
        <input type="password" name="password" required><br><br>

        <label>تأكيد كلمة المرور:</label><br>
        <input type="password" name="password_confirmation" required><br><br>

        <label for="role">Select Role:</label>
        <select name="role" required>
            <option value="">-- Select Role --</option>
            <option value="student">Student</option>
            <option value="supervisor">Supervisor</option>
            <!-- If needed:
            <option value="admin">Admin</option>
            -->
        </select><br><br>

        <button type="submit">تسجيل</button>
    </form>

    <p>هل لديك حساب بالفعل؟ <a href="{{ route('auth.login') }}">تسجيل الدخول</a></p>
</body>
</html>
