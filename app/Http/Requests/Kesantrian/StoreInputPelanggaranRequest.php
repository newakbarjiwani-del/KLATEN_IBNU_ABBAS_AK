<?php

namespace App\Http\Requests\Kesantrian;

use App\Models\ReferensiPelanggaran;
use App\Models\Santri;
use App\Models\TahunAkademik;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInputPelanggaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->kelCode() === 'admkes';
    }

    protected function prepareForValidation(): void
    {
        $note = $this->input('keterangan');

        $this->merge([
            'nocust' => trim((string) $this->input('nocust')),
            'keterangan' => is_string($note) ? trim($note) : $note,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'jenis' => ['required', 'in:pelanggaran,remisi'],
            'thn_aka' => ['required', 'string', 'max:30'],
            'tgl_kejadian' => ['required', 'date'],
            'nocust' => ['required', 'string', 'max:10'],
            'id_langgar' => ['required', 'integer'],
            'skor' => ['exclude_if:jenis,pelanggaran', 'required', 'integer', 'between:0,32767'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jenis.required' => 'Jenis wajib dipilih.',
            'jenis.in' => 'Jenis harus Pelanggaran atau Remisi.',
            'thn_aka.required' => 'Tahun akademik wajib dipilih.',
            'tgl_kejadian.required' => 'Tanggal wajib diisi.',
            'tgl_kejadian.date' => 'Tanggal tidak valid.',
            'nocust.required' => 'NIS wajib diisi.',
            'nocust.max' => 'NIS paling panjang 10 karakter.',
            'id_langgar.required' => 'Pelanggaran wajib dipilih.',
            'skor.required' => 'Skor remisi wajib diisi.',
            'skor.integer' => 'Skor harus berupa angka.',
            'skor.between' => 'Skor harus antara 0 dan 32767.',
            'keterangan.max' => 'Keterangan terlalu panjang.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tahun = $this->input('thn_aka');
            $nis = $this->input('nocust');
            $id = $this->input('id_langgar');

            if (is_string($tahun) && $tahun !== '' && ! TahunAkademik::query()->where('thn_aka', $tahun)->exists()) {
                $validator->errors()->add('thn_aka', 'Tahun akademik tidak ada di master.');
            }

            if (is_string($nis) && $nis !== '' && ! Santri::query()->where('NOCUST', $nis)->exists()) {
                $validator->errors()->add('nocust', 'NIS tidak ditemukan.');
            }

            if (is_numeric($id) && ! ReferensiPelanggaran::query()->whereKey((int) $id)->exists()) {
                $validator->errors()->add('id_langgar', 'Pelanggaran tidak ada di master.');
            }
        });
    }

    public function note(): ?string
    {
        $note = $this->validated('keterangan');

        if (! is_string($note) || trim($note) === '') {
            return null;
        }

        return trim($note);
    }
}
