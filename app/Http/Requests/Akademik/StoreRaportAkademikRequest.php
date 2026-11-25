<?php

namespace App\Http\Requests\Akademik;

use App\Models\TahunAkademik;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRaportAkademikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->allows('admaka') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
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
            'ujian' => ['required', 'in:MID,AKHIR'],
            'tgl_upload' => ['nullable', 'date'],
            'berkas' => ['required', 'array', 'min:1', 'max:20'],
            'berkas.*' => ['file', 'extensions:pdf', 'max:10240'],
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
            'ujian.required' => 'Jenis ujian wajib dipilih.',
            'ujian.in' => 'Jenis ujian harus Mid atau Akhir.',
            'berkas.required' => 'Berkas PDF wajib dipilih.',
            'berkas.array' => 'Berkas PDF wajib dipilih.',
            'berkas.max' => 'Paling banyak 20 berkas sekaligus.',
            'berkas.*.extensions' => 'Setiap berkas harus berupa PDF.',
            'berkas.*.max' => 'Setiap berkas PDF paling besar 10 MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tahun = $this->input('thn_aka');

            if (is_string($tahun) && $tahun !== '' && ! TahunAkademik::query()->where('thn_aka', $tahun)->exists()) {
                $validator->errors()->add('thn_aka', 'Tahun akademik tidak ada di master.');
            }
        });
    }
}
