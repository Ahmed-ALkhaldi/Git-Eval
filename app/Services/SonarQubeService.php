<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SonarQubeService
{
    public function analyzeProject($projectKey){
        $response = Http::get('http://localhost:9000/api/measures/component', [
            'component' => $projectKey,
            'metricKeys' => 'bugs,vulnerabilities,code_smells,coverage,duplicated_lines_density,ncloc,security_hotspots',
        ]);

        if ($response->failed()) {
            return null;
        }

        $metrics = collect($response->json()['component']['measures'] ?? [])
            ->mapWithKeys(fn($m) => [$m['metric'] => $m['value']]);

        return [
            'bugs' => $metrics->get('bugs', 0),
            'vulnerabilities' => $metrics->get('vulnerabilities', 0),
            'code_smells' => $metrics->get('code_smells', 0),
            'coverage' => $metrics->get('coverage', 0),
            'duplicated_lines_density' => $metrics->get('duplicated_lines_density', 0),
            'lines_of_code' => $metrics->get('ncloc', 0), // ✅ سطر الكود
            'security_hotspots' => $metrics->get('security_hotspots', 0), // ✅ النقاط الأمنية
        ];
    }

    public function isSonarQubeRunning(): bool
    {
        try {
            $response = Http::timeout(3)->get('http://localhost:9000/api/system/status');
            return $response->ok() && $response->json('status') === 'UP';
        } catch (\Exception $e) {
            \Log::error('🔴 SonarQube server not reachable: ' . $e->getMessage());
            return false;
        }
    }
}