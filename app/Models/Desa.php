<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Desa extends Model
{
    protected $table = 'desa';

    protected $fillable = ['urutan', 'nama'];

    public function rekapData(): HasMany
    {
        return $this->hasMany(RekapData::class);
    }

    public static function ordered()
    {
        return static::orderBy('urutan')->get();
    }
}
