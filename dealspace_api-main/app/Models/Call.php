<?php

namespace App\Models;

use App\Enums\OutcomeOptionsEnum;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Call extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'person_id',
        'phone',
        'is_incoming',
        'note',
        'outcome',
        'duration',
        'to_number',
        'from_number',
        'user_id',
        'recording_url',
        'recording_sid',
        'twilio_call_sid',
        'status',
    ];

    protected $casts = [
        'is_incoming' => 'boolean',
        'outcome' => OutcomeOptionsEnum::class,
        'duration' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with Person model
     */
    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Relationship with User model (agent)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for incoming calls
     */
    public function scopeIncoming($query)
    {
        return $query->where('is_incoming', true);
    }

    /**
     * Scope for outgoing calls
     */
    public function scopeOutgoing($query)
    {
        return $query->where('is_incoming', false);
    }

    /**
     * Scope for calls by agent
     */
    public function scopeByAgent($query, $agentId)
    {
        return $query->where('user_id', $agentId);
    }

    /**
     * Scope for calls by person
     */
    public function scopeByPerson($query, $personId)
    {
        return $query->where('person_id', $personId);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->duration) {
            return '00:00';
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Check if call has recording
     */
    public function hasRecording()
    {
        return !empty($this->recording_url);
    }

    /**
     * Check if call is completed
     */
    public function isCompleted()
    {
        return in_array($this->status, ['completed', 'busy', 'no-answer', 'failed', 'canceled']);
    }
}
