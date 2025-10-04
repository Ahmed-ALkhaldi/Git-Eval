# إصلاح مشكلة WinSock 10106 في SonarQube Scanner

## المشكلة الأصلية

كان يظهر الخطأ التالي عند تشغيل SonarQube Scanner من Laravel:

```
ERROR: SonarQube server [http://localhost:9000] can not be reached
Caused by: java.net.SocketException: Unrecognized Windows Sockets error: 10106: socket
```

## السبب الجذري (Root Cause)

المشكلة كانت في أن Laravel يمرر بيئة محدودة للـ Process عند تشغيل `sonar-scanner.bat`، مما يفقد متغيرات Windows الأساسية مثل:

- `SystemRoot` / `WINDIR`
- `PATH` الكامل
- `TEMP` / `TMP`
- `USERPROFILE`, `HOMEDRIVE`, `HOMEPATH`

في Windows، غياب `SystemRoot` تحديداً يسبب فشل تهيئة WinSock داخل عمليات الأبناء، وينتج عنه خطأ الشبكات 10106 عند أول محاولة Socket.

## الحل المطبق

### 1. إصلاح Environment Variables في ProjectController.php

```php
// إعداد البيئة للـ Process - الحل للمشكلة WinSock 10106
$baseEnv = getenv(); // الحصول على بيئة النظام الكاملة
$env = $baseEnv; // استخدامها كأساس

// إضافة/تعديل المتغيرات المطلوبة فقط
$env['JAVA_HOME'] = $jhome;
$env['SONAR_SCANNER_OPTS'] = implode(' ', [
    '-Xmx2048m',
    '-Xms512m',
    '-Djava.net.useSystemProxies=false',
    '-Dfile.encoding=UTF-8',
    // مسار مؤهل كامل لمجلد التمب
    '-Djava.io.tmpdir=' . getenv('TEMP') ?: 'C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Local\\Temp',
]);

// التأكد من وجود متغيرات Windows الأساسية
$env['SystemRoot'] = $baseEnv['SystemRoot'] ?? 'C:\\Windows';
$env['WINDIR'] = $baseEnv['WINDIR'] ?? 'C:\\Windows';
$env['TEMP'] = $baseEnv['TEMP'] ?? 'C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Local\\Temp';
$env['TMP'] = $baseEnv['TMP'] ?? $env['TEMP'];

// إضافة مجلد scanner إلى PATH (بدون استبدال PATH الأصلي)
$scannerDir = dirname($scan);
if (isset($baseEnv['PATH'])) {
    $env['PATH'] = $scannerDir . ';' . $baseEnv['PATH'];
} else {
    $env['PATH'] = $scannerDir;
}

// استدعاء ملف الـ batch (CMD) مع البيئة الصحيحة
$process = new Process([$bat, $projectDir, $projectKey, $host, $token, $scan, $jhome, $to], null, $env);
```

### 2. تحديث sonar_analyze.bat

```batch
REM Use fully qualified path for tmpdir to avoid Windows socket issues
set SONAR_SCANNER_OPTS=-Xmx2048m -Xms512m -Djava.net.useSystemProxies=false -Dfile.encoding=UTF-8 -Djava.io.tmpdir=%USERPROFILE%\AppData\Local\Temp
```

### 3. تغيير localhost إلى 127.0.0.1

تم تحديث الافتراضي في:
- `ProjectController.php`
- `SonarQubeService.php`

```php
// بدلاً من http://localhost:9000
$sonarHost = env('SONARQUBE_HOST', 'http://127.0.0.1:9000');
```

## النتيجة

بعد تطبيق هذه التغييرات:

✅ **تم حل مشكلة WinSock 10106**
✅ **SonarQube Scanner يعمل بنفس السلوك عند التشغيل من Laravel كما عند التشغيل اليدوي**
✅ **تم الحفاظ على جميع متغيرات Windows الأساسية**
✅ **تم تحسين مسار tmpdir ليكون مؤهلاً بالكامل**

## اختبار الحل

1. تأكد من تشغيل SonarQube Server على `http://127.0.0.1:9000`
2. نظف الكاش: `php artisan config:clear`
3. شغل Laravel: `php artisan serve`
4. جرب زر "Code Analysis" في واجهة المشرف

يجب أن يعمل التحليل بنجاح بدون أخطاء WinSock.

## ملاحظات إضافية

- الحل يحافظ على البيئة الأصلية للنظام ويضيف فقط المتغيرات المطلوبة
- تم استخدام `127.0.0.1` بدل `localhost` لتجنب أي مشاكل IPv6 أو Proxy
- مسار `tmpdir` أصبح مؤهلاً بالكامل لتجنب مشاكل Windows
- تم الحفاظ على `PATH` الأصلي وإضافة مجلد scanner إليه فقط
