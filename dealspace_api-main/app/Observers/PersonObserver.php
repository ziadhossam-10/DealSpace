<?php

namespace App\Observers;

use App\Models\Person;

class PersonObserver
{
    /**
     * Handle the Person "saving" event.
     */
    public function saving(Person $person): void
    {
        // Set initial_assigned_user_id if not already set
        if (is_null($person->initial_assigned_user_id) && $person->assigned_user_id) {
            $person->initial_assigned_user_id = $person->assigned_user_id;
        }

        // Prevent updates to initial_assigned_user_id once it's set
        if (
            $person->isDirty('initial_assigned_user_id') &&
            !is_null($person->getOriginal('initial_assigned_user_id'))
        ) {
            $person->initial_assigned_user_id = $person->getOriginal('initial_assigned_user_id');
        }
    }
}
