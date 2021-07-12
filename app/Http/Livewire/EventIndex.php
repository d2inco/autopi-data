<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\Events;
use Livewire\WithPagination;

class EventIndex extends Component
{
    use WithPagination;

    public $query = '';

    protected $listeners = [
        'eventAdded',
    ];

    public function eventAdded($eventId)
    {
    }

    public function render()
    {
        $eventList = Events::latest('ts')->where('event_tag', 'LIKE', '%' . $this->query . '%')
            ->orWhere('event_data', 'LIKE', '%' . $this->query . '%');


        return view('livewire.event-index', [
            'eventList' => $eventList->paginate(15)
        ]);
    }
}
