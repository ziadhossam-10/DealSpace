<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\CalendarEvent;
use App\Models\Call;
use App\Models\Email;
use App\Models\Event;
use App\Models\Note;
use App\Models\Person;
use App\Models\Task;
use App\Models\TextMessage;
use App\Observers\AppointmentObserver;
use App\Observers\CalendarEventObserver;
use App\Observers\CallObserver;
use App\Observers\EmailObserver;
use App\Observers\EventObserver;
use App\Observers\NoteMessageObserver;
use App\Observers\PersonObserver;
use App\Observers\TaskObserver;
use App\Observers\TextMessageObserver;
use Illuminate\Support\ServiceProvider;

class ObserverServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Call::observe(CallObserver::class);
        Email::observe(EmailObserver::class);
        TextMessage::observe(TextMessageObserver::class);
        Note::observe(NoteMessageObserver::class);
        Appointment::observe(AppointmentObserver::class);
        Task::observe(TaskObserver::class);
        CalendarEvent::observe(CalendarEventObserver::class);
        Person::observe(PersonObserver::class);
        Event::observe(EventObserver::class);
    }
}
