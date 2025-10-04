<?php

namespace App\Jobs;

use App\Models\{Project, CodeAnalysisReport, CodeAnalysisResult};
use App\Services\SonarQubeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\Log;

class SyncSonarAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $projectKey,
        public ?string $taskId = null
    ) {}

    public function handle(SonarQubeService $sonar)
    {
        Log::info('Starting SonarQube sync', [
            'project_key' => $this->projectKey,
            'task_id' => $this->taskId
        ]);

        // جد مشروع Laravel الذي يطابق projectKey
        $project = Project::where('sonar_project_key', $this->projectKey)->first();
        if (!$project) {
            Log::warning('Project not found for SonarQube key', ['project_key' => $this->projectKey]);
            return;
        }

        // (اختياري) انتظر اكتمال التحليل إن وصل الويبهوك مبكرًا
        // $sonar->waitForCeTask($this->taskId);

        // 1) المقاييس - استخدام analysis_key للحفاظ على التاريخ
        $metrics = $sonar->analyzeProject($this->projectKey);
        if ($metrics) {
            $report = CodeAnalysisReport::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'analysis_key' => $metrics['analysis_key'] ?? null
                ],
                [
                    'bugs'                     => $metrics['bugs'] ?? 0,
                    'vulnerabilities'          => $metrics['vulnerabilities'] ?? 0,
                    'code_smells'              => $metrics['code_smells'] ?? 0,
                    'coverage'                 => $metrics['coverage'] ?? 0,
                    'duplicated_lines_density' => $metrics['duplicated_lines_density'] ?? 0,
                    'ncloc'                    => $metrics['ncloc'] ?? 0,
                    'security_hotspots'        => $metrics['security_hotspots'] ?? 0,
                    'quality_gate'             => $metrics['quality_gate'] ?? null,
                    'analysis_at'              => $metrics['analysis_at'] ?? now(),
                ]
            );

            Log::info('Code analysis report updated', [
                'project_id' => $project->id,
                'report_id' => $report->id
            ]);
        }

        // 2) القضايا التفصيلية (اختياري)
        try {
            $issues = $sonar->listIssues($this->projectKey, 200);
            if (!empty($issues)) {
                // احذف آخر قضايا محلية (اختياري) أو نفّذ upsert idempotent
                CodeAnalysisResult::where('project_id', $project->id)->delete();

                foreach ($issues as $i) {
                    CodeAnalysisResult::create([
                        'project_id' => $project->id,
                        'issue_type' => $i['type'] ?? 'CODE_SMELL',
                        'message'    => $i['message'] ?? '',
                        'component'  => $i['component'] ?? '',
                        'line'       => $i['line'] ?? null,
                        'severity'   => $i['severity'] ?? null,
                        'rule'       => $i['rule'] ?? null,
                    ]);
                }

                Log::info('Code analysis issues synced', [
                    'project_id' => $project->id,
                    'issues_count' => count($issues)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync issues', [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}