<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SonarQubeService
{
    protected string $host;
    protected ?string $token;

    public function __construct()
    {
        $this->host  = rtrim(env('SONARQUBE_HOST', 'http://127.0.0.1:9000'), '/');
        $this->token = env('SONARQUBE_TOKEN');
    }

    public function analyzeProject(string $projectKey): ?array
    {
        $http = $this->http();
        
        // 1) measures
        $m = $http->get($this->host.'/api/measures/component', [
            'component'  => $projectKey,
            'metricKeys' => 'bugs,vulnerabilities,code_smells,coverage,duplicated_lines_density,ncloc,security_hotspots',
        ]);
        if ($m->failed()) return null;

        $measures = collect($m->json('component.measures') ?? [])->pluck('value','metric');

        // 2) quality gate
        $q = $http->get($this->host.'/api/qualitygates/project_status', [
            'projectKey' => $projectKey,
        ]);
        $qg = $q->ok() ? ($q->json('projectStatus.status') ?? null) : null;

        // 3) latest analysis (اختياري)
        $a = $http->get($this->host.'/api/project_analyses/search', [
            'project' => $projectKey, 'ps' => 1, 'p' => 1
        ]);
        $analysisKey = null; $analysisAt = now();
        if ($a->ok()) {
            $ev = collect($a->json('analyses') ?? [])->first();
            if ($ev) {
                $analysisKey = $ev['key'] ?? null;
                $analysisAt  = isset($ev['date']) ? \Carbon\Carbon::parse($ev['date']) : now();
            }
        }

        $f = fn($k,$d=null)=>isset($measures[$k]) ? (float)$measures[$k] : $d;
        $i = fn($k,$d=null)=>isset($measures[$k]) ? (int)$measures[$k]   : $d;

        return [
            'bugs'                     => $i('bugs',0),
            'vulnerabilities'          => $i('vulnerabilities',0),
            'code_smells'              => $i('code_smells',0),
            'coverage'                 => $f('coverage',0.0),
            'duplicated_lines_density' => $f('duplicated_lines_density',0.0),
            'ncloc'                    => $i('ncloc',0),
            'security_hotspots'        => $i('security_hotspots',0),
            'quality_gate'             => $qg,
            'analysis_key'              => $analysisKey,
            'analysis_at'               => $analysisAt,
        ];
    }

    public function listIssues(string $projectKey, int $ps=200): array
    {
        $http = $this->http();
        $p=1; $out=[];
        do {
            $r = $http->get($this->host.'/api/issues/search', [
                'componentKeys'=>$projectKey, 'ps'=>$ps, 'p'=>$p
            ]);
            if ($r->failed()) break;
            $data = $r->json();
            foreach ($data['issues'] ?? [] as $i) {
                $out[] = [
                  'type'     => $i['type'] ?? '',
                  'severity' => $i['severity'] ?? null,
                  'rule'     => $i['rule'] ?? null,
                  'message'  => $i['message'] ?? '',
                  'component'=> $i['component'] ?? '',
                  'line'     => $i['line'] ?? null,
                ];
            }
            $total = $data['total'] ?? count($out);
            $p++;
        } while (count($out) < $total);
        return $out;
    }

    public function isSonarQubeRunning(): bool
    {
        try {
            $http = $this->http();
            $r = $http->timeout(3)->get($this->host.'/api/system/status');
            return $r->ok() && $r->json('status') === 'UP';
        } catch (\Throwable $e) {
            Log::error('SonarQube not reachable: '.$e->getMessage());
            return false;
        }
    }

    public function fetchAndStoreMeasures(string $projectKey, ?string $taskId = null): void
    {
        // 1) (اختياري) جلب analysisId من taskId
        $analysisId = null;
        if ($taskId) {
            try {
                $task = $this->http()->get($this->host.'/api/ce/task', ['id' => $taskId]);
                if ($task->ok()) {
                    $taskData = $task->json();
                    $analysisId = $taskData['task']['analysisId'] ?? null;
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch analysisId from taskId', [
                    'taskId' => $taskId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 2) مقاييس المشروع (لا تحتاج analysisId)
        $metrics = 'bugs,vulnerabilities,code_smells,coverage,duplicated_lines_density,security_hotspots,ncloc';
        $measuresResponse = $this->http()->get($this->host.'/api/measures/component', [
            'component'  => $projectKey,
            'metricKeys' => $metrics,
        ]);

        if ($measuresResponse->failed()) {
            throw new \Exception('Failed to fetch measures: ' . $measuresResponse->body());
        }

        $measures = collect($measuresResponse->json('component.measures') ?? [])->pluck('value','metric');

        // 3) حالة Quality Gate (بـ analysisId إن توفر، وإلا بالـ projectKey)
        $qgParams = $analysisId ? ['analysisId' => $analysisId] : ['projectKey' => $projectKey];
        $qgResponse = $this->http()->get($this->host.'/api/qualitygates/project_status', $qgParams);
        $qualityGate = $qgResponse->ok() ? ($qgResponse->json('projectStatus.status') ?? null) : null;

        // 4) خزّن في DB
        $project = \App\Models\Project::where('sonar_project_key', $projectKey)->first();
        if (!$project) {
            throw new \Exception("Project not found for key: {$projectKey}");
        }

        $f = fn($k,$d=null)=>isset($measures[$k]) ? (float)$measures[$k] : $d;
        $i = fn($k,$d=null)=>isset($measures[$k]) ? (int)$measures[$k]   : $d;

        \App\Models\CodeAnalysisReport::updateOrCreate(
            [
                'project_id' => $project->id,
                'analysis_key' => $analysisId
            ],
            [
                'bugs'                     => $i('bugs',0),
                'vulnerabilities'          => $i('vulnerabilities',0),
                'code_smells'              => $i('code_smells',0),
                'coverage'                 => $f('coverage',0.0),
                'duplicated_lines_density' => $f('duplicated_lines_density',0.0),
                'ncloc'                    => $i('ncloc',0),
                'security_hotspots'        => $i('security_hotspots',0),
                'quality_gate'             => $qualityGate,
                'analysis_at'              => now(),
            ]
        );

        Log::info('SonarQube measures stored successfully', [
            'project_id' => $project->id,
            'project_key' => $projectKey,
            'analysis_id' => $analysisId,
            'task_id' => $taskId
        ]);
    }

    protected function http()
    {
        return $this->token
          ? Http::withBasicAuth($this->token,'')
          : Http::withoutVerifying();
    }
}