<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $school = $this->route('school');

        return $school !== null
            && $this->user()?->hasSchoolPermission($school, 'schools.manage');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'timezone' => ['sometimes', 'required', 'string', 'max:80'],
            'locale' => ['sometimes', 'required', 'string', 'max:12'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'academic_year_start_month' => ['sometimes', 'required', 'integer', 'between:1,12'],
            'date_format' => ['sometimes', 'required', 'string', Rule::in(['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y'])],
            'sms_enabled' => ['sometimes', 'required', 'boolean'],
            'sms_provider' => ['sometimes', 'nullable', 'string', 'max:80'],
            'sms_api_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'attendance_warning_threshold_percent' => ['sometimes', 'required', 'integer', 'between:1,100'],
            'fee_invoice_prefix' => ['sometimes', 'required', 'string', 'max:24'],
            'result_grade_scale_id' => ['sometimes', 'nullable', 'integer'],
            'allow_parent_portal' => ['sometimes', 'required', 'boolean'],
            'allow_student_portal' => ['sometimes', 'required', 'boolean'],
            'pdf_header_logo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pdf_footer_text' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
