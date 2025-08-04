<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class MossService
{
    private string $batchScript;

    public function __construct()
    {
        // 📌 مسار سكربت الباتش
        $this->batchScript = base_path('resources/moss/compare_moss.bat');
    }

    /**
     * ✅ مقارنة مشروعين باستخدام Batch + MOSS
     * الآن يتم تمرير المجلدين كاملين بين علامات تنصيص
     */
    public function compareProjects(array $files1, array $files2): ?array
    {
        // 📌 جلب مجلد المشروعين (المسارات الكاملة)
        $project1Dir = dirname($files1[0]);
        $project2Dir = dirname($files2[0]);

        if (!file_exists($this->batchScript)) {
            Log::error("❌ Batch script not found at ".$this->batchScript);
            return null;
        }

        // ✅ بناء الأمر مع وضع علامات تنصيص للمسارات
        $batPath = $this->batchScript;
        $command = [
            "cmd",
            "/c",
            "\"$batPath\"",
            "\"$project1Dir\"",
            "\"$project2Dir\""
        ];

        Log::info("🚀 Executing MOSS Batch: ".implode(' ', $command));

        // ✅ تشغيل الأمر
        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::error("❌ MOSS Batch Error: ".$process->getErrorOutput());
            return null;
        }

        $output = $process->getOutput();
        Log::info("✅ Batch Output: ".$output);

        // ✅ استخراج رابط تقرير MOSS
        if (!preg_match('/http[s]?:\/\/\S+/', $output, $matches)) {
            Log::error("❌ No MOSS report URL found in batch output.");
            return null;
        }

        $reportUrl = $matches[0];
        Log::info("📄 Report URL: ".$reportUrl);

        // ✅ جلب HTML التقرير وتحليله
        $html = @file_get_contents($reportUrl);
        if (!$html) {
            Log::error("❌ Failed to fetch MOSS report.");
            return null;
        }

        return $this->parseMossReport($html);
    }

    /**
     * ✅ تحليل HTML الناتج من تقرير MOSS
     */
    private function parseMossReport(string $html): array
    {
        $matches = [];
        preg_match_all('/<A HREF="([^"]+)">([^<]+)<\/A>\s+\((\d+)%\)/', $html, $rows, PREG_SET_ORDER);

        $totalSimilarity = 0;
        $count = count($rows);

        foreach ($rows as $row) {
            $matches[] = [
                'file' => $row[2],
                'link' => $row[1],
                'percentage' => (int) $row[3]
            ];
            $totalSimilarity += (int) $row[3];
        }

        return [
            'average_similarity' => $count ? $totalSimilarity / $count : 0,
            'details' => $matches
        ];
    }
}
