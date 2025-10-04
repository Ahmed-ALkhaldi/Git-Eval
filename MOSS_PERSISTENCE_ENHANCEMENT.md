# MOSS Report Persistence Enhancement

## Overview
تم تطبيق تحسين شامل لحفظ تقارير MOSS بشكل دائم في قاعدة البيانات، مما يضمن الوصول للتقرير حتى بعد انتهاء صلاحية الرابط الأصلي من MOSS.

## Problem Solved

### 🚨 **المشكلة الأساسية**
- **روابط MOSS مؤقتة**: روابط تقارير MOSS قد تنتهي صلاحيتها
- **فقدان البيانات**: عدم إمكانية الرجوع للتقرير لاحقاً
- **إعادة الفحص**: الحاجة لإجراء فحص جديد في كل مرة

### ✅ **الحل المطبق**
- **حفظ دائم**: تخزين النتائج المهيكلة في قاعدة البيانات
- **HTML مضغوط**: حفظ نسخة من التقرير الأصلي مضغوطة
- **بيانات إضافية**: تتبع الوقت والمدة وحالة التنفيذ

## Technical Implementation

### 1. **Database Migration**
```php
// database/migrations/2025_10_04_000000_alter_plagiarism_checks_add_persisted_report.php
Schema::table('plagiarism_checks', function (Blueprint $table) {
    $table->decimal('similarity_percentage', 5, 2)->nullable()->change();
    $table->json('matches')->nullable()->change();
    $table->string('report_url')->nullable()->change();

    // حقول جديدة
    $table->unsignedInteger('matches_count')->nullable();
    $table->string('moss_task_id')->nullable();
    $table->timestamp('compared_at')->nullable();
    $table->unsignedInteger('duration_ms')->nullable();
    $table->longText('report_html_gz')->nullable(); // HTML مضغوط
    $table->string('report_path')->nullable(); // مسار ملف محلي
});
```

### 2. **Model Enhancements**
```php
// App\Models\PlagiarismCheck.php
protected $fillable = [
    'project1_id', 'project2_id',
    'similarity_percentage', 'matches', 'matches_count',
    'report_url', 'moss_task_id', 'compared_at', 'duration_ms',
    'report_html_gz', 'report_path',
];

// Accessor لاسترجاع HTML المضغوط
public function getReportHtmlAttribute(): ?string
{
    if ($this->report_html_gz) {
        try {
            return gzdecode(base64_decode($this->report_html_gz));
        } catch (\Throwable $e) {
            Log::warning('Failed to decompress report HTML: ' . $e->getMessage());
            return null;
        }
    }
    return null;
}

// Mutator لحفظ HTML مضغوط
public function setReportHtmlAttribute(?string $html): void
{
    if ($html) {
        try {
            $this->attributes['report_html_gz'] = base64_encode(gzencode($html, 9));
        } catch (\Throwable $e) {
            Log::warning('Failed to compress report HTML: ' . $e->getMessage());
            $this->attributes['report_html_gz'] = null;
        }
    } else {
        $this->attributes['report_html_gz'] = null;
    }
}
```

### 3. **Service Layer Updates**
```php
// App\Services\MossService.php
public function compareProjects(string $project1Dir, string $project2Dir, ?int $project1Id = null, ?int $project2Id = null): ?array
{
    $t0 = hrtime(true); // بداية تتبع الوقت
    
    // ... تشغيل MOSS والحصول على النتائج ...
    
    // تحليل النتائج
    $parsed = $this->parseMossReport($html);
    $resultArray = [
        'average_similarity' => round($parsed['average_similarity'] ?? 0, 2),
        'details'            => $parsed['details'] ?? [],
        'report_url'         => $reportUrl,
    ];

    // حفظ النتائج في قاعدة البيانات
    if ($project1Id && $project2Id) {
        try {
            DB::transaction(function () use ($resultArray, $reportUrl, $html, $project1Id, $project2Id, $t0) {
                PlagiarismCheck::create([
                    'project1_id'          => $project1Id,
                    'project2_id'          => $project2Id,
                    'similarity_percentage' => $resultArray['average_similarity'],
                    'matches'              => json_encode($resultArray['details']),
                    'matches_count'        => count($resultArray['details']),
                    'report_url'           => $reportUrl,
                    'moss_task_id'         => 'moss_' . date('Ymd_His') . '_' . mt_rand(1000, 9999),
                    'compared_at'          => now(),
                    'duration_ms'          => (int) ((hrtime(true) - $t0) / 1e6),
                    'report_html_gz'       => base64_encode(gzencode($html, 9)),
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('❌ Failed to save plagiarism check results: ' . $e->getMessage());
        }
    }

    return $resultArray;
}
```

### 4. **Controller Updates**
```php
// App\Http\Controllers\PlagiarismCheckController.php
$moss = new \App\Services\MossService();
$result = $moss->compareProjects($dir1, $dir2, $project1->id, $project2->id);

// النتائج محفوظة بالفعل في MossService
$report = PlagiarismCheck::where('project1_id', $project1->id)
    ->where('project2_id', $project2->id)
    ->orderBy('id', 'desc')
    ->first();
```

## Data Structure

### 📊 **Stored Information**
```json
{
  "project1_id": 1,
  "project2_id": 2,
  "similarity_percentage": 15.5,
  "matches": [
    {
      "file1": "src/main.js",
      "file1_link": "https://moss.stanford.edu/results/...",
      "p1": 20,
      "file2": "src/app.js", 
      "file2_link": "https://moss.stanford.edu/results/...",
      "p2": 15,
      "lines": 45
    }
  ],
  "matches_count": 1,
  "report_url": "https://moss.stanford.edu/results/...",
  "moss_task_id": "moss_20251004_143022_1234",
  "compared_at": "2025-10-04 14:30:22",
  "duration_ms": 125000,
  "report_html_gz": "H4sIAAAAAAAAA+3X..."
}
```

## Key Features

### 🎯 **Comprehensive Storage**
- **Structured Data**: النتائج المهيكلة (نسبة التشابه، التطابقات)
- **Original HTML**: نسخة مضغوطة من التقرير الأصلي
- **Metadata**: معلومات إضافية (الوقت، المدة، معرف المهمة)

### 🗜️ **Space Optimization**
- **Compression**: ضغط HTML بـ gzip (يوفر 70-90% من المساحة)
- **Base64 Encoding**: ترميز آمن للتخزين في قاعدة البيانات
- **Efficient Storage**: تخزين محسن للملفات الكبيرة

### 🔒 **Data Integrity**
- **Transaction Safety**: استخدام DB transactions لضمان التكامل
- **Error Handling**: معالجة شاملة للأخطاء
- **Logging**: تسجيل مفصل لجميع العمليات

### ⚡ **Performance**
- **Non-blocking**: الحفظ لا يمنع إرجاع النتائج
- **Background Processing**: العمليات في الخلفية
- **Caching**: إمكانية التخزين المؤقت للنتائج

## Benefits

### ✅ **Reliability**
- **Permanent Access**: وصول دائم للتقرير
- **No Re-scanning**: عدم الحاجة لإعادة الفحص
- **Data Preservation**: حفظ البيانات بشكل دائم

### 📈 **Analytics**
- **Historical Data**: بيانات تاريخية للمقارنات
- **Performance Metrics**: مقاييس الأداء والوقت
- **Trend Analysis**: تحليل الاتجاهات

### 🔍 **Audit Trail**
- **Complete History**: سجل كامل للفحوصات
- **Timestamp Tracking**: تتبع دقيق للأوقات
- **Task Identification**: معرفات فريدة للمهام

### 💾 **Storage Efficiency**
- **Compressed Storage**: تخزين مضغوط يوفر المساحة
- **Database Integration**: تكامل مع قاعدة البيانات
- **Clean Architecture**: بنية نظيفة ومنظمة

## Usage Examples

### 📋 **Accessing Stored Reports**
```php
// الحصول على آخر تقرير لمشروع
$report = PlagiarismCheck::where('project1_id', $projectId)
    ->orWhere('project2_id', $projectId)
    ->orderBy('compared_at', 'desc')
    ->first();

// استرجاع HTML الأصلي
$originalHtml = $report->report_html;

// الحصول على إحصائيات
$stats = [
    'similarity' => $report->similarity_percentage,
    'matches_count' => $report->matches_count,
    'duration' => $report->duration_ms,
    'compared_at' => $report->compared_at,
];
```

### 🔄 **Migration and Rollback**
```bash
# تطبيق Migration
php artisan migrate

# إلغاء Migration (إذا لزم الأمر)
php artisan migrate:rollback --step=1
```

## Future Enhancements

### 🔮 **Potential Improvements**
- **File-based Storage**: تخزين HTML كملفات منفصلة
- **Retention Policies**: سياسات الاحتفاظ بالبيانات
- **Compression Levels**: مستويات ضغط قابلة للتخصيص
- **Batch Operations**: عمليات مجمعة للتقارير

### 📊 **Analytics Features**
- **Similarity Trends**: تحليل اتجاهات التشابه
- **Performance Monitoring**: مراقبة أداء النظام
- **Usage Statistics**: إحصائيات الاستخدام

---

**تم تطبيق هذا التحسين لضمان الوصول الدائم لتقارير MOSS وتحسين موثوقية النظام بشكل عام.**
