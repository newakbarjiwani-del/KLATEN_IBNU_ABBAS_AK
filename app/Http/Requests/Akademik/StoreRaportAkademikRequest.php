<?php

namespace App\Http\Requests\Akademik;

use App\Models\Santri;
use App\Models\TahunAkademik;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRaportAkademikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->kelCode() === 'admaka';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nocust' => trim((string) $this->input('nocust')),
            'thn_aka' => trim((string) $this->input('thn_aka')),
            'ujian' => strtoupper(trim((string) $this->input('ujian'))),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'thn_aka' => ['required', 'string', 'max:20'],
            'nocust' => ['required', 'string', 'max:10'],
            'kelas' => ['required', 'string'],
            'ujian' => ['required', 'in:MID,AKHIR'],
            'berkas' => ['required', 'file', 'extensions:pdf', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'thn_aka.required' => 'Tahun akademik wajib dipilih.',
            'thn_aka.max' => 'Tahun akademik terlalu panjang.',
            'nocust.required' => 'NIS wajib diisi.',
            'nocust.max' => 'NIS paling panjang 10 karakter.',
            'kelas.required' => 'Kelas wajib dipilih.',
            'ujian.required' => 'Jenis ujian wajib dipilih.',
            'ujian.in' => 'Jenis ujian harus Mid atau Akhir.',
            'berkas.required' => 'Berkas PDF wajib dipilih.',
            'berkas.extensions' => 'Berkas harus berupa PDF.',
            'berkas.max' => 'Berkas PDF paling besar 10 MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tahun = $this->input('thn_aka');
            $nis = $this->input('nocust');

            if (is_string($tahun) && $tahun !== '' && ! TahunAkademik::query()->where('thn_aka', $tahun)->exists()) {
                $validator->errors()->add('thn_aka', 'Tahun akademik tidak ada di master.');
            }

            if (! is_string($nis) || $nis === '') {
                return;
            }

            $santri = Santri::query()->where('NOCUST', $nis)->first();

            if ($santri === null) {
                $validator->errors()->add('nocust', 'NIS tidak ditemukan.');

                return;
            }

            $picked = Santri::pecahKunciKelas((string) $this->input('kelas', ''));

            if ($picked === null) {
                $validator->errors()->add('kelas', 'Kelas tidak dikenal.');

                return;
            }

            $matchesStudent = trim((string) $santri->CODE02) === $picked['unit']
                && trim((string) $santri->DESC02) === $picked['kelas']
                && trim((string) $santri->DESC03) === $picked['kelompok'];

            if (! $matchesStudent) {
                $validator->errors()->add('kelas', 'Kelas tidak sesuai dengan NIS.');
            }
        });
    }
}
