<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminAttendanceUpdateRequest extends FormRequest
{
    /**
     * このリクエストの実行を許可する。
     *
     * @return bool リクエストを許可する場合はtrue
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 管理者による勤怠修正時のバリデーションルールを定義する。
     *
     * @return array<string, ValidationRule|array<mixed>|string> バリデーションルール
     */
    public function rules(): array
    {
        return [
            'clock_in' => ['required'],
            'clock_out' => ['required', 'after:clock_in'],
            'note' => ['required'],
        ];
    }

    /**
     * 管理者による勤怠修正時のバリデーションメッセージを定義する。
     *
     * @return array<string, string> バリデーションメッセージ
     */
    public function messages(): array
    {
        return [
            'clock_in.required' => '出勤時間を入力してください',
            'clock_out.required' => '退勤時間を入力してください',
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'note.required' => '備考を記入してください',
        ];
    }

    /**
     * 勤怠修正時の休憩時間が出退勤時間の範囲内であるかを追加検証する。
     *
     * @param Validator $validator バリデーション処理を行うValidator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('breaks', []) as $index => $break) {
                if (
                    empty($break['break_start']) ||
                    empty($break['break_end'])
                ) {
                    continue;
                }

                if ($break['break_start'] >= $break['break_end']) {
                    $validator->errors()->add(
                        "breaks.$index.break_end",
                        '休憩時間が不適切な値です'
                    );

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

                    continue;
                }
            }
        });
    }
}
