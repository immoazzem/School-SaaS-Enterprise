<?php

namespace App\ValueObjects;

use App\Models\School;

readonly class SchoolSettings
{
    public function __construct(
        public string $timezone,
        public string $locale,
        public string $currency,
        public int $academicYearStartMonth,
        public string $dateFormat,
        public bool $smsEnabled,
        public ?string $smsProvider,
        public ?string $smsApiKey,
        public int $attendanceWarningThresholdPercent,
        public string $feeInvoicePrefix,
        public ?int $resultGradeScaleId,
        public bool $allowParentPortal,
        public bool $allowStudentPortal,
        public ?string $pdfHeaderLogo,
        public ?string $pdfFooterText,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'academic_year_start_month' => 1,
            'date_format' => 'Y-m-d',
            'sms_enabled' => false,
            'sms_provider' => null,
            'sms_api_key' => null,
            'attendance_warning_threshold_percent' => 75,
            'fee_invoice_prefix' => 'INV',
            'result_grade_scale_id' => null,
            'allow_parent_portal' => true,
            'allow_student_portal' => true,
            'pdf_header_logo' => null,
            'pdf_footer_text' => null,
        ];
    }

    public static function fromSchool(School $school): self
    {
        return self::fromArray(array_merge(
            self::defaults(),
            [
                'timezone' => $school->timezone,
                'locale' => $school->locale,
            ],
            $school->settings ?? [],
        ));
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public static function fromArray(array $settings): self
    {
        $settings = array_merge(self::defaults(), $settings);

        return new self(
            timezone: (string) $settings['timezone'],
            locale: (string) $settings['locale'],
            currency: (string) $settings['currency'],
            academicYearStartMonth: (int) $settings['academic_year_start_month'],
            dateFormat: (string) $settings['date_format'],
            smsEnabled: (bool) $settings['sms_enabled'],
            smsProvider: $settings['sms_provider'] === null ? null : (string) $settings['sms_provider'],
            smsApiKey: $settings['sms_api_key'] === null ? null : (string) $settings['sms_api_key'],
            attendanceWarningThresholdPercent: (int) $settings['attendance_warning_threshold_percent'],
            feeInvoicePrefix: (string) $settings['fee_invoice_prefix'],
            resultGradeScaleId: $settings['result_grade_scale_id'] === null ? null : (int) $settings['result_grade_scale_id'],
            allowParentPortal: (bool) $settings['allow_parent_portal'],
            allowStudentPortal: (bool) $settings['allow_student_portal'],
            pdfHeaderLogo: $settings['pdf_header_logo'] === null ? null : (string) $settings['pdf_header_logo'],
            pdfFooterText: $settings['pdf_footer_text'] === null ? null : (string) $settings['pdf_footer_text'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'currency' => $this->currency,
            'academic_year_start_month' => $this->academicYearStartMonth,
            'date_format' => $this->dateFormat,
            'sms_enabled' => $this->smsEnabled,
            'sms_provider' => $this->smsProvider,
            'sms_api_key' => $this->smsApiKey,
            'attendance_warning_threshold_percent' => $this->attendanceWarningThresholdPercent,
            'fee_invoice_prefix' => $this->feeInvoicePrefix,
            'result_grade_scale_id' => $this->resultGradeScaleId,
            'allow_parent_portal' => $this->allowParentPortal,
            'allow_student_portal' => $this->allowStudentPortal,
            'pdf_header_logo' => $this->pdfHeaderLogo,
            'pdf_footer_text' => $this->pdfFooterText,
        ];
    }
}
