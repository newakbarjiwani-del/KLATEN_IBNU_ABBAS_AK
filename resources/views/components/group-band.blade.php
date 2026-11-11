@props(['label', 'value', 'span'])

<tr class="row-group">
    <td colspan="{{ $span }}">
        <span class="group-band-kicker">{{ $label }}</span>
        <strong>{{ $value !== '' ? $value : 'Kosong' }}</strong>
    </td>
</tr>
