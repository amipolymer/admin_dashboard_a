<?php

namespace App\Support;

use App\Data\SrHrTeam;
use App\Models\EmployeesNewJoiner;
use App\Support\HrAuthorizedSignature;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SrHrLetterApproval
{
    public const TYPE_OFFER = 'offer';
    public const TYPE_APPOINTMENT = 'appointment';

    public static function configuredEmails(): array
    {
        $merged = array_merge(SrHrTeam::emails(), config('onboarding.sr_hr_emails', []));

        return array_values(array_unique(array_filter(array_map(function ($email) {
            $email = trim((string) $email);

            return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
        }, $merged))));
    }

    public static function isAllowedApproverEmail(EmployeesNewJoiner $employee, string $type, string $email): bool
    {
        $email = strtolower(trim($email));
        $allowed = array_map('strtolower', self::approverPoolFor($employee, $type));

        return in_array($email, $allowed, true);
    }

    public static function isConfiguredApproverEmail(string $email): bool
    {
        $email = strtolower(trim($email));

        return in_array($email, array_map('strtolower', self::configuredEmails()), true);
    }

    /**
     * @return list<string>
     */
    public static function approverPoolFor(EmployeesNewJoiner $employee, string $type): array
    {
        $notify = self::state($employee, $type)['notify_emails'] ?? [];

        return $notify !== [] ? $notify : self::configuredEmails();
    }

    public static function empOtherArray(EmployeesNewJoiner $employee): array
    {
        $other = $employee->emp_other ?? [];
        if (is_string($other)) {
            $decoded = json_decode($other, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($other) ? $other : [];
    }

    public static function state(EmployeesNewJoiner $employee, ?string $type): array
    {
        if ($type === null || $type === '') {
            return [];
        }

        self::migrateLegacyIfNeeded($employee);

        $fromColumn = ($employee->emp_sr_hr_approval ?? [])[$type] ?? [];

        if ($fromColumn !== []) {
            return $fromColumn;
        }

        return self::empOtherArray($employee)['sr_hr_approvals'][$type] ?? [];
    }

    /** Ensure dedicated column is populated from legacy emp_other storage. */
    protected static function migrateLegacyIfNeeded(EmployeesNewJoiner $employee): void
    {
        $column = $employee->emp_sr_hr_approval ?? [];
        if ($column !== [] && $column !== null) {
            return;
        }

        $legacy = self::empOtherArray($employee)['sr_hr_approvals'] ?? [];
        if ($legacy === []) {
            return;
        }

        self::persistApprovals($employee, $legacy, false);
    }

    /**
     * @param  array<string, array<string, mixed>>  $approvals
     */
    protected static function persistApprovals(EmployeesNewJoiner $employee, array $approvals, bool $syncLegacy = true): void
    {
        $employee->emp_sr_hr_approval = $approvals;
        $employee->emp_offer_sr_hr_status = $approvals['offer']['status'] ?? null;
        $employee->emp_appointment_sr_hr_status = $approvals['appointment']['status'] ?? null;

        if ($syncLegacy) {
            $other = self::empOtherArray($employee);
            $other['sr_hr_approvals'] = $approvals;
            $employee->emp_other = $other;
        }

        $employee->save();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function allApprovals(EmployeesNewJoiner $employee): array
    {
        self::migrateLegacyIfNeeded($employee);

        return $employee->emp_sr_hr_approval ?? self::empOtherArray($employee)['sr_hr_approvals'] ?? [];
    }

    protected static function setTypeBlock(EmployeesNewJoiner $employee, string $type, array $block): void
    {
        $all = self::allApprovals($employee);
        $all[$type] = $block;
        self::persistApprovals($employee, $all);
    }

    public static function isPending(EmployeesNewJoiner $employee, string $type): bool
    {
        $s = self::state($employee, $type);

        return ($s['status'] ?? '') === 'pending' && !empty($s['token']);
    }

    public static function isApproved(EmployeesNewJoiner $employee, string $type): bool
    {
        return (self::state($employee, $type)['status'] ?? '') === 'approved';
    }

    /**
     * @param  list<string>  $notifyEmails
     */
    public static function requestApproval(EmployeesNewJoiner $employee, string $type, array $notifyEmails): string
    {
        $token = Str::random(64);
        self::setTypeBlock($employee, $type, [
            'token' => $token,
            'status' => 'pending',
            'requested_at' => now()->toIso8601String(),
            'notify_emails' => $notifyEmails,
            'approved_at' => null,
            'approved_by_email' => null,
            'approved_by_name' => null,
            'approved_by_id' => null,
            'hr_signature_src' => null,
            'hr_signature_file' => null,
            'reject_reason' => null,
            'rejected_at' => null,
            'rejected_by_email' => null,
        ]);

        $employee->refresh();

        $step = $type === self::TYPE_OFFER ? 'offer_pending_sr_hr' : 'appointment_pending_sr_hr';
        $employee->setOnboardingStep($step);

        self::emailApprovers($employee, $type, $notifyEmails, $token);

        return $token;
    }

    public static function findEmployeeByToken(string $token): ?EmployeesNewJoiner
    {
        $token = trim($token);
        if ($token === '' || strlen($token) < 32) {
            return null;
        }

        $byJson = EmployeesNewJoiner::query()
            ->where(function ($q) use ($token) {
                $q->where('emp_sr_hr_approval->offer->token', $token)
                    ->orWhere('emp_sr_hr_approval->appointment->token', $token)
                    ->orWhere('emp_other->sr_hr_approvals->offer->token', $token)
                    ->orWhere('emp_other->sr_hr_approvals->appointment->token', $token);
            })
            ->first();

        if ($byJson) {
            return $byJson;
        }

        $needle = '"token":"' . addcslashes($token, '"\\') . '"';
        $byLike = EmployeesNewJoiner::query()
            ->whereNotNull('emp_other')
            ->where('emp_other', 'like', '%' . $needle . '%')
            ->first();

        if ($byLike && self::typeForToken($byLike, $token) !== null) {
            return $byLike;
        }

        foreach (EmployeesNewJoiner::query()->whereNotNull('emp_other')->cursor() as $employee) {
            if (self::typeForToken($employee, $token) !== null) {
                return $employee;
            }
        }

        return null;
    }

    public static function typeForToken(EmployeesNewJoiner $employee, string $token): ?string
    {
        $approvals = self::allApprovals($employee);
        foreach ([self::TYPE_OFFER, self::TYPE_APPOINTMENT] as $type) {
            if (($approvals[$type]['token'] ?? '') === $token) {
                return $type;
            }
        }

        return null;
    }

    public static function approve(EmployeesNewJoiner $employee, string $type, string $approverEmail): void
    {
        $block = self::state($employee, $type);
        if (($block['status'] ?? '') !== 'pending') {
            throw new \RuntimeException('This letter is not pending SR-HR approval.');
        }

        $sig = HrAuthorizedSignature::resolveForApproverEmail($approverEmail);

        self::setTypeBlock($employee, $type, array_merge($block, [
            'status' => 'approved',
            'approved_at' => now()->toIso8601String(),
            'approved_by_email' => $approverEmail,
            'approved_by_id' => $sig['id'],
            'approved_by_name' => $sig['name'],
            'approved_by_role' => $sig['role'],
            'hr_signature_src' => $sig['src'],
            'hr_signature_file' => $sig['file'],
            'reject_reason' => null,
            'rejected_at' => null,
            'rejected_by_email' => null,
        ]));
        $employee->refresh();
    }

    public static function reject(EmployeesNewJoiner $employee, string $type, string $reason, ?string $approverEmail = null): void
    {
        $block = self::state($employee, $type);
        self::setTypeBlock($employee, $type, array_merge($block, [
            'status' => 'rejected',
            'reject_reason' => $reason,
            'rejected_at' => now()->toIso8601String(),
            'rejected_by_email' => $approverEmail,
        ]));
        $employee->refresh();

        $step = $type === self::TYPE_OFFER ? 'offer_sr_rejected' : 'appointment_sr_rejected';
        $employee->setOnboardingStep($step);
    }

    public static function approvalUrl(string $token): string
    {
        return route('sr-hr.approval.show', ['token' => $token]);
    }

    /**
     * @param  list<string>  $emails
     */
    protected static function emailApprovers(
        EmployeesNewJoiner $employee,
        string $type,
        array $emails,
        string $token
    ): void {
        if ($emails === []) {
            $emails = self::configuredEmails();
        }

        $subject = $type === self::TYPE_OFFER
            ? config('onboarding.sr_hr_approval_subject_offer')
            : config('onboarding.sr_hr_approval_subject_appointment');

        $letter = $type === self::TYPE_OFFER
            ? (($employee->emp_other ?? [])['offer_letter'] ?? [])
            : (($employee->emp_other ?? [])['appointment_letter'] ?? []);

        $approvalLink = self::approvalUrl($token);
        $previewRoute = $type === self::TYPE_OFFER
            ? route('offer.preview.pdf', $employee->id)
            : route('appointment.preview.pdf', $employee->id);

        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            try {
                OnboardingMail::deliver('emails.onboarding.sr_hr_approval_request', [
                    'employee' => $employee,
                    'type' => $type,
                    'letter' => $letter,
                    'approvalLink' => $approvalLink,
                    'previewLink' => $previewRoute,
                    'hrShowLink' => route('EmployeeJoiner.Show', $employee->id),
                ], function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                });
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public static function clear(EmployeesNewJoiner $employee, string $type): void
    {
        $all = self::allApprovals($employee);
        unset($all[$type]);
        self::persistApprovals($employee, $all);
        $employee->refresh();
    }

    /**
     * HR reset: clear SR-HR record for a letter type and move onboarding step back for re-submission.
     */
    public static function reset(EmployeesNewJoiner $employee, string $type): void
    {
        self::clear($employee, $type);

        if ($type === self::TYPE_OFFER) {
            if (in_array($employee->onboardingStep(), ['offer_pending_sr_hr', 'offer_sr_rejected'], true)) {
                $employee->setOnboardingStep('documents_approved');
            }
        } elseif ($type === self::TYPE_APPOINTMENT) {
            if (in_array($employee->onboardingStep(), ['appointment_pending_sr_hr', 'appointment_sr_rejected'], true)) {
                $employee->setOnboardingStep('appointment_accepted');
            }
        }
    }

    /** Human-readable summary for HR admin panel. */
    public static function summaryForHr(EmployeesNewJoiner $employee, string $type): array
    {
        $s = self::state($employee, $type);
        if ($s === []) {
            return ['label' => 'Not requested', 'status' => null];
        }

        return [
            'status' => $s['status'] ?? null,
            'label' => ucfirst($s['status'] ?? 'unknown'),
            'requested_at' => $s['requested_at'] ?? null,
            'notify_emails' => $s['notify_emails'] ?? [],
            'approved_at' => $s['approved_at'] ?? null,
            'approved_by_email' => $s['approved_by_email'] ?? null,
            'approved_by_name' => $s['approved_by_name'] ?? null,
            'hr_signature_src' => $s['hr_signature_src'] ?? null,
            'hr_signature_file' => $s['hr_signature_file'] ?? null,
            'reject_reason' => $s['reject_reason'] ?? null,
            'rejected_at' => $s['rejected_at'] ?? null,
            'rejected_by_email' => $s['rejected_by_email'] ?? null,
            'token' => isset($s['token']) ? substr($s['token'], 0, 12) . '…' : null,
        ];
    }

    /** Resend approval email using existing pending token. */
    public static function resendApprovalRequest(EmployeesNewJoiner $employee, string $type): void
    {
        $state = self::state($employee, $type);
        if (($state['status'] ?? '') !== 'pending' || empty($state['token'])) {
            throw new \RuntimeException('No pending SR-HR approval to resend.');
        }

        $emails = $state['notify_emails'] ?? self::configuredEmails();
        if ($emails === []) {
            throw new \RuntimeException('No SR-HR approver emails on file.');
        }

        self::emailApprovers($employee, $type, $emails, $state['token']);
    }

    /** Change pending approver (e.g. on leave) and email the new SR-HR — same approval link. */
    public static function reassignPendingApprover(EmployeesNewJoiner $employee, string $type, string $newEmail): void
    {
        $state = self::state($employee, $type);
        if (($state['status'] ?? '') !== 'pending' || empty($state['token'])) {
            throw new \RuntimeException('No pending SR-HR approval to reassign.');
        }

        $newEmail = strtolower(trim($newEmail));
        if (!self::isConfiguredApproverEmail($newEmail)) {
            throw new \InvalidArgumentException('Select a valid SR-HR approver from the list.');
        }

        $current = array_map('strtolower', $state['notify_emails'] ?? []);
        if (in_array($newEmail, $current, true)) {
            self::resendApprovalRequest($employee, $type);

            return;
        }

        self::setTypeBlock($employee, $type, array_merge($state, [
            'notify_emails' => [$newEmail],
            'requested_at' => now()->toIso8601String(),
        ]));
        $employee->refresh();

        self::emailApprovers($employee, $type, [$newEmail], $state['token']);
    }
}
