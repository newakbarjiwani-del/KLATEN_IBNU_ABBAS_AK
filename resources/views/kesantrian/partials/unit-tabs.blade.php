@if ($lockedUnit === null)
    <div class="unit-tabs">
        @foreach ($units as $unit)
            <a href="{{ route('kesantrian.menu', ['menu' => $menu, 'unit' => $unit]) }}" class="{{ $selectedUnit === $unit ? 'is-active' : '' }}">{{ $unit }}</a>
        @endforeach
    </div>
@endif
