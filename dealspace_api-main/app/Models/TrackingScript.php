<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class TrackingScript extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'name',
        'description',
        'script_key',
        'user_id',
        'domain',
        'is_active',
        'track_all_forms',
        'form_selectors',
        'field_mappings',
        'auto_lead_capture',
        'track_page_views',
        'track_utm_parameters',
        'custom_events',
        'settings',
        'tenant_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'track_all_forms' => 'boolean',
        'form_selectors' => 'array',
        'field_mappings' => 'array',
        'auto_lead_capture' => 'boolean',
        'track_page_views' => 'boolean',
        'track_utm_parameters' => 'boolean',
        'custom_events' => 'array',
        'settings' => 'array',
        'domain' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->script_key)) {
                $model->script_key = 'ds_' . Str::random(32);
            }
        });
    }

    /**
     * Get the user that owns this tracking script
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all events tracked by this script
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'source', 'script_key');
    }

    /**
     * Get the JavaScript tracking code
     */
    public function getTrackingCodeAttribute(): string
    {
        $baseUrl = config('app.url');
        $scriptKey = $this->script_key;

        return "
<!-- Dealspace Pixel - {$this->name} -->
<script>
(function(d,s,id,k){
    var js,fjs=d.getElementsByTagName(s)[0];
    if(d.getElementById(id))return;
    js=d.createElement(s);js.id=id;
    js.src='{$baseUrl}/api/tracking/script.js?key='+k;
    fjs.parentNode.insertBefore(js,fjs);
    window.dealspaceConfig = window.dealspaceConfig || {};
    window.dealspaceConfig.scriptKey = k;
    window.dealspaceConfig.apiUrl = '{$baseUrl}/api/tracking';
}(document,'script','dealspace-pixel','{$scriptKey}'));
</script>
<!-- End Dealspace Pixel -->";
    }

    /**
     * Generate a new script key
     */
    public function regenerateKey(): void
    {
        $this->script_key = 'ds_' . Str::random(32);
        $this->save();
    }

    /**
     * Check if a domain is allowed for this script
     */
    public function isDomainAllowed(string $domain): bool
    {
        if (empty($this->domain)) {
            return true; // No domain restriction
        }

        $allowedDomains = is_array($this->domain) ? $this->domain : [$this->domain];

        foreach ($allowedDomains as $allowedDomain) {
            if ($domain === $allowedDomain || str_ends_with($domain, '.' . $allowedDomain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get default field mappings
     */
    public static function getDefaultFieldMappings(): array
    {
        return [
            'name' => ['name', 'full_name', 'fullname', 'person_name', 'customer_name'],
            'first_name' => ['first_name', 'firstname', 'fname', 'given_name'],
            'last_name' => ['last_name', 'lastname', 'lname', 'family_name', 'surname'],
            'email' => ['email', 'email_address', 'e_mail', 'user_email', 'contact_email'],
            'phone' => ['phone', 'telephone', 'mobile', 'phone_number', 'contact_phone'],
            'message' => ['message', 'comment', 'inquiry', 'description', 'notes'],
            'company' => ['company', 'organization', 'business', 'company_name'],
            'property_interest' => ['property', 'property_id', 'listing', 'property_interest'],
            'budget' => ['budget', 'price_range', 'max_price', 'price_limit'],
        ];
    }
}
