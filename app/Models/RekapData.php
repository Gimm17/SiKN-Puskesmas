<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekapData extends Model
{
    protected $table = 'rekap_data';

    protected $fillable = [
        'desa_id',
        'bulan',
        'tahun',
        'lahir_hidup_l',
        'lahir_hidup_p',
        'kn_lengkap_l',
        'kn_lengkap_p',
        'screening_hipotiroid_l',
        'screening_hipotiroid_p',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bulan' => 'integer',
        'tahun' => 'integer',
        'lahir_hidup_l' => 'integer',
        'lahir_hidup_p' => 'integer',
        'kn_lengkap_l' => 'integer',
        'kn_lengkap_p' => 'integer',
        'screening_hipotiroid_l' => 'integer',
        'screening_hipotiroid_p' => 'integer',
    ];

    // ---------- Computed Attributes ----------
    public function getLahirHidupTotalAttribute(): int
    {
        return $this->lahir_hidup_l + $this->lahir_hidup_p;
    }

    public function getKnLengkapTotalAttribute(): int
    {
        return $this->kn_lengkap_l + $this->kn_lengkap_p;
    }

    public function getKnPctLAttribute(): float
    {
        return $this->lahir_hidup_l > 0
            ? round($this->kn_lengkap_l / $this->lahir_hidup_l * 100, 1)
            : 0;
    }

    public function getKnPctPAttribute(): float
    {
        return $this->lahir_hidup_p > 0
            ? round($this->kn_lengkap_p / $this->lahir_hidup_p * 100, 1)
            : 0;
    }

    public function getKnPctTotalAttribute(): float
    {
        $lhTotal = $this->lahir_hidup_total;
        return $lhTotal > 0
            ? round($this->kn_lengkap_total / $lhTotal * 100, 1)
            : 0;
    }

    public function getScreeningHipotiroidTotalAttribute(): int
    {
        return ($this->screening_hipotiroid_l ?? 0) + ($this->screening_hipotiroid_p ?? 0);
    }

    public function getScreeningPctLAttribute(): float
    {
        return $this->lahir_hidup_l > 0
            ? round(($this->screening_hipotiroid_l ?? 0) / $this->lahir_hidup_l * 100, 1)
            : 0;
    }

    public function getScreeningPctPAttribute(): float
    {
        return $this->lahir_hidup_p > 0
            ? round(($this->screening_hipotiroid_p ?? 0) / $this->lahir_hidup_p * 100, 1)
            : 0;
    }

    public function getScreeningPctTotalAttribute(): float
    {
        $lhTotal = $this->lahir_hidup_total;
        return $lhTotal > 0
            ? round($this->screening_hipotiroid_total / $lhTotal * 100, 1)
            : 0;
    }

    // ---------- Relationships ----------
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
