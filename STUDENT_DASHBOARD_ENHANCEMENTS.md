# Student Dashboard Enhancements

## نظرة عامة
تم إضافة ميزات جديدة لواجهة الطالب لتحسين تجربة المستخدم وإدارة المشاريع.

## الميزات المضافة

### 1. تعديل الملف الشخصي
- **الوصف**: إمكانية تعديل البيانات الشخصية للطالب
- **الحقول المتاحة**:
  - البريد الإلكتروني (Email)
  - كلمة المرور الجديدة (Password)
  - اسم الجامعة (University Name)
  - الرقم الجامعي (Student Number)

#### الملفات المحدثة:
- `resources/views/student/studentDashboard.blade.php` - إضافة زر تعديل الملف الشخصي
- `app/Http/Controllers/StudentProfileController.php` - Controller جديد لإدارة الملف الشخصي
- `routes/web.php` - إضافة Routes جديدة

#### الكود المطبق:
```php
// Routes
Route::get('/student/profile/edit', [StudentProfileController::class, 'edit'])
    ->name('student.profile.edit');
Route::put('/student/profile/update', [StudentProfileController::class, 'update'])
    ->name('student.profile.update');

// Controller
public function update(Request $request)
{
    $user = Auth::user();
    $student = $user->student;

    $request->validate([
        'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
        'password' => 'nullable|min:8|confirmed',
        'university_name' => 'nullable|string|max:255',
        'university_num' => 'nullable|string|max:50',
    ]);

    // تحديث بيانات المستخدم والطالب
    \App\Models\User::where('id', $user->id)->update($updateData);
    $student->update([...]);
}
```

### 2. إعادة تحميل المشروع
- **الوصف**: إمكانية إعادة تحميل مشروع من GitHub مع حذف الملفات القديمة
- **الميزات**:
  - حذف الملفات القديمة (مجلد المشروع + ملف ZIP)
  - تحميل المشروع الجديد من GitHub
  - تحديث رابط المستودع في قاعدة البيانات
  - تحقق من صحة رابط GitHub

#### الملفات المحدثة:
- `resources/views/student/studentDashboard.blade.php` - إضافة زر إعادة تحميل المشروع
- `app/Http/Controllers/ProjectController.php` - إضافة دالة `reload`
- `routes/web.php` - إضافة Route جديد

#### الكود المطبق:
```php
// Route
Route::put('/projects/{project}/reload', [ProjectController::class, 'reload'])
    ->name('projects.reload');

// Controller Methods
public function reload(Request $request, Project $project)
{
    // التحقق من أن الطالب هو مالك المشروع
    abort_unless($student && $project->owner_student_id === $student->id, 403);
    
    // حذف الملفات القديمة
    $this->deleteProjectFiles($project->id);
    
    // تحميل المشروع الجديد
    $this->downloadProjectFromGitHub($request->github_url, $project->id);
    
    // تحديث رابط المستودع
    if ($project->repository) {
        $project->repository->update(['github_url' => $request->github_url]);
    }
}

private function deleteProjectFiles(int $projectId): void
{
    $projectDir = storage_path("app/projects/project_{$projectId}");
    $zipPath = storage_path("app/private/zips/project_{$projectId}.zip");
    
    if (is_dir($projectDir)) {
        File::deleteDirectory($projectDir);
    }
    
    if (file_exists($zipPath)) {
        unlink($zipPath);
    }
}

private function downloadProjectFromGitHub(string $githubUrl, int $projectId): void
{
    // تحويل URL إلى API URL
    $apiUrl = str_replace('https://github.com/', 'https://api.github.com/repos/', $githubUrl);
    $apiUrl .= '/zipball/main';
    
    // تحميل الملف
    $response = Http::timeout(300)->get($apiUrl);
    
    // حفظ الملف المضغوط
    $zipPath = storage_path("app/private/zips/project_{$projectId}.zip");
    file_put_contents($zipPath, $response->body());
    
    // فك الضغط
    $this->extractProject($zipPath, $projectId);
}
```

## التحسينات في واجهة المستخدم

### 1. عرض البيانات الإضافية
```blade
<div class="user-info">
    <p><strong>Email :</strong> {{ $u?->email ?? '-' }}</p>
    <p><strong>Role  :</strong> {{ $u?->role ?? 'student' }}</p>
    @if($student)
        <p><strong>University :</strong> {{ $student->university_name ?? '-' }}</p>
        <p><strong>Student Number :</strong> {{ $student->university_num ?? '-' }}</p>
    @endif
    <div class="action-buttons" style="margin-top: 15px;">
        <button class="btn" id="editProfileBtn">✏️ Edit Profile</button>
    </div>
</div>
```

### 2. النوافذ المنبثقة الجديدة
- **نافذة تعديل الملف الشخصي**: تحتوي على حقول الإدخال مع التحقق من صحة البيانات
- **نافذة إعادة تحميل المشروع**: تحتوي على تحذير وحقل إدخال رابط GitHub

### 3. JavaScript المحسن
```javascript
// Edit Profile Button
if (editProfileBtn) {
    editProfileBtn.addEventListener('click', () => {
        editProfilePopup.classList.add('open');
    });
}

// Form validation for edit profile
const editProfileForm = document.getElementById('editProfileForm');
if (editProfileForm) {
    editProfileForm.addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const passwordConfirmation = document.getElementById('password_confirmation').value;
        
        if (password && password !== passwordConfirmation) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
    });
}

// Global functions for modal management
function openReloadProjectModal(projectId) {
    const form = document.getElementById('reloadProjectForm');
    if (form) {
        form.action = `{{ url('/projects') }}/${projectId}/reload`;
    }
    document.getElementById('reloadProjectPopup').classList.add('open');
}
```

## الأمان والتحقق

### 1. التحقق من الصلاحيات
- **تعديل الملف الشخصي**: فقط الطالب المسجل يمكنه تعديل ملفه الشخصي
- **إعادة تحميل المشروع**: فقط مالك المشروع يمكنه إعادة تحميله

### 2. التحقق من صحة البيانات
- **البريد الإلكتروني**: فريد ومتطلب
- **كلمة المرور**: اختيارية، لكن إذا تم إدخالها يجب أن تكون 8 أحرف على الأقل ومطابقة للتأكيد
- **رابط GitHub**: يجب أن يكون رابط صحيح لـ GitHub

### 3. معالجة الأخطاء
- **تسجيل الأخطاء**: جميع العمليات مسجلة في Laravel Log
- **رسائل الخطأ**: رسائل واضحة للمستخدم
- **التحقق من الاستجابة**: التحقق من نجاح تحميل الملفات من GitHub

## الفوائد

### 1. تحسين تجربة المستخدم
- ✅ **سهولة التعديل**: تعديل البيانات الشخصية بسهولة
- ✅ **إدارة المشاريع**: إعادة تحميل المشاريع عند الحاجة
- ✅ **واجهة بديهية**: أزرار واضحة ونوافذ منبثقة منظمة

### 2. تحسين الأداء
- ✅ **تنظيف الملفات**: حذف الملفات القديمة لتوفير المساحة
- ✅ **تحميل محدث**: الحصول على أحدث إصدار من المشروع
- ✅ **إدارة أفضل**: تحديث رابط المستودع في قاعدة البيانات

### 3. الأمان والموثوقية
- ✅ **التحقق من الصلاحيات**: فقط المالك يمكنه إدارة مشروعه
- ✅ **التحقق من البيانات**: التأكد من صحة البيانات المدخلة
- ✅ **معالجة الأخطاء**: التعامل مع الأخطاء بشكل مناسب

## الاختبار

### 1. اختبار تعديل الملف الشخصي
```bash
# اختبار تحديث البريد الإلكتروني
curl -X PUT /student/profile/update \
  -d "email=new@example.com" \
  -H "Content-Type: application/x-www-form-urlencoded"

# اختبار تحديث كلمة المرور
curl -X PUT /student/profile/update \
  -d "password=newpassword&password_confirmation=newpassword" \
  -H "Content-Type: application/x-www-form-urlencoded"
```

### 2. اختبار إعادة تحميل المشروع
```bash
# اختبار إعادة تحميل مشروع
curl -X PUT /projects/1/reload \
  -d "github_url=https://github.com/username/repository" \
  -H "Content-Type: application/x-www-form-urlencoded"
```

## الخطوات المستقبلية

### 1. تحسينات مقترحة
- **إشعارات**: إضافة إشعارات عند نجاح العمليات
- **تاريخ التعديلات**: تتبع تاريخ التعديلات
- **النسخ الاحتياطية**: إنشاء نسخ احتياطية قبل الحذف

### 2. ميزات إضافية
- **تحميل متعدد**: إمكانية تحميل عدة مشاريع
- **جدولة**: جدولة إعادة التحميل
- **إحصائيات**: إحصائيات استخدام الميزات

## الخلاصة

تم تطبيق الميزات الجديدة بنجاح مع:
- ✅ **واجهة مستخدم محسنة** مع أزرار واضحة ونوافذ منبثقة
- ✅ **أمان قوي** مع التحقق من الصلاحيات والبيانات
- ✅ **كود نظيف** مع معالجة مناسبة للأخطاء
- ✅ **توثيق شامل** للميزات والكود المطبق

هذه الميزات تحسن بشكل كبير تجربة الطالب في إدارة مشروعه وملفه الشخصي.
