<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsvImportLog extends Model
{
    protected $fillable = [
        'filename',
        'file_size_bytes',
        'total_rows',
        'created_count',
        'updated_count',
        'translation_count',
        'skipped_count',
        'errors',
        'status',
        'preview_rows',
    ];

    protected $casts = [
        'errors' => 'json',
        'preview_rows' => 'json',
    ];

    public function getSuccessfulCount(): int
    {
        return $this->created_count + $this->updated_count + $this->translation_count;
    }

    public function getErrorCount(): int
    {
        return $this->skipped_count;
    }

    public function getSuccessRate(): float
    {
        if ($this->total_rows == 0) {
            return 0;
        }

        return ($this->getSuccessfulCount() / $this->total_rows) * 100;
    }
}
