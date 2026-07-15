<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;
use Carbon\Carbon;

class OnboardingLetterDeadline
{
    public const TYPE_OFFER = 'offer';
    public const TYPE_REGISTRATION = 'registration';
    public const TYPE_APPOINTMENT = 'appointment';

    public static function isDateValid(?Carbon $dueDate): bool
    {
        if (!$dueDate) {
            return true;
        }

        return !Carbon::parse($dueDate)->startOfDay()->lt(Carbon::today());
    }

    public static function isExpired(?Carbon $dueDate): bool
    {
        return !self::isDateValid($dueDate);
    }

    public static function isOfferPending(EmployeesNewJoiner $employee): bool
    {
        return $employee->onboardingStep() === 'offer_sent'
            && in_array($employee->emp_offer_letter_status, ['pending', 'process', '0'], true);
    }

    public static function isRegistrationPending(EmployeesNewJoiner $employee): bool
    {
        return $employee->onboardingStep() === 'registration_sent';
    }

    public static function isAppointmentPending(EmployeesNewJoiner $employee): bool
    {
        return $employee->onboardingStep() === 'appointment_sent'
            && in_array($employee->emp_appointment_letter_status, ['pending', 'process', '0'], true);
    }

    public static function isOfferExpired(EmployeesNewJoiner $employee): bool
    {
        return self::isOfferPending($employee)
            && self::isExpired($employee->emp_offer_due_date);
    }

    public static function isRegistrationExpired(EmployeesNewJoiner $employee): bool
    {
        return self::isRegistrationPending($employee)
            && self::isExpired($employee->emp_registration_due_date);
    }

    public static function isAppointmentExpired(EmployeesNewJoiner $employee): bool
    {
        return self::isAppointmentPending($employee)
            && self::isExpired($employee->emp_appointment_due_date);
    }

    public static function canCandidateViewOffer(EmployeesNewJoiner $employee): bool
    {
        if (!self::isOfferPending($employee)) {
            return true;
        }

        return !self::isOfferExpired($employee);
    }

    public static function canCandidateActOnOffer(EmployeesNewJoiner $employee): bool
    {
        return self::isOfferPending($employee) && !self::isOfferExpired($employee);
    }

    public static function canCandidateUploadRegistration(EmployeesNewJoiner $employee): bool
    {
        return OnboardingStepGate::canCandidateUploadRegistration($employee)
            && !self::isRegistrationExpired($employee);
    }

    public static function canCandidateViewAppointment(EmployeesNewJoiner $employee): bool
    {
        if (!self::isAppointmentPending($employee)) {
            return true;
        }

        return !self::isAppointmentExpired($employee);
    }

    public static function canCandidateActOnAppointment(EmployeesNewJoiner $employee): bool
    {
        return self::isAppointmentPending($employee) && !self::isAppointmentExpired($employee);
    }

    public static function expiredMessage(string $type): string
    {
        return match ($type) {
            self::TYPE_OFFER => 'The offer letter acceptance deadline has passed. You cannot view or respond to the offer.',
            self::TYPE_REGISTRATION => 'The deadline to upload your resignation acceptance letter has passed.',
            self::TYPE_APPOINTMENT => 'The appointment letter acceptance deadline has passed. You cannot view or respond to the appointment letter.',
            default => 'This letter deadline has passed.',
        };
    }

    public static function expiredTitle(string $type): string
    {
        return match ($type) {
            self::TYPE_OFFER => 'Offer letter expired',
            self::TYPE_REGISTRATION => 'Resignation letter upload expired',
            self::TYPE_APPOINTMENT => 'Appointment letter expired',
            default => 'Deadline expired',
        };
    }

    public static function assignOfferDueDate(EmployeesNewJoiner $employee, ?Carbon $date = null): void
    {
        $employee->emp_offer_due_date = $date ?? now()->addDays((int) config('onboarding.offer_accept_days', 7));
    }

    public static function assignRegistrationDueDate(EmployeesNewJoiner $employee, ?Carbon $date = null): void
    {
        $employee->emp_registration_due_date = $date ?? now()->addDays((int) config('onboarding.registration_upload_days', 7));
    }

    public static function assignAppointmentDueDate(EmployeesNewJoiner $employee, ?Carbon $date = null): void
    {
        $employee->emp_appointment_due_date = $date ?? now()->addDays((int) config('onboarding.appointment_accept_days', 2));
    }
}
