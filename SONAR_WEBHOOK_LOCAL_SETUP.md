# إعداد SonarQube Webhook للبيئة المحلية

## نظرة عامة

تم تطبيق نظام ويبهوك SonarQube مع قبول بدون توقيع في البيئة المحلية فقط، مع الحماية المناسبة.

## التعديلات المطبقة

### 1. إعدادات الخدمة (`config/services.php`)

```php
'sonar' => [
    'webhook_secret' => env('SONAR_WEBHOOK_SECRET', ''),
],
```

### 2. مسار الويبهوك (`routes/api.php`)

```php
// SonarQube Webhook - مسار منفصل بدون حماية Sanctum
Route::post('/webhooks/sonar', [SonarWebhookController::class, 'handle'])
    ->middleware('throttle:sonar-webhook');
```

### 3. Rate Limiter (`app/Providers/AppServiceProvider.php`)

```php
// Rate Limiter للويبهوك SonarQube
RateLimiter::for('sonar-webhook', function ($request) {
    // 30 طلب في الدقيقة كفاية للـ CE + إعادة الإرسال
    return [Limit::perMinute(30)->by('sonar-webhook')];
});
```

### 4. منطق الويبهوك (`app/Http/Controllers/SonarWebhookController.php`)

#### سياسة التحقق الذكية:

1. **إذا كان هناك `SONAR_WEBHOOK_SECRET`:**
   - يتم التحقق من التوقيع HMAC-SHA256
   - مناسب للإنتاج والستيجينغ

2. **إذا لم يكن هناك `SONAR_WEBHOOK_SECRET`:**
   - يتم القبول فقط إذا:
     - `APP_ENV=local`
     - IP المصدر هو `127.0.0.1` أو `::1`
     - User-Agent يبدأ بـ `SonarQube/`

#### معالجة الحمولة:

- تحقق من بنية JSON الصحيحة
- استخراج `projectKey`, `status`, `analysisId`, `ceTaskId`
- تشغيل `SyncSonarAnalysisJob` عند النجاح فقط
- إرجاع HTTP 204 (No Content) للاستجابة

## إعداد البيئة المحلية

### في ملف `.env`:

```env
APP_ENV=local
APP_DEBUG=true

# اتركه فارغاً أثناء التطوير المحلي
SONAR_WEBHOOK_SECRET=

# إعدادات SonarQube الأخرى
SONARQUBE_HOST=http://127.0.0.1:9000
SONARQUBE_TOKEN=your-token-here
```

## إعداد SonarQube

### في SonarQube Admin Panel:

1. اذهب إلى **Administration → Webhooks**
2. أضف webhook جديد:
   - **Name**: `GitEval Local`
   - **URL**: `http://127.0.0.1:8000/api/webhooks/sonar`
   - **Secret**: اتركه فارغاً للبيئة المحلية

## اختبار النظام

### 1. تشغيل الخدمات:

```bash
# تشغيل SonarQube Server
cd C:\sonarqube-25.6.0.109173\bin\windows-x86-64
StartSonar.bat

# تشغيل Laravel Server
php artisan serve

# تشغيل Queue Worker
php artisan queue:work --tries=3 --backoff=5
```

### 2. اختبار التحليل:

1. اضغط على زر **"Code Analysis"** في واجهة المشرف
2. راقب `storage/logs/laravel.log` للرسائل التالية:

```
[INFO] Sonar webhook accepted
[INFO] Sonar webhook job dispatched
[INFO] Code analysis report updated
```

### 3. رسائل اللوج المتوقعة:

#### عند القبول بدون توقيع (محلي):
```
[INFO] Sonar webhook accepted: {"projectKey":"project_1","status":"SUCCESS","analysisId":"...","ceTaskId":"...","ip":"127.0.0.1"}
[INFO] Sonar webhook job dispatched: {"projectKey":"project_1","ceTaskId":"..."}
```

#### عند الرفض (غير محلي):
```
[WARNING] Rejected unsigned Sonar webhook (not local or bad UA/IP): {"env":"production","ip":"192.168.1.100","ua":"curl/7.68.0"}
```

## الأمان

### الحماية المطبقة:

1. **Rate Limiting**: 30 طلب في الدقيقة
2. **IP Restriction**: فقط `127.0.0.1` و `::1` في البيئة المحلية
3. **User-Agent Validation**: يجب أن يبدأ بـ `SonarQube/`
4. **Environment Check**: فقط `APP_ENV=local`
5. **Payload Validation**: تحقق من بنية JSON الصحيحة

### للانتقال للإنتاج:

1. ضع `SONAR_WEBHOOK_SECRET` في `.env`
2. أضف نفس الـ secret في SonarQube webhook settings
3. غير `APP_ENV` إلى `production`

## استكشاف الأخطاء

### إذا لم يتم قبول الويبهوك:

1. تأكد من `APP_ENV=local`
2. تأكد من أن SonarQube يرسل من `127.0.0.1`
3. تحقق من User-Agent في SonarQube
4. راقب اللوج للرسائل التحذيرية

### إذا لم يتم تشغيل Job:

1. تأكد من تشغيل Queue Worker
2. تحقق من أن `SONARQUBE_HOST` صحيح
3. تأكد من صحة `SONARQUBE_TOKEN`

## المزايا

✅ **أمان محلي**: قبول بدون توقيع فقط في البيئة المحلية
✅ **حماية قوية**: متطلبات متعددة للقبول بدون توقيع
✅ **سهولة التطوير**: لا حاجة لإعداد توقيع محلياً
✅ **جاهز للإنتاج**: دعم كامل للتوقيع في الإنتاج
✅ **Rate Limiting**: حماية من الهجمات
✅ **Logging شامل**: تتبع كامل للعمليات
