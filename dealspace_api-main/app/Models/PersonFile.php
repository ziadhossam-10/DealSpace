<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;

class PersonFile extends Model
{
    use BelongsToPrimaryModel;

    protected $fillable = [
        'person_id',
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

    public function getRelationshipToPrimaryModel(): string
    {
        return 'person';
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
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
