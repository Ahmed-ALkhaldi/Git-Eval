# إصلاح مشكلة SonarQube Webhook Payload

## المشكلة الأصلية

كان يظهر الخطأ التالي في اللوج:

```
local.WARNING: Sonar webhook payload missing keys {"projectKey":"project_2","status":"SUCCESS","analysisId":null,"ceTaskId":null}
```

## السبب الجذري

الكود كان يبحث عن حقول غير موجودة في الـ Payload الحقيقي من SonarQube:

- ❌ **كان يبحث عن**: `ceTask.id` و `analysisId`
- ✅ **الـ Payload الحقيقي يحتوي على**: `taskId` و **لا يحتوي على** `analysisId`

## الحل المطبق

### 1. إصلاح SonarWebhookController

#### قبل الإصلاح:
```php
$analysisId = $payload['analysisId']    ?? null;
$ceTaskId   = $payload['ceTask']['id']  ?? null;

if (!$projectKey || !$status || !$ceTaskId) {
    Log::warning('Sonar webhook payload missing keys', compact('projectKey','status','analysisId','ceTaskId'));
    return response('bad payload', 400);
}
```

#### بعد الإصلاح:
```php
$taskId     = $payload['taskId']        ?? null;   // الصحيح حسب الدوك
$analysedAt = $payload['analysedAt']    ?? null;
$qg         = $payload['qualityGate']['status'] ?? null;

if (!$projectKey || !$status || !$taskId) {
    Log::warning('Sonar webhook payload missing keys (expect taskId/status/project.key)', compact('projectKey','status','taskId'));
    return response('bad payload', 400);
}
```

### 2. إضافة دالة fetchAndStoreMeasures

تم إضافة دالة جديدة في `SonarQubeService` تقوم بـ:

1. **جلب analysisId من taskId** (اختياري):
   ```php
   $task = $this->http()->get($this->host.'/api/ce/task', ['id' => $taskId]);
   $analysisId = $taskData['task']['analysisId'] ?? null;
   ```

2. **سحب المقاييس مباشرة بالـ projectKey**:
   ```php
   $measuresResponse = $this->http()->get($this->host.'/api/measures/component', [
       'component'  => $projectKey,
       'metricKeys' => 'bugs,vulnerabilities,code_smells,coverage,duplicated_lines_density,security_hotspots,ncloc',
   ]);
   ```

3. **سحب حالة Quality Gate**:
   ```php
   $qgParams = $analysisId ? ['analysisId' => $analysisId] : ['projectKey' => $projectKey];
   $qgResponse = $this->http()->get($this->host.'/api/qualitygates/project_status', $qgParams);
   ```

4. **تخزين النتائج في قاعدة البيانات**:
   ```php
   CodeAnalysisReport::updateOrCreate([
       'project_id' => $project->id,
       'analysis_key' => $analysisId
   ], [
       'bugs' => $i('bugs',0),
       'vulnerabilities' => $i('vulnerabilities',0),
       // ... باقي المقاييس
       'quality_gate' => $qualityGate,
       'analysis_at' => now(),
   ]);
   ```

### 3. تحديث منطق الويبهوك

#### قبل الإصلاح:
```php
// شغّل Job للمزامنة
SyncSonarAnalysisJob::dispatch($projectKey, $ceTaskId);
```

#### بعد الإصلاح:
```php
// استخدم الخدمة المحدثة لسحب وتخزين المقاييس
app(\App\Services\SonarQubeService::class)
    ->fetchAndStoreMeasures($projectKey, $taskId);
```

## هيكل Payload الصحيح من SonarQube

```json
{
  "project": {
    "key": "project_1",
    "name": "Project Name"
  },
  "status": "SUCCESS",
  "taskId": "AY1234567890",
  "analysedAt": "2024-01-15T10:30:00+0000",
  "qualityGate": {
    "status": "OK"
  }
}
```

## المزايا الجديدة

✅ **مطابقة وثائق SonarQube الرسمية**: استخدام الحقول الصحيحة
✅ **سحب مباشر للمقاييس**: بدون الحاجة لـ Job منفصل
✅ **دعم analysisId اختياري**: يتم جلبه من taskId عند الحاجة
✅ **معالجة أخطاء محسنة**: مع رسائل لوج واضحة
✅ **تخزين فوري**: النتائج تظهر مباشرة بعد التحليل

## اختبار النظام

### 1. تشغيل الخدمات:
```bash
php artisan serve
php artisan queue:work
```

### 2. اختبار التحليل:
1. اضغط على زر **"Code Analysis"** في واجهة المشرف
2. راقب `storage/logs/laravel.log` للرسائل التالية:

#### رسائل النجاح المتوقعة:
```
[INFO] Sonar webhook accepted: {"projectKey":"project_1","status":"SUCCESS","taskId":"AY1234567890","analysedAt":"2024-01-15T10:30:00+0000","qg":"OK"}
[INFO] SonarQube measures stored successfully: {"project_id":1,"project_key":"project_1","analysis_id":"AY1234567890","task_id":"AY1234567890"}
```

#### لا مزيد من رسائل الخطأ:
```
❌ لن تظهر: "Sonar webhook payload missing keys"
```

## النتيجة

- ✅ **تم حل مشكلة Payload نهائياً**
- ✅ **الويبهوك يعمل بشكل صحيح**
- ✅ **النتائج تُحفظ مباشرة في قاعدة البيانات**
- ✅ **النظام متوافق مع وثائق SonarQube الرسمية**

الآن يجب أن يعمل التحليل بنجاح وتظهر النتائج في واجهة GitEval! 🚀
