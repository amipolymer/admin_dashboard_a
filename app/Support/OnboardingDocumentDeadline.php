<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;
use Carbon\Carbon;

class OnboardingDocumentDeadline
{
    public static function isExpired(EmployeesNewJoiner $employee): bool
    {
        if (!$employee->emp_document_due_date) {
            return false;
        }

        return Carbon::parse($employee->emp_document_due_date)->startOfDay()->lt(Carbon::today());
    }

    /**
     * Block the whole candidate portal when the initial document deadline passed
     * and the candidate still needs to submit or fix documents.
     */
    public static function isPortalBlocked(EmployeesNewJoiner $employee): bool
    {
        if (!self::isExpired($employee)) {
            return false;
        }

        if (OnboardingDocumentReedit::isAllowed($employee)) {
            return false;
        }

        if (OnboardingDocumentReedit::wasRecentlyResubmitted($employee)) {
            return false;
        }

        if (!$employee->hasCandidateSubmittedDocuments()) {
            return true;
        }

        return $employee->emp_document_status === 'rejected'
            || $employee->onboardingStep() === 'documents_rejected';
    }

    public static function blockedMessage(): string
    {
        return 'The document submission deadline has passed. You cannot use the onboarding portal until HR extends the deadline.';
    }

    public static function blockedTitle(): string
    {
        return 'Document submission deadline expired';
    }
}
