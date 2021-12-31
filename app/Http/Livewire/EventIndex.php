<?php

namespace App\Http\Livewire;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

use Livewire\Component;

use App\Models\Events;
use Livewire\WithPagination;
use App\Models\User;

class EventIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $workingSearch = '';
    public $pageLen;

    public $errorMessage = '';

    protected $listeners = [
        'eventAdded',
    ];

    public function eventAdded($eventId)
    {
    }

    public function mount()
    {
        $this->search = Session::get('eventSearchText', '');
    }

    public function render()
    {
        try {
            User::where('name', 'REGEXP', $this->search)->first();
            $this->workingSearch = $this->search;

            Session::put('eventSearchText', $this->search);
            $this->errorMessage = "";
        } catch (QueryException $e) {
            $this->errorMessage = "Regular Expression Error";
            // don't update workingSearch, so it still uses the last-working value.
        }

        Log::debug('eventIndex():', ['search' => $this->search, 'workingSearch' => $this->workingSearch]);

        $eventList = Events::latest('ts')
            ->where('event_type', 'REGEXP', $this->workingSearch)
            ->orWhere('event_tag', 'REGEXP', $this->workingSearch)
            ->orWhere('event_data', 'REGEXP', $this->workingSearch)
            ->orWhere('extra_data', 'REGEXP', $this->workingSearch);

        return view('livewire.event-index', [
            'eventList' => $eventList->paginate(15)
        ]);
    }
}
