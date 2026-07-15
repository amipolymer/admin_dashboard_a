<?php

namespace App\Data;

class DocumentNamesList
{
    /** Categories managed by HR / system — not shown in candidate Documents upload. */
    public const HR_MANAGED_CATEGORIES = ['Onboarding Letters', 'HR Records'];

    public static function all()
    {
        return [
            'ID Proofs' => [
                'aadhaar_card' => 'Aadhaar Card',
                'pan_card' => 'PAN Card',
                'passport' => 'Passport',
                'address_proof' => 'Address Proof',
                'photo' => 'Passport Size Photo',
                'emergency_contact' => 'Emergency Contact',
            ],
            'Education' => [
                '10th_marksheet' => '10th Marksheet',
                '12th_marksheet' => '12th Marksheet',
                'highest_certificate' => 'Highest Qualification',
                'additional_certification' => 'Additional Certification',
            ],
            'Employment' => [
                'appointment_letter' => 'Appointment Letter',
                'increment_letter' => 'Increment Letter',
                'salary_slips' => 'Salary Slips',
                'bank_statement' => 'Bank Statement',
            ],
            'Compliance' => [
                'pf_uan' => 'PF UAN Proof',
                'cancelled_cheque' => 'Cancelled Cheque',
                'medical_fitness_certificate' => 'Medical Fitness Certificate',
            ],
            'Onboarding Letters' => [
                'signed_offer_letter' => 'Signed Offer Letter (PDF)',
                'sr_hr_approved_appointment_letter' => 'Appointment Letter — SR-HR Approved (PDF)',
                'resignation_acceptance_letter' => 'Resignation Acceptance Letter (Upload)',
                'signed_registration_letter' => 'Resignation Acceptance Letter (Verified)',
                'signed_appointment_letter' => 'Signed Appointment Letter (PDF)',
                'signed_company_policy' => 'Company Policy Acceptance (Signed PDF)',
            ],
            'HR Records' => [
                'cv' => 'CV / Resume',
                'interview_evaluation' => 'Interview Evaluation',
                'mrf_file' => 'MRF File',
                'hr_combined_legacy_documents' => 'Combined Legacy / Missing Documents (HR)',
            ],
        ];
    }

    /** @return list<string> */
    public static function hrManagedKeys(): array
    {
        $keys = [];
        foreach (self::HR_MANAGED_CATEGORIES as $category) {
            $keys = array_merge($keys, array_keys(self::all()[$category] ?? []));
        }

        return $keys;
    }

    public static function isHrManagedKey(string $key): bool
    {
        return in_array($key, self::hrManagedKeys(), true);
    }

    /** Document groups for candidate portal Documents tab upload only. */
    public static function forCandidateUpload(): array
    {
        $out = [];
        foreach (self::all() as $group => $docs) {
            if (in_array($group, self::HR_MANAGED_CATEGORIES, true)) {
                continue;
            }
            if ($docs !== []) {
                $out[$group] = $docs;
            }
        }

        return $out;
    }

    /** @return list<string> */
    public static function candidateUploadKeys(): array
    {
        return array_keys(collect(self::forCandidateUpload())->collapse()->toArray());
    }

    /** @return array<string, string> */
    public static function collapsedLabels(): array
    {
        return collect(self::all())->collapse()->toArray();
    }
}
