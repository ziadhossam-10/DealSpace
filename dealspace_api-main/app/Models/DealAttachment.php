<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;

class DealAttachment extends Model
{
    use BelongsToPrimaryModel;

    protected $fillable = [
        'deal_id',
        'name',
        'path',
        'type',
        'size',
        'mime_type',
        'description'
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    protected $appends = ['file_url'];

    /**
     * Get the deal that this attachment belongs to.
     */
    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * Get the relationship to primary model (required by BelongsToPrimaryModel trait).
     */
    public function getRelationshipToPrimaryModel(): string
    {
        return 'deal';
    }

    /**
     * Get the full URL to the file.
     *
     * @return string|null
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->path) {
            return null;
        }
        return storage_path($this->path);
    }

    /**
     * Get human readable file size.
     *
     * @return string
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->size) {
            return 'Unknown';
        }

        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
