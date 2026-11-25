<?php

namespace App\Http\Requests\Kesantrian;

use App\Models\TahunAkademik;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTahunAkademikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->allows('admkes') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'tahun' => ['required', 'regex:/^\d{4}$/'],
            'semester' => ['required', 'in:GANJIL,GENAP'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.regex' => 'Tahun harus 4 digit, contoh 2024.',
            'semester.required' => 'Semester wajib dipilih.',
            'semester.in' => 'Semester harus Ganjil atau Genap.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tahun = $this->input('tahun');
            $semester = $this->input('semester');

            if (! is_string($tahun) || preg_match('/^\d{4}$/', $tahun) !== 1) {
                return;
            }

            $year = (int) $tahun;

            if ($year < 1990 || $year > 2098) {
                $validator->errors()->add('tahun', 'Tahun harus antara 1990 dan 2098.');

                return;
            }

            if (! in_array($semester, ['GANJIL', 'GENAP'], true)) {
                return;
            }

            $label = $year.'/'.($year + 1).' - '.$semester;

            if (strlen($label) > 18) {
                $validator->errors()->add('tahun', 'Tahun akademik melebihi 18 karakter.');

                return;
            }

            if (TahunAkademik::query()->where('thn_aka', $label)->exists()) {
                $validator->errors()->add('tahun', 'Tahun akademik '.$label.' sudah ada.');
            }
        });
    }

    public function label(): string
    {
        $year = (int) $this->validated('tahun');

        return $year.'/'.($year + 1).' - '.$this->validated('semester');
    }
}
