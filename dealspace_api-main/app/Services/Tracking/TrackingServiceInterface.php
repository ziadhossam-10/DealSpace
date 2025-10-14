<?php

namespace App\Services\Tracking;

use App\Models\Event;

interface TrackingServiceInterface
{
    /**
     * Track a page view event
     *
     * @param string $scriptKey
     * @param array $data
     * @return Event
     */
    public function trackPageView(string $scriptKey, array $data): Event;

    /**
     * Track a form submission event
     *
     * @param string $scriptKey
     * @param array $data
     * @return Event
     */
    public function trackFormSubmission(string $scriptKey, array $data): Event;

    /**
     * Track a custom event
     *
     * @param string $scriptKey
     * @param array $data
     * @return Event
     */
    public function trackCustomEvent(string $scriptKey, array $data): Event;
}
