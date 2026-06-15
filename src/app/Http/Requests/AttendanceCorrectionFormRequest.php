<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttendanceCorrectionFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'clock_in' => ['required'],
            'clock_out' => ['required', 'after:clock_in'],

            'breaks.*.break_start' => ['nullable'],
            'breaks.*.break_end' => ['nullable'],

            'note' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in.required' => '出勤時間を入力してください',
            'clock_out.required' => '退勤時間を入力してください',
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',

            'note.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            foreach ($this->input('breaks', []) as $index => $break) {

                if (
                    empty($break['break_start']) &&
                    empty($break['break_end'])
                ) {
                    continue;
                }

                if (
                    empty($break['break_start']) ||
                    empty($break['break_end'])
                ) {
                    $validator->errors()->add(
                        "breaks.$index.break_end",
                        '休憩時間を入力してください'
                    );

                    continue;
                }

                if ($break['break_start'] >= $break['break_end']) {
                    $validator->errors()->add(
                        "breaks.$index.break_end",
                        '休憩時間が不適切な値です'
                    );

                    // 1つの休憩に複数のエラーメッセージを表示しない
                    continue;
                }

                if (
                    $break['break_start'] < $this->clock_in ||
                    $break['break_start'] > $this->clock_out
                ) {
                    $validator->errors()->add(
                        "breaks.$index.break_start",
                        '休憩時間が不適切な値です'
                    );

                    continue;
                }

                if ($break['break_end'] > $this->clock_out) {
                    $validator->errors()->add(
                        "breaks.$index.break_end",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }
            }
        });
    }
}
