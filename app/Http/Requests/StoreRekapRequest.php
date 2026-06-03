<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRekapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'between:2020,2099'],
            'rows'  => ['required', 'array'],
            'rows.*.desa_id'       => ['required', 'integer', 'exists:desa,id'],
            'rows.*.lahir_hidup_l' => ['required', 'integer', 'min:0'],
            'rows.*.lahir_hidup_p' => ['required', 'integer', 'min:0'],
            'rows.*.kn_lengkap_l'  => ['required', 'integer', 'min:0'],
            'rows.*.kn_lengkap_p'  => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            foreach ($this->input('rows', []) as $i => $row) {
                if (($row['kn_lengkap_l'] ?? 0) > ($row['lahir_hidup_l'] ?? 0)) {
                    $v->errors()->add("rows.$i.kn_lengkap_l", 'KN Lengkap L tidak boleh melebihi Lahir Hidup L.');
                }
                if (($row['kn_lengkap_p'] ?? 0) > ($row['lahir_hidup_p'] ?? 0)) {
                    $v->errors()->add("rows.$i.kn_lengkap_p", 'KN Lengkap P tidak boleh melebihi Lahir Hidup P.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'rows.*.lahir_hidup_l.min' => 'Nilai tidak boleh negatif.',
            'rows.*.kn_lengkap_l.min'  => 'Nilai tidak boleh negatif.',
        ];
    }
}
