<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Jobs\SyncSonarAnalysisJob;

class SonarWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1) إعدادات
        $secret   = config('services.sonar.webhook_secret');
        $raw      = $request->getContent();
        $provided = $request->header('X-Sonar-Webhook-HMAC-SHA256'); // SonarQube 25.x
        $ua       = $request->userAgent() ?? '';
        $ip       = $request->ip();

        // 2) تحديد ما إذا كنا في وضع "محلي مُخفف"
        $isLocalEnv   = app()->environment('local');
        $isLocalIP    = in_array($ip, ['127.0.0.1', '::1'], true);
        $uaLooksOk    = stripos($ua, 'SonarQube/') === 0;

        // 3) سياسة التحقق:
        // - إن كان هناك secret: تحقق بالتوقيع (إنتاج/ستيجينغ غالباً)
        // - إن لم يوجد secret: اسمح فقط إذا (بيئة محلية + IP محلي + UA صحيح)
        if (!empty($secret)) {
            $calc = hash_hmac('sha256', $raw, $secret);
            if (!$provided || !hash_equals(strtolower($calc), strtolower($provided))) {
                Log::warning('Invalid Sonar webhook signature', [
                    'ip' => $ip,
                    'user_agent' => $ua,
                    'provided_signature' => $provided,
                ]);
                return response('invalid signature', 401);
            }
        } else {
            if (!($isLocalEnv && $isLocalIP && $uaLooksOk)) {
                // بدون توقيع، لا نقبل إلا محليًا وبمصدر موثوق
                Log::warning('Rejected unsigned Sonar webhook (not local or bad UA/IP)', [
                    'env' => app()->environment(),
                    'ip'  => $ip,
                    'ua'  => $ua,
                ]);
                return response('signature required', 401);
            }
        }

        // 4) تحقق بنية الحمولة الأساسي
        //    SonarQube يرسل JSON مثل:
        //    { "project": {"key": "...", "name": "..."}, "status": "SUCCESS", "taskId": "...", "analysedAt": "..." }
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            return response('invalid json', 400);
        }

        // تحقق مفاتيح مهمة - استخدام الحقول الصحيحة حسب وثائق SonarQube
        $projectKey = $payload['project']['key'] ?? null;
        $status     = $payload['status']        ?? null;
        $taskId     = $payload['taskId']        ?? null;   // الصحيح حسب الدوك
        $analysedAt = $payload['analysedAt']    ?? null;
        $qg         = $payload['qualityGate']['status'] ?? null; // مفيد لو تريد تخزين حالة الـ QG

        if (!$projectKey || !$status || !$taskId) {
            Log::warning('Sonar webhook payload missing keys (expect taskId/status/project.key)', compact('projectKey','status','taskId'));
            return response('bad payload', 400);
        }

        // (اختياري لكنه مفيد): التحقق أن الـ projectKey معروف لديك
        // يمكنك تفعيل هذا إن كان لديك عمود للمفتاح:
        // $knownKeys = \App\Models\Project::pluck('sonar_project_key')->filter()->all();
        // if (!in_array($projectKey, $knownKeys, true)) {
        //     return response('unknown project', 400);
        // }

        Log::info('Sonar webhook accepted', compact('projectKey','status','taskId','analysedAt','qg'));

        // 5) نفّذ منطقك عند النجاح فقط (تخزين المقاييس/النتائج)
        if ($status === 'SUCCESS') {
            try {
                // استخدم الخدمة المحدثة لسحب وتخزين المقاييس
                app(\App\Services\SonarQubeService::class)
                    ->fetchAndStoreMeasures($projectKey, $taskId);
                
                Log::info('Sonar webhook measures fetched and stored', [
                    'projectKey' => $projectKey,
                    'taskId'     => $taskId,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to fetch/store Sonar measures', [
                    'projectKey' => $projectKey,
                    'taskId'     => $taskId,
                    'error'      => $e->getMessage(),
                ]);
                // أعد 200 حتى لا يُعيد SonarQube إرسال الويبهوك بلا توقف، لكن سجّل الخطأ
                return response()->noContent();
            }
        }

        return response()->noContent(); // 204
    }
}