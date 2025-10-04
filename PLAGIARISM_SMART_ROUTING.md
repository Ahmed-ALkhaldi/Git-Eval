# Plagiarism Smart Routing Feature

## Overview
تم تطوير ميزة التوجيه الذكي لفحص السرقة الأدبية التي تتحقق تلقائياً من وجود تقرير سابق وتوجه المستخدم للصفحة المناسبة.

## How It Works

### 1. **Smart Detection Logic**
عند الضغط على زر "Plagiarism" في أي مشروع:

```php
// البحث عن آخر تقرير سرقة أدبية لهذا المشروع
$latestReport = PlagiarismCheck::where(function($query) use ($id) {
    $query->where('project1_id', $id)
          ->orWhere('project2_id', $id);
})
->with(['project1', 'project2'])
->orderBy('id', 'desc')
->first();
```

### 2. **Conditional Routing**

#### ✅ **If Report Exists** → `plagiarism-result.blade.php`
- يعرض آخر تقرير سرقة أدبية للمشروع
- يظهر أسماء المشاريع المقارنة بوضوح
- يعرض نسبة التشابه والتفاصيل
- يتضمن زر "Run New Check" لإجراء فحص جديد

#### ⚙️ **If No Report** → `plagiarism_select.blade.php`
- يعرض صفحة اختيار المشروع للمقارنة
- يسمح باختيار مشروع آخر للمقارنة معه
- يبدأ عملية فحص السرقة الأدبية الجديدة

## Features

### 🎯 **Automatic Detection**
- يتحقق تلقائياً من وجود تقارير سابقة
- يبحث في كلا الاتجاهين (project1_id و project2_id)
- يأخذ آخر تقرير حسب التاريخ

### 🔄 **Flexible Navigation**
- زر "Back to Accepted Projects" للعودة
- زر "Run New Check" لإجراء فحص جديد
- توجيه ذكي حسب حالة المشروع

### 📊 **Enhanced Display**
- عرض أسماء المشاريع بوضوح
- تصميم محسن مع ألوان جذابة
- معلومات شاملة عن المقارنة

## Technical Implementation

### Controller Changes
```php
// app/Http/Controllers/PlagiarismCheckController.php
public function plagiarism($id)
{
    // ... authentication checks ...
    
    $project = Project::findOrFail($id);
    
    // Search for latest plagiarism report
    $latestReport = PlagiarismCheck::where(function($query) use ($id) {
        $query->where('project1_id', $id)
              ->orWhere('project2_id', $id);
    })
    ->with(['project1', 'project2'])
    ->orderBy('id', 'desc')
    ->first();
    
    // Route based on report existence
    if ($latestReport) {
        return view('supervisor.plagiarism-result', [
            'report'  => $latestReport,
            'matches' => json_decode($latestReport->matches, true),
        ]);
    }
    
    // Show selection page for new check
    $project1      = $project;
    $otherProjects = Project::where('id', '!=', $id)->get();
    
    return view('supervisor.plagiarism_select', compact('project1', 'otherProjects'));
}
```

### View Enhancements
```html
<!-- Project Names Header in plagiarism-result.blade.php -->
<div style="margin-bottom: 16px; padding: 12px; background: rgba(33, 150, 243, 0.1); border-radius: 8px; border: 1px solid rgba(33, 150, 243, 0.2);">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
    <div style="text-align: center; flex: 1;">
      <strong style="color: #1976d2;">Project 1:</strong>
      <div style="font-weight: 600; color: #0d1b2a;">{{ $report->project1->title ?? 'Unknown Project' }}</div>
    </div>
    <div style="text-align: center; flex: 1;">
      <strong style="color: #1976d2;">Project 2:</strong>
      <div style="font-weight: 600; color: #0d1b2a;">{{ $report->project2->title ?? 'Unknown Project' }}</div>
    </div>
  </div>
</div>
```

## User Experience Flow

### 🔍 **First Time Check**
1. User clicks "Plagiarism" button
2. System detects no previous report
3. Shows `plagiarism_select` page
4. User selects project to compare with
5. System runs MOSS analysis
6. Redirects to `plagiarism-result` with new report

### 📋 **Subsequent Checks**
1. User clicks "Plagiarism" button
2. System detects existing report
3. Shows `plagiarism-result` page with latest data
4. User can view results or click "Run New Check"
5. If "Run New Check" clicked, goes to `plagiarism_select`

## Benefits

### ✅ **Improved UX**
- توجيه ذكي حسب حالة المشروع
- عدم الحاجة للبحث عن التقارير السابقة
- واجهة موحدة ومتسقة

### ⚡ **Efficiency**
- تقليل الخطوات المطلوبة
- عرض سريع للنتائج الموجودة
- إمكانية إجراء فحوصات جديدة بسهولة

### 🎨 **Visual Enhancement**
- عرض أسماء المشاريع بوضوح
- تصميم محسن ومتجاوب
- ألوان وأيقونات جذابة

## Future Enhancements

### 🔮 **Potential Improvements**
- إضافة إحصائيات عن عدد الفحوصات السابقة
- إمكانية مقارنة مع عدة مشاريع
- حفظ تفضيلات المشاريع للمقارنة
- إشعارات عند اكتمال الفحص الجديد

---

**تم تطوير هذه الميزة لتحسين تجربة المستخدم وتسهيل عملية فحص السرقة الأدبية في النظام.**
