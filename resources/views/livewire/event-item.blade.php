<tr class="border border-2">
    <td style="width: 5em" class="text-right border border-1">{{ $event->id }}</td>
    <td style="width: 6em" class="text-right border border-1">{{ $event->raw_id }}</td>
    <td style="width: 15%" class="text-center border border-1">
        <div>{{ substr($dtg, 11, 11) }}</div>
        <div class="text-xs">{{ substr($dtg, 0, 10) }}</div>
    </td>
    <td>{{ $event->event_type }}</td>
    <td>{{ $event->event_tag }} </td>
    <td class="whitespace-pre-line">{{ preg_replace('/(^{\n|\n}$)/', '', $event->event_data) }}</td>
</tr>
