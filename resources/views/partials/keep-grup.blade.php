@if (request()->filled('grup'))
    <input type="hidden" name="grup" value="{{ request('grup') }}">
@endif
