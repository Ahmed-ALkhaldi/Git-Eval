# تعليمات تشغيل SonarQube

## تشغيل SonarQube Server

### الطريقة 1: تشغيل مباشر
```bash
# افتح Command Prompt أو PowerShell كـ Administrator
cd C:\sonarqube-25.6.0.109173\bin\windows-x86-64
StartSonar.bat
```

### الطريقة 2: تشغيل في الخلفية
```bash
# في PowerShell
Start-Process -FilePath "C:\sonarqube-25.6.0.109173\bin\windows-x86-64\StartSonar.bat" -WindowStyle Hidden
```

## التحقق من حالة SonarQube

### فتح المتصفح:
- **URL**: http://localhost:9000
- **Username**: admin
- **Password**: admin (افتراضي)

### التحقق من API:
```bash
curl http://localhost:9000/api/system/status
```

## إعدادات Laravel

### في ملف `.env`:
```env
SONARQUBE_HOST=http://localhost:9000
SONARQUBE_TOKEN=your-token-here
SONAR_SCANNER_BIN="C:/sonar-scanner-4.3.0.2102-windows/bin/sonar-scanner.bat"
SONAR_ANALYZE_BAT=sonar_analyze.bat
SONAR_SCANNER_TIMEOUT=600
JAVA_HOME="C:/Program Files/Java/jdk-17"
```

## الحصول على Token

1. افتح SonarQube: http://localhost:9000
2. سجل الدخول كـ admin
3. اذهب إلى **My Account → Security**
4. أنشئ token جديد
5. ضع الـ token في `.env`

## اختبار النظام

1. شغل SonarQube Server
2. نظّف الكاش بعد تحديث `.env`:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   ```
3. شغل Laravel Server: `php artisan serve`
4. شغل Queue Worker: `php artisan queue:work --tries=3 --backoff=5`
5. اضغط على زر "Code Analysis" في واجهة المشرف

## استكشاف الأخطاء

### إذا ظهر خطأ "The system cannot find the path specified":
- تأكد من أن مسار `SONAR_SCANNER_BIN` صحيح
- تأكد من أن sonar-scanner مثبت في المسار المحدد

### إذا ظهر خطأ "Connection refused":
- تأكد من أن SonarQube يعمل على http://localhost:9000
- تأكد من أن الـ token صحيح

### إذا ظهر خطأ "Analysis failed":
- تحقق من logs في `storage/logs/laravel.log`
- تأكد من أن المشروع يحتوي على ملفات PHP للتحليل

### إذا ظهر خطأ "Unrecognized VM option 'MaxPermSize'":
- هذا يحدث مع Java 8+ لأن `MaxPermSize` تم إزالته
- تم إصلاحه في `sonar_analyze.bat` باستخدام إعدادات Java 8+ المتوافقة
- استخدام `-Xmx2048m -Xms512m` بدلاً من `MaxPermSize`

### إذا ظهر خطأ "AccessDeniedException" في C:\Windows:
- SonarQube Scanner يحاول إنشاء ملفات مؤقتة في مجلد Windows
- تم إصلاحه بتوجيه الملفات المؤقتة إلى مجلد المستخدم
- استخدام `-Djava.io.tmpdir=%TEMP%` لتجنب مشاكل الصلاحيات

## التحديثات الجديدة

### تحسينات مسار Scanner:
- استخدام forward slashes (`/`) بدلاً من backslashes (`\`)
- وضع المسار بين علامات اقتباس (`"`)
- إضافة متغيرات بيئة `NO_PROXY` لتجاوز مشاكل Proxy على Windows

### استبدال sonar.login بـ sonar.token:
- `sonar.login` أصبح مهجوراً (deprecated)
- استخدام `sonar.token` بدلاً منه لتجنب التحذيرات

### تنظيف الكاش:
بعد أي تعديل على `.env`، يجب تشغيل:
```bash
php artisan config:clear && php artisan cache:clear && php artisan route:clear
```

## طريقة التنفيذ الجديدة - Batch File

### ملف sonar_analyze.bat:
- ينفّذ عبر CMD مباشرة خارج بيئة Laravel
- يضبط `NO_PROXY` و `SONAR_SCANNER_OPTS` تلقائياً
- يضيف `JAVA_HOME\bin` إلى الـ PATH مؤقتاً
- يعالج خطأ 10106 على Windows
- يستخدم `sonar.token` (الأحدث) بدل `sonar.login`

### مزايا الطريقة الجديدة:
- **استقرار أكبر**: تنفيذ مباشر عبر CMD
- **معالجة أخطاء Windows**: حل مشاكل Proxy والشبكة
- **أمان أفضل**: إخفاء Token في اللوجز
- **مرونة أكثر**: سهولة تعديل إعدادات التحليل

### كيفية العمل:
1. Laravel يستدعي `sonar_analyze.bat`
2. الـ batch يضبط البيئة ويشغل Scanner
3. النتائج تُرفع لـ SonarQube Server
4. Webhook يُزامن النتائج إلى قاعدة البيانات
