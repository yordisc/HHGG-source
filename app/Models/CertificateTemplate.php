<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateTemplate extends Model
{
    protected $fillable = [
        'certification_id',
        'slug',
        'name',
        'html_template',
        'css_template',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }

    public static function getDefault(): ?self
    {
        return static::query()
            ->where('is_default', true)
            ->where('certification_id', null)
            ->first();
    }

    public static function forCertification(Certification $certification): ?self
    {
        return static::query()
            ->where('certification_id', $certification->id)
            ->first();
    }
}
