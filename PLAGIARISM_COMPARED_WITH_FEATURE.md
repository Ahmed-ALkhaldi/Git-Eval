# إضافة اسم المشروع الآخر في قسم Plagiarism بالتقرير النهائي

## المطلوب

إظهار اسم المشروع الآخر الذي تم المقارنة به في قسم Plagiarism في التقرير النهائي.

## التعديلات المطبقة

### 1. إضافة العلاقات في نموذج PlagiarismCheck

**ملف:** `app/Models/PlagiarismCheck.php`

```php
/** المشروع الأول في المقارنة */
public function project1()
{
    return $this->belongsTo(Project::class, 'project1_id');
}

/** المشروع الثاني في المقارنة */
public function project2()
{
    return $this->belongsTo(Project::class, 'project2_id');
}
```

### 2. تحديث ProjectController لتحميل العلاقات

**ملف:** `app/Http/Controllers/ProjectController.php`

```php
// تحميل العلاقات اللازمة للعرض
$project->load([
    'owner.user',
    'supervisor.user',
    'students.user',
    'repository',
    'evaluation',
    'studentEvaluations.student.user',
    'plagiarismChecks.project1',             // project_id = this مع معلومات المشروع الأول
    'plagiarismChecks.project2',             // project_id = this مع معلومات المشروع الثاني
    'plagiarismChecksAsProject2.project1',   // project2_id = this مع معلومات المشروع الأول
    'plagiarismChecksAsProject2.project2',   // project2_id = this مع معلومات المشروع الثاني
    'codeAnalysisReport',
]);
```

### 3. تحديث صفحة التقرير النهائي

**ملف:** `resources/views/projects/report.blade.php`

#### قبل التعديل:
```php
<div class="section">
  <div class="title"><h2>Plagiarism</h2></div>
  @if($plag)
    <p><strong>Similarity:</strong> {{ $fmt($sim, '%') }}</p>
    <p><strong>Report:</strong>
      @if($rep)
        <a href="{{ $rep }}" target="_blank" rel="noopener">Open report</a>
      @else
        <span class="muted">—</span>
      @endif
    </p>
  @else
    <p class="muted">No plagiarism check found.</p>
  @endif
</div>
```

#### بعد التعديل:
```php
<div class="section">
  <div class="title"><h2>Plagiarism</h2></div>
  @if($plag)
    <p><strong>Similarity:</strong> {{ $fmt($sim, '%') }}</p>
    @php
      // تحديد المشروع الآخر في المقارنة
      $otherProject = null;
      if ($plag->project1_id == $project->id) {
          $otherProject = $plag->project2;
      } elseif ($plag->project2_id == $project->id) {
          $otherProject = $plag->project1;
      }
    @endphp
    @if($otherProject)
      <p><strong>Compared with:</strong> {{ $otherProject->title }}</p>
    @endif
    <p><strong>Report:</strong>
      @if($rep)
        <a href="{{ $rep }}" target="_blank" rel="noopener">Open report</a>
      @else
        <span class="muted">—</span>
      @endif
    </p>
  @else
    <p class="muted">No plagiarism check found.</p>
  @endif
</div>
```

## كيف يعمل التعديل

### 1. تحديد المشروع الآخر:
```php
@php
  // تحديد المشروع الآخر في المقارنة
  $otherProject = null;
  if ($plag->project1_id == $project->id) {
      $otherProject = $plag->project2;  // هذا المشروع كان project1، إذن الآخر هو project2
  } elseif ($plag->project2_id == $project->id) {
      $otherProject = $plag->project1;  // هذا المشروع كان project2، إذن الآخر هو project1
  }
@endphp
```

### 2. عرض اسم المشروع الآخر:
```php
@if($otherProject)
  <p><strong>Compared with:</strong> {{ $otherProject->title }}</p>
@endif
```

## النتيجة النهائية

الآن في التقرير النهائي، قسم Plagiarism سيظهر:

```
Plagiarism
Similarity: 15.5%
Compared with: Student Management System
Report: Open report
```

## المزايا

✅ **وضوح أكبر**: المستخدم يعرف مع أي مشروع تمت المقارنة
✅ **معلومات كاملة**: التقرير أصبح أكثر تفصيلاً ومفيداً
✅ **سهولة الفهم**: لا حاجة للبحث عن معلومات إضافية
✅ **تطبيق بسيط**: تعديلات طفيفة بدون تعقيد

## اختبار التعديل

1. **اذهب إلى أي مشروع له فحص plagiarism**
2. **اضغط على "Final Report"**
3. **تحقق من قسم Plagiarism** - يجب أن يظهر:
   - Similarity percentage
   - **Compared with: [اسم المشروع الآخر]** ← الجديد
   - رابط التقرير

الآن التقرير النهائي أصبح أكثر وضوحاً ومفيداً! 🎯
