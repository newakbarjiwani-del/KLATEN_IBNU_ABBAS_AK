<h3 class="bagian">{{ $huruf }}. {{ mb_strtoupper($kelompok->nama) }}</h3>
<table class="nilai">
    <thead>
        <tr>
            <th>No</th>
            <th>Kegiatan</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($kelompok->items as $index => $item)
            <tr>
                <td class="no">{{ $index + 1 }}</td>
                <td>{{ $item->nama }}</td>
                <td class="ket">{{ $nilai[$item->id] ?? '' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
