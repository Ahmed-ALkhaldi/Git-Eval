<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use App\Models\PlagiarismCheck;

class MossService
{

    private function mossPortOpen(int $timeout = 3): bool {
        $fp = @fsockopen('moss.stanford.edu', 7690, $errno, $errstr, $timeout);
        if ($fp) { fclose($fp); return true; }
        Log::warning("⛔ Port 7690 closed in PHP context: errno={$errno} err={$errstr}");
        return false;
    }


    /**
     * ✅ مقارنة مشروعين باستخدام سكربت batch الحالي
     * - يقرأ الرابط من resources/moss/moss_result.txt
     * - يعمل من داخل resources/moss
     * - يحفظ النتائج في قاعدة البيانات
     */
    public function compareProjects(string $project1Dir, string $project2Dir, ?int $project1Id = null, ?int $project2Id = null): ?array
    {
        $t0 = hrtime(true); // بداية تتبع الوقت
        
        $workdir    = base_path('resources/moss');
        $batch      = $workdir . DIRECTORY_SEPARATOR . 'compare_moss.bat';
        $resultTxt  = $workdir . DIRECTORY_SEPARATOR . 'moss_result.txt';
        $outLog     = $workdir . DIRECTORY_SEPARATOR . 'moss_output.log';

        if (!file_exists($batch)) {
            Log::error("❌ Batch script not found at $batch");
            return null;
        }

        @unlink($resultTxt);
        @unlink($outLog);

        // 1) اكتب runner قصير
        $runner = $workdir . DIRECTORY_SEPARATOR . ('run_compare_moss_' . date('Ymd_His') . '_' . mt_rand(1000,9999) . '.cmd');
        $cmdContent = <<<BAT
        @echo off
        cd /d "{$workdir}"
        call "{$batch}" "{$project1Dir}" "{$project2Dir}"
        BAT;
        if (file_put_contents($runner, $cmdContent) === false) {
            Log::error("❌ Failed to write runner file: $runner");
            return null;
        }

        // 2) وقت في المستقبل (+1 دقيقة) لتفادي تحذير /ST
        $when = (new \DateTime('+1 minute'))->format('H:i');

        $taskName = 'CompareMoss_' . date('Ymd_His') . '_' . mt_rand(1000,9999);

        // 3) إنشاء المهمة (بدون /RL HIGHEST لتقليل الحاجة للامتيازات)
        $createArgs = [
            'schtasks','/create',
            '/tn', $taskName,
            '/sc','ONCE',
            '/st', $when,
            '/tr', $runner,
            '/F'
        ];
        $create = new \Symfony\Component\Process\Process($createArgs, $workdir);
        $create->setTimeout(60);
        $create->run();
        Log::info('🛠 schtasks /create output: '.$create->getOutput());
        if (!$create->isSuccessful()) {
            Log::error('❌ schtasks /create error: '.$create->getErrorOutput());
            @unlink($runner);
            return null;
        }

        try {
            // 4) شغّل المهمة فورًا
            $run = new \Symfony\Component\Process\Process(['schtasks','/run','/tn',$taskName], $workdir);
            $run->setTimeout(30);
            $run->run();
            Log::info('▶️ schtasks /run output: '.$run->getOutput());
            if (!$run->isSuccessful()) {
                Log::error('❌ schtasks /run error: '.$run->getErrorOutput());
                return null;
            }

            // 5) انتظر إنتاج moss_result.txt بحد أقصى 150 ثانية
            $deadline = time() + 150;
            $stableCount = 0; $lastSize = -1;
            while (time() < $deadline) {
                if (file_exists($resultTxt)) {
                    clearstatcache(true, $resultTxt);
                    $size = filesize($resultTxt);
                    if ($size > 0 && $size === $lastSize) {
                        $stableCount++;
                        if ($stableCount >= 2) break;
                    } else {
                        $stableCount = 0;
                    }
                    $lastSize = $size;
                }
                usleep(300000);
            }

            if (!file_exists($resultTxt)) {
                Log::error('❌ No result file produced (moss_result.txt) by scheduled task.');
                if (file_exists($outLog)) {
                    Log::warning("📝 moss_output.log (tail): " . mb_substr(@file_get_contents($outLog), -2000));
                }
                return null;
            }

            $resultText = trim((string)@file_get_contents($resultTxt));
            Log::info("🔗 result.txt: " . mb_substr($resultText, 0, 500));

            if (!preg_match('/https?:\/\/\S+/', $resultText, $m)) {
                Log::error("❌ No URL found in moss_result.txt.");
                if (file_exists($outLog)) {
                    Log::warning("📝 moss_output.log (tail): " . mb_substr(@file_get_contents($outLog), -2000));
                }
                return null;
            }

            $reportUrl = $m[0];
            Log::info("📄 Report URL: {$reportUrl}");

            $html = @file_get_contents($reportUrl);
            if (!$html) {
                Log::error("❌ Failed to fetch MOSS report HTML from {$reportUrl}");
                return null;
            }

            // تحليل النتائج
            $parsed = $this->parseMossReport($html);
            $resultArray = [
                'average_similarity' => round($parsed['average_similarity'] ?? 0, 2),
                'details'            => $parsed['details'] ?? [],
                'report_url'         => $reportUrl,
            ];

            // حفظ النتائج في قاعدة البيانات إذا توفرت معرفات المشاريع
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
                            'report_html_gz'       => base64_encode(gzencode($html, 9)), // تخزين HTML مضغوط
                        ]);
                    });
                    
                    Log::info('✅ Plagiarism check results saved to database', [
                        'project1_id' => $project1Id,
                        'project2_id' => $project2Id,
                        'similarity'  => $resultArray['average_similarity'],
                        'matches_count' => count($resultArray['details'])
                    ]);
                } catch (\Throwable $e) {
                    Log::error('❌ Failed to save plagiarism check results: ' . $e->getMessage());
                    // لا نوقف العملية إذا فشل الحفظ
                }
            }

            return $resultArray;

        } finally {
            // 1) احذف مهمة الـ Scheduler والـ runner المؤقت
            try {
                $del = new \Symfony\Component\Process\Process(['schtasks','/delete','/tn',$taskName,'/f'], $workdir);
                $del->setTimeout(30);
                $del->run();
                Log::info('🧹 schtasks /delete output: '.$del->getOutput());
            } catch (\Throwable $e) {
                Log::warning('⚠️ Failed to delete scheduled task: '.$e->getMessage());
            }
            @unlink($runner);

            // 2) نظافة ملفات ناتجة عن المقارنة في مجلد resources/moss
            //    نحذف فقط الملفات المعروفة كي لا نمس ملفاتك الثابتة (مثل moss.pl و compare_moss.bat).
            $toDelete = [
                $workdir . DIRECTORY_SEPARATOR . 'moss_result.txt',
                $workdir . DIRECTORY_SEPARATOR . 'moss_output.log',
                $workdir . DIRECTORY_SEPARATOR . 'merged_project1.php',
                $workdir . DIRECTORY_SEPARATOR . 'merged_project2.php',
            ];

            // لو في نسخ أخرى أو أنماط مشابهة لاحقًا:
            foreach (glob($workdir . DIRECTORY_SEPARATOR . 'merged_project*.php') ?: [] as $f) {
                $toDelete[] = $f;
            }

            foreach (array_unique($toDelete) as $f) {
                @is_file($f) && @unlink($f);
            }
        }
    }




    /**
     * ✅ تحليل HTML الناتج من تقرير MOSS
     */
    private function parseMossReport(string $html): array
{
        $matches = [];

        // 1) نمط الصفوف الجدولية: <TR><TD><A HREF="...">file1 (77%)</A></TD> <TD>... file2 (77%)</A></TD> <TD ALIGN=right>3145</TD></TR>
        if (preg_match_all(
            '/<tr>\s*<td>\s*<a href="([^"]+)">([^<]+)\s*\((\d+)%\)\s*<\/a>\s*<\/td>\s*<td>\s*<a href="([^"]+)">([^<]+)\s*\((\d+)%\)\s*<\/a>\s*<\/td>\s*<td[^>]*>\s*(\d+)\s*<\/td>\s*<\/tr>/i',
            $html, $rows, PREG_SET_ORDER
        )) {
            foreach ($rows as $r) {
                $matches[] = [
                    'file1'      => $r[2],
                    'file1_link' => $r[1],
                    'p1'         => (int)$r[3],
                    'file2'      => $r[5],
                    'file2_link' => $r[4],
                    'p2'         => (int)$r[6],
                    'lines'      => (int)$r[7],
                ];
            }
        }

        // 2) نمط الروابط القديمة: <A HREF="...">file (77%)</A>  (قد تأتي أزواج في سطور متتالية)
        if (empty($matches)) {
            if (preg_match_all('/<A HREF="([^"]+)">([^<]+)\s*\((\d+)%\)\s*<\/A>/i', $html, $links, PREG_SET_ORDER)) {
                // حوّل كل زوج متتالٍ إلى صف واحد
                for ($i = 0; $i + 1 < count($links); $i += 2) {
                    $matches[] = [
                        'file1'      => $links[$i][2],
                        'file1_link' => $links[$i][1],
                        'p1'         => (int)$links[$i][3],
                        'file2'      => $links[$i+1][2],
                        'file2_link' => $links[$i+1][1],
                        'p2'         => (int)$links[$i+1][3],
                        'lines'      => null,
                    ];
                }
            }
        }

        // حساب متوسط تقريبي (متوسط p1 و p2 لكل صف ثم متوسط عام)
        $avg = 0; $n = 0;
        foreach ($matches as $m) {
            if (isset($m['p1'], $m['p2'])) {
                $avg += ($m['p1'] + $m['p2']) / 2;
                $n++;
            }
        }
        $average = $n ? $avg / $n : 0;

        return [
            'average_similarity' => $average,
            'details'            => $matches,
        ];
    }

}
