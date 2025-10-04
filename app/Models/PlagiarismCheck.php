<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PlagiarismCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'project1_id',
        'project2_id',
        'similarity_percentage',
        'matches',
        'matches_count',
        'report_url',
        'moss_task_id',
        'compared_at',
        'duration_ms',
        'report_html_gz',
        'report_path',
    ];
    
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /** المشروع الأول في المقارنة */
    public function project1()
    {
        return $this->belongsTo(Project::class, 'project1_id');
    }

    /** المشروع الثاني في المقارنة */
    public function project2()
    {
        return $this->belongsTo(Project::class, 'project2_id');
    }

    /**
     * استرجاع HTML المضغوط
     */
    public function getReportHtmlAttribute(): ?string
    {
        if ($this->report_html_gz) {
            try {
                return gzdecode(base64_decode($this->report_html_gz));
            } catch (\Throwable $e) {
                Log::warning('Failed to decompress report HTML: ' . $e->getMessage());
                return null;
            }
        }
        
        return null;
    }

    /**
     * حفظ HTML مضغوط
     */
    public function setReportHtmlAttribute(?string $html): void
    {
        if ($html) {
            try {
                $this->attributes['report_html_gz'] = base64_encode(gzencode($html, 9));
            } catch (\Throwable $e) {
                Log::warning('Failed to compress report HTML: ' . $e->getMessage());
                $this->attributes['report_html_gz'] = null;
            }
        } else {
            $this->attributes['report_html_gz'] = null;
        }
    }

    /**
     * الحصول على رابط التقرير (أصلي أو محلي)
     */
    public function getReportUrlAttribute(): ?string
    {
        // إذا كان هناك مسار محلي، استخدمه
        if (isset($this->attributes['report_path']) && $this->attributes['report_path']) {
            return asset('storage/' . $this->attributes['report_path']);
        }
        
        // وإلا استخدم الرابط الأصلي
        return $this->attributes['report_url'] ?? null;
    }

}
