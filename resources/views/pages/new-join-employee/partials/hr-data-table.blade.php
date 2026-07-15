{{-- Two-column label/value table. Expects $rows as [label => value] --}}
@if (!empty($rows))
    <table class="table table-sm table-bordered mb-0 hr-data-kv">
        <tbody>
            @foreach ($rows as $label => $value)
                <tr>
                    <th class="bg-light text-nowrap" style="width:32%;">{{ $label }}</th>
                    <td>{{ $value }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p class="text-muted small mb-0">No data.</p>
@endif
