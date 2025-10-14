<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Email extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'person_id',
        'email_account_id',
        'subject',
        'body',
        'body_html',
        'to_email',
        'from_email',
        'message_id',
        'headers',
        'attachments',
        'is_incoming',
        'sent_at',
        'received_at',
        'is_processed',
        'status',
        'error_message',
        'user_id',
        'tenant_id'
    ];

    protected $casts = [
        'is_incoming' => 'boolean',
        'is_processed' => 'boolean',
        'headers' => 'array',
        'attachments' => 'array',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function emailAccount()
    {
        return $this->belongsTo(EmailAccount::class);
    }

    // Scopes for filtering
    public function scopeIncoming($query)
    {
        return $query->where('is_incoming', true);
    }

    public function scopeOutgoing($query)
    {
        return $query->where('is_incoming', false);
    }

    public function scopeByPerson($query, $personId)
    {
        return $query->where('person_id', $personId);
    }

    public function scopeByAccount($query, $accountId)
    {
        return $query->where('email_account_id', $accountId);
    }
}
