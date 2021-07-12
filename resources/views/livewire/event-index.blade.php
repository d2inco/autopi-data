<div wire:poll.30s>
    <div class="py-6">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            <label for="search-text">Search</label>
            <input type="text" id="search-text" wire:model="query" />
        </div>
    </div>
    <div class="max-w-7xl mx-auto">
        {{ $eventList->links() }}
    </div>
    <div class="py-4">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <table width="100%" class="border border-2">
                    <th>ID</th>
                    <th>Raw ID</th>
                    <th>TS (MT)</th>
                    <th class="text-left">Type</th>
                    <th class="text-left">Tag</th>
                    <th class="text-left">Extra</th>
                    @foreach ($eventList as $event)
                        <livewire:event-item :event="$event" :key="$event->id" />
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
