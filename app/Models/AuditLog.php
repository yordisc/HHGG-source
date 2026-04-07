<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'action',
        'entity',
        'entity_id',
        'entity_name',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public static function log(
        string $action,
        string $entity,
        ?int $entityId = null,
        ?string $entityName = null,
        ?array $changes = null
    ): void {
        static::create([
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByEntity($query, string $entity)
    {
        return $query->where('entity', $entity);
    }
}
