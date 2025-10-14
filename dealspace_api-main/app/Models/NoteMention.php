<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;

class NoteMention extends Model
{
    use BelongsToPrimaryModel;

    /**
     * The table associated with the model.
     */
    protected $table = 'note_mentions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'note_id',
        'mentioned_user_id',
        'position'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'position' => 'integer',
    ];

    public function getRelationshipToPrimaryModel(): string
    {
        return 'note';
    }

    /**
     * Get the note that this mention belongs to.
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    /**
     * Get the user who was mentioned.
     */
    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }

    /**
     * Get the user who was mentioned (alias for better readability).
     */
    public function user(): BelongsTo
    {
        return $this->mentionedUser();
    }

    /**
     * Scope to get mentions for a specific note.
     */
    public function scopeForNote($query, $noteId)
    {
        return $query->where('note_id', $noteId);
    }

    /**
     * Scope to get mentions for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('mentioned_user_id', $userId);
    }

    /**
     * Get mentions with user data loaded.
     */
    public function scopeWithUser($query)
    {
        return $query->with('mentionedUser');
    }
}
