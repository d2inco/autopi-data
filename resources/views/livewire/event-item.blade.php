<tr class="border border-2">
    <td style="width: 5em" class="text-right border border-1">{{ $event->id }}</td>
    <td style="width: 6em" class="text-right border border-1">{{ $event->raw_id }}</td>
    <td style="width: 15%" class="text-center border border-1">
        <div>{{ substr($dtg, 11, 11) }}</div>
        <div class="text-xs">{{ substr($dtg, 0, 10) }}</div>
    </td>
    <td>{{ $event->event_type }}</td>
    <td>{{ $event->event_tag }} </td>
    <td>
        @forelse ($event->event_data?? array()  as $k => $v)
            {{ $k }}: {{ trim($v) }}<br />
        @empty
            ---
        @endforelse
    </td>
    <td>
        @forelse ($event->extra_data ?? array() as $k => $v)
            {{ $k }}: {{ trim($v) }}<br />
        @empty
            ---
        @endforelse
    </td>
</tr>
