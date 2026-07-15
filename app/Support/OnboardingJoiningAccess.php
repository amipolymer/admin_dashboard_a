<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;
use Carbon\Carbon;

class OnboardingJoiningAccess
{
    public static function canCandidateEdit(EmployeesNewJoiner $employee): bool
    {
        return $employee->onboardingStep() === 'join_forms_sent';
    }

    public static function hasSubmittedData(EmployeesNewJoiner $employee): bool
    {
        $req = $employee->emp_joining_requirements ?? [];

        return is_array($req) && $req !== [];
    }

    public static function canCandidateView(EmployeesNewJoiner $employee): bool
    {
        if (!self::hasSubmittedData($employee)) {
            return false;
        }

        return in_array($employee->onboardingStep(), [
            'join_forms_submitted',
            'policy_signed',
            'appointment_pending_sr_hr',
            'appointment_sr_rejected',
            'appointment_sent',
            'appointment_accepted',
            'appointment_rejected',
            'end',
        ], true);
    }

    public static function isPortalRelevant(EmployeesNewJoiner $employee): bool
    {
        if (self::canCandidateEdit($employee) || self::canCandidateView($employee)) {
            return true;
        }

        return in_array($employee->onboardingStep(), [
            'registration_verified',
            'bgv_started',
            'bgv_completed',
        ], true) || self::hasSubmittedData($employee);
    }

    public static function candidateBlockedMessage(EmployeesNewJoiner $employee): ?string
    {
        if (self::canCandidateEdit($employee) || self::canCandidateView($employee)) {
            return null;
        }

        return self::buildBlockedMessage($employee);
    }

    /** HR has started the join process (forms sent onward). */
    public static function hasJoiningProcessStarted(EmployeesNewJoiner $employee): bool
    {
        if ($employee->emp_policy_accepted_at) {
            return true;
        }

        return in_array($employee->onboardingStep(), [
            'join_forms_sent',
            'join_forms_submitted',
            'policy_signed',
            'appointment_pending_sr_hr',
            'appointment_sr_rejected',
            'appointment_sent',
            'appointment_accepted',
            'appointment_rejected',
            'end',
        ], true);
    }

    public static function shouldShowJoiningTab(EmployeesNewJoiner $employee): bool
    {
        return self::isPortalRelevant($employee)
            || self::canCandidateEdit($employee)
            || self::canCandidateView($employee);
    }

    public static function shouldShowPolicyTab(EmployeesNewJoiner $employee): bool
    {
        return self::hasJoiningProcessStarted($employee);
    }

    protected static function buildBlockedMessage(EmployeesNewJoiner $employee): string
    {
        $step = $employee->onboardingStep();

        if (in_array($step, [
            'start', 'profile_completed', 'hr_review', 'documents_submitted',
            'documents_approved', 'documents_rejected',
            'offer_pending_sr_hr', 'offer_sr_rejected', 'offer_sent',
            'offer_accepted', 'offer_rejected',
        ], true)) {
            return 'Joining forms are not available yet. Complete your profile, documents, and offer acceptance first.';
        }

        if (in_array($step, ['registration_sent', 'registration_submitted'], true)) {
            return 'Joining forms open after HR verifies your registration letter and completes background verification.';
        }

        if ($step === 'registration_verified') {
            return 'Joining forms are not open yet. HR will start background verification, then open joining forms when your join date is confirmed.';
        }

        if ($step === 'bgv_started') {
            if (OnboardingEarlyJoin::isAllowed($employee)) {
                $joinDate = self::resolvedJoinDate($employee);

                return 'Background verification is in progress. HR has approved early joining'
                    . ($joinDate ? ' — scheduled join date: ' . $joinDate->format('d M Y') . '.' : '.')
                    . ' Joining forms will open when HR starts the join process.';
            }

            return 'Joining forms are not open yet. Background verification is in progress. HR will open joining forms after BGV is complete, or after early joining is approved.';
        }

        if ($step === 'bgv_completed') {
            $joinDate = self::resolvedJoinDate($employee);
            if (!$joinDate) {
                return 'Joining forms are not open yet. HR will set your join date and start the joining process.';
            }

            if (!OnboardingEarlyJoin::isJoinDateAllowedForStartProcess($joinDate)) {
                return 'Joining forms are not open yet. Your join date (' . $joinDate->format('d M Y') . ') is outside the active window ('
                    . OnboardingEarlyJoin::joinDateWindowStart()->format('d M Y') . ' – '
                    . OnboardingEarlyJoin::joinDateWindowEnd()->format('d M Y') . '). Please contact HR.';
            }

            return 'Joining forms are not open yet. HR will start the join process for your scheduled date (' . $joinDate->format('d M Y') . ').';
        }

        return 'Joining forms will be available when HR starts the join process.';
    }

    protected static function resolvedJoinDate(EmployeesNewJoiner $employee): ?Carbon
    {
        $date = $employee->emp_joining_date ?? $employee->scheduledJoinDate();
        if (!$date) {
            return null;
        }

        return $date instanceof Carbon
            ? $date->copy()->startOfDay()
            : Carbon::parse((string) $date)->startOfDay();
    }
}
