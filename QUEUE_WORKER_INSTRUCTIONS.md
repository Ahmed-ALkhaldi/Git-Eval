# تعليمات تشغيل Queue Worker

## لماذا نحتاج Queue Worker؟

عند الضغط على زر "Code Analysis"، النظام يشغل `SyncSonarAnalysisJob` في الخلفية لجلب النتائج من SonarQube وحفظها في قاعدة البيانات.

**بدون Queue Worker، لن تُحفظ النتائج!**

## كيفية تشغيل Queue Worker

### الطريقة 1: تشغيل مباشر (للتطوير)
```bash
php artisan queue:work --tries=3 --backoff=5
```

### الطريقة 2: تشغيل في الخلفية (للإنتاج)
```bash
# Windows (PowerShell)
Start-Process -NoNewWindow php -ArgumentList "artisan queue:work --tries=3 --backoff=5"

# Linux/Mac
nohup php artisan queue:work --tries=3 --backoff=5 &
```

### الطريقة 3: استخدام Supervisor (مُستحسن للإنتاج)
إنشاء ملف `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

ثم تشغيل:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## مراقبة Queue Worker

### فحص حالة الطوابير:
```bash
php artisan queue:monitor
```

### فحص Jobs المعلقة:
```bash
php artisan queue:failed
```

### إعادة تشغيل Jobs فاشلة:
```bash
php artisan queue:retry all
```

## إعدادات مهمة

### في ملف `.env`:
```env
QUEUE_CONNECTION=database
```

### إعدادات Job:
- `--tries=3`: عدد المحاولات عند الفشل
- `--backoff=5`: انتظار 5 ثواني بين المحاولات
- `--timeout=60`: مهلة كل job (بالثواني)

## استكشاف الأخطاء

### فحص logs:
```bash
tail -f storage/logs/laravel.log
```

### فحص Jobs في قاعدة البيانات:
```sql
SELECT * FROM jobs ORDER BY created_at DESC LIMIT 10;
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;
```

## ملاحظات مهمة

1. **يجب تشغيل Queue Worker دائماً** في بيئة الإنتاج
2. **استخدم Supervisor** أو **systemd** لإدارة العملية
3. **راقب الذاكرة** - قد تحتاج إعادة تشغيل دورية
4. **احتفظ بـ logs** لمراقبة الأداء والأخطاء

## اختبار النظام

1. شغل Queue Worker
2. اضغط على زر "Code Analysis" 
3. راقب logs للتأكد من تشغيل Job
4. تحقق من ظهور النتائج في قاعدة البيانات
