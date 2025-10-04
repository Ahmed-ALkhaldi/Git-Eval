<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Models\Student;

class FileController extends Controller
{
    /**
     * عرض شهادة القيد للطالب
     * يمكن للمشرفين والمدراء فقط عرض شهادات القيد
     */
    public function viewEnrollmentCertificate($studentId)
    {
        $user = Auth::user();
        
        // التحقق من الصلاحيات - المشرفين والمدراء فقط
        if (!$user || !in_array($user->role, ['supervisor', 'admin'])) {
            abort(403, 'Access denied. Supervisors and admins only.');
        }

        $student = Student::findOrFail($studentId);
        
        // التحقق من وجود شهادة القيد
        if (!$student->enrollment_certificate_path) {
            abort(404, 'No enrollment certificate found for this student.');
        }

        // التحقق من وجود الملف فعلياً
        if (!Storage::disk('local')->exists($student->enrollment_certificate_path)) {
            abort(404, 'Enrollment certificate file not found.');
        }

        // الحصول على الملف
        $filePath = Storage::disk('local')->path($student->enrollment_certificate_path);
        $fileName = basename($student->enrollment_certificate_path);
        
        // تحديد نوع الملف
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $mimeType = match(strtolower($extension)) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream'
        };

        // إرجاع الملف للعرض في المتصفح
        return Response::file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"'
        ]);
    }

    /**
     * تحميل شهادة القيد
     */
    public function downloadEnrollmentCertificate($studentId)
    {
        $user = Auth::user();
        
        // التحقق من الصلاحيات
        if (!$user || !in_array($user->role, ['supervisor', 'admin'])) {
            abort(403, 'Access denied. Supervisors and admins only.');
        }

        $student = Student::findOrFail($studentId);
        
        if (!$student->enrollment_certificate_path) {
            abort(404, 'No enrollment certificate found for this student.');
        }

        if (!Storage::disk('local')->exists($student->enrollment_certificate_path)) {
            abort(404, 'Enrollment certificate file not found.');
        }

        $fileName = 'enrollment_certificate_' . $student->university_num . '_' . $student->id . '.' . 
                   pathinfo($student->enrollment_certificate_path, PATHINFO_EXTENSION);

        $filePath = Storage::disk('local')->path($student->enrollment_certificate_path);
        
        return Response::download($filePath, $fileName);
    }
}