<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GithubInsightsService
{
    public function __construct(
        protected ?string $token = null
    ) {
        // سنعمل بدون توكين إن لم يوجد
        $this->token = $this->token ?: env('GITHUB_TOKEN'); // قد يكون null
    }

    protected function client(array $extra = [])
    {
        $headers = array_merge([
            'User-Agent'    => 'GitEvalAI',
            'Accept'        => 'application/vnd.github+json',
        ], $extra);

        // لو فيه توكين هنضيفه، وإلا نشتغل عام (public rate limits)
        $req = Http::withHeaders($headers)->timeout(25);
        if ($this->token) {
            $req = $req->withHeaders(['Authorization' => "Bearer {$this->token}"]);
        }
        return $req;
    }

    /** Repo meta (default_branch وغيرها) — يعمل بدون توكين */
    public function getRepo(string $owner, string $repo): ?array
    {
        $r = $this->client()->get("https://api.github.com/repos/{$owner}/{$repo}");
        return $r->ok() ? $r->json() : null;
    }

    /**
     * Contributor stats (قد ترجع 202 Accepted لأول مرة)
     * تعمل عامة، لكن أحيانًا تحتاج retry خفيف.
     */
    public function getContributorStats(string $owner, string $repo): array
    {
        $r = $this->client()->get("https://api.github.com/repos/{$owner}/{$repo}/stats/contributors");
        if ($r->status() === 202) {
            // GitHub يبني الإحصائيات — انتظر ثم أعد المحاولة مرة أو مرتين
            usleep(700000); // 0.7s
            $r = $this->client()->get("https://api.github.com/repos/{$owner}/{$repo}/stats/contributors");
            if ($r->status() === 202) {
                usleep(900000); // 0.9s
                $r = $this->client()->get("https://api.github.com/repos/{$owner}/{$repo}/stats/contributors");
            }
        }
        return $r->ok() ? ($r->json() ?: []) : [];
    }

    /**
     * commits?author=.. — اعمل paginate بحذر (بدون توكين).
     * لتخفيف الضغط: نوقف عند 300 commit/مؤلف كحد أقصى.
     */
    public function listCommitsByAuthor(string $owner, string $repo, string $author, ?string $since=null, ?string $until=null): array
    {
        $out = [];
        $page = 1;
        $cap  = 300; // سقف أمان
        do {
            $q = ['author' => $author, 'per_page' => 100, 'page' => $page];
            if ($since) $q['since'] = $since;
            if ($until) $q['until'] = $until;

            $r = $this->client()->get("https://api.github.com/repos/{$owner}/{$repo}/commits", $q);
            if (!$r->ok()) break;

            $batch = $r->json() ?: [];
            $out = array_merge($out, $batch);
            $page++;

            if (count($out) >= $cap || count($batch) < 100) break;
        } while (true);

        return array_slice($out, 0, $cap);
    }

    /** Search PRs opened/merged بواسطة user — يعمل بدون توكين لكن بحد أشد */
    public function countPullsByUser(string $owner, string $repo, string $username): array
    {
        // opened
        $q = "repo:{$owner}/{$repo} is:pr author:{$username}";
        $r = $this->client()->get("https://api.github.com/search/issues", ['q' => $q, 'per_page' => 1]);
        $opened = $r->ok() ? ($r->json()['total_count'] ?? 0) : 0;

        // merged
        $q2 = "repo:{$owner}/{$repo} is:pr author:{$username} is:merged";
        $r2 = $this->client()->get("https://api.github.com/search/issues", ['q' => $q2, 'per_page' => 1]);
        $merged = $r2->ok() ? ($r2->json()['total_count'] ?? 0) : 0;

        return ['opened' => $opened, 'merged' => $merged];
    }

    /** Issues opened by user — يعمل بدون توكين */
    public function countIssuesByUser(string $owner, string $repo, string $username): int
    {
        $q = "repo:{$owner}/{$repo} is:issue author:{$username}";
        $r = $this->client()->get("https://api.github.com/search/issues", ['q' => $q, 'per_page' => 1]);
        return $r->ok() ? ($r->json()['total_count'] ?? 0) : 0;
    }

    /**
     * Reviews تقريبية عبر التعليقات على PRs — Search API (بدون توكين يعمل لكن بحد)
     * لو أردت تقليل نداءات البحث، تقدر ترجع 0 أو تتخطى هذا المؤشر.
     */
    public function countReviewsByUser(string $owner, string $repo, string $username): int
    {
        $q = "repo:{$owner}/{$repo} is:pr commenter:{$username}";
        $r = $this->client()->get("https://api.github.com/search/issues", ['q' => $q, 'per_page' => 1]);
        return $r->ok() ? ($r->json()['total_count'] ?? 0) : 0;
    }
}
