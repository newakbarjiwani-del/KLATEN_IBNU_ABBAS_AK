<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Capaian Tahfidz</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font-family: "Times New Roman", Times, serif; background: #d9d9d9; }
        .alat { position: sticky; top: 0; z-index: 2; display: flex; justify-content: flex-end; padding: 10px 16px; background: #102433; }
        .alat button { height: 34px; padding: 0 14px; border: 0; border-radius: 2px; background: #0875ab; color: #fff; font-family: "Segoe UI", sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; }
        .lembar { width: 277mm; margin: 12px auto; padding: 12mm; background: #fff; }
        h1 { margin: 0; text-align: center; font-size: 18px; letter-spacing: 0.03em; }
        p { margin: 6px 0 14px; text-align: center; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #222; padding: 4px 5px; vertical-align: top; }
        th { text-align: center; }
        @media print {
            body { background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .alat { display: none; }
            .lembar { width: auto; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="alat">
        <button type="button" onclick="window.print()">Cetak / simpan PDF</button>
    </div>
    <section class="lembar">
        <h1>LAPORAN CAPAIAN TAHFIDZ</h1>
        <p>{{ implode(', ', $labelBulan) }}</p>
        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    @foreach ($kolom as $kolomBaris)
                        <th>{{ $kolomBaris['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>{{ $record->periode }}</td>
                        <td>{{ trim((string) $record->NOCUST) }}</td>
                        <td>{{ trim((string) $record->NMCUST) }}</td>
                        @foreach ($kolom as $kolomBaris)
                            <td>{{ trim((string) ($record->{$kolomBaris['key']} ?? '')) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 3 + count($kolom) }}">Belum ada data pada bulan yang dipilih.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</body>
</html>
