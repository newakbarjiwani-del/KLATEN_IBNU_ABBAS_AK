@php
    $teks = trim((string) ($nilai ?? ''));
@endphp
<td class="col-grade">
    @if (preg_match('/^[A-E]$/i', $teks) === 1)
        <span class="grade-badge grade-{{ strtolower($teks) }}">{{ $teks }}</span>
    @elseif ($teks !== '')
        {{ $teks }}
    @else
        <span class="dash-empty">—</span>
    @endif
</td>
