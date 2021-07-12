<?php

namespace App\Http\Livewire;

use Livewire\Component;

use Carbon\Carbon;
# use App\Models\Events;

class EventItem extends Component
{
    public $event;

    public function render()
    {
        $dtg = Carbon::parse($this->event->ts)
            ->setTimezone('US/Mountain')
            ->format('Y-m-d H:i:s.v');

        return view('livewire.event-item', ['dtg' => $dtg]);
    }
}
