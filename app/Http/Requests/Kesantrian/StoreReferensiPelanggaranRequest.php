<?php

namespace App\Http\Requests\Kesantrian;

use App\Models\ReferensiPelanggaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReferensiPelanggaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->kelCode() === 'admkes';
    }

    protected function prepareForValidation(): void
    {
        $note = $this->input('keterangan');

        $this->merge([
            'nama' => trim((string) $this->input('nama')),
            'keterangan' => is_string($note) ? trim($note) : $note,
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:1000'],
            'skor' => ['required', 'integer', 'between:0,32767'],
            'keterangan' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama pelanggaran wajib diisi.',
            'nama.max' => 'Nama pelanggaran terlalu panjang.',
            'skor.required' => 'Skor wajib diisi.',
            'skor.integer' => 'Skor harus berupa angka.',
            'skor.between' => 'Skor harus antara 0 dan 32767.',
            'keterangan.max' => 'Keterangan paling panjang 20 karakter.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $nama = $this->input('nama');

            if (! is_string($nama) || $nama === '') {
                return;
            }

            $exists = ReferensiPelanggaran::query()
                ->where('jenis_pelangaran', $nama)
                ->exists();

            if ($exists) {
                $validator->errors()->add('nama', 'Pelanggaran ini sudah ada.');
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
