<?php

namespace App\Support;

use App\Data\SrHrTeam;

class HrAuthorizedSignature
{
    /**
     * HR signature image (data URI) for PDF / embedded display after SR-HR approval.
     */
    public static function srcFromApprovalBlock(array $approval): ?string
    {
        if (($approval['status'] ?? '') !== 'approved') {
            return null;
        }

        if (!empty($approval['hr_signature_src'])) {
            return $approval['hr_signature_src'];
        }

        if (!empty($approval['hr_signature_file'])) {
            $fromFile = SrHrTeam::dataUriFromSignatureRef((string) $approval['hr_signature_file']);
            if ($fromFile) {
                return $fromFile;
            }
        }

        $email = trim((string) ($approval['approved_by_email'] ?? ''));
        if ($email === '') {
            return null;
        }

        return SrHrTeam::signatureDataUriForEmail($email);
    }

    /**
     * Web URL for HR signature preview (HR panel, SR-HR page).
     */
    public static function urlFromApprovalBlock(array $approval): ?string
    {
        if (($approval['status'] ?? '') !== 'approved') {
            return null;
        }

        if (!empty($approval['hr_signature_file'])) {
            $url = SrHrTeam::publicUrlFromSignatureRef((string) $approval['hr_signature_file']);
            if ($url) {
                return $url;
            }
        }

        $email = trim((string) ($approval['approved_by_email'] ?? ''));

        return $email !== ''
            ? SrHrTeam::signaturePublicUrl(SrHrTeam::findMemberByEmail($email), $email)
            : null;
    }

    /** HTML preview URL when available; otherwise embedded data URI for PDF. */
    public static function previewFromApprovalBlock(array $approval, bool $asUrl = false): ?string
    {
        if (($approval['status'] ?? '') !== 'approved') {
            return null;
        }

        if ($asUrl) {
            return self::urlFromApprovalBlock($approval) ?? self::srcFromApprovalBlock($approval);
        }

        return self::srcFromApprovalBlock($approval);
    }

    /** Pending SR-HR picker — signature preview URL for selected approver. */
    public static function previewUrlForApproverEmail(string $email): ?string
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        return SrHrTeam::signaturePublicUrl(SrHrTeam::findMemberByEmail($email), $email);
    }

    /**
     * @return array{src: ?string, file: ?string, name: ?string, id: ?string, url: ?string}
     */
    public static function resolveForApproverEmail(string $email): array
    {
        $member = SrHrTeam::findMemberByEmail($email);
        $path = SrHrTeam::resolveSignaturePath($member, $email);
        $src = $path ? SrHrTeam::dataUriFromPath($path) : null;

        return [
            'src' => $src,
            'file' => $path ? SrHrTeam::relativeSignatureRef($path) : null,
            'name' => $member['name'] ?? null,
            'role' => trim((string) ($member['role'] ?? '')) ?: null,
            'id' => $member['id'] ?? null,
            'url' => SrHrTeam::signaturePublicUrl($member, $email),
        ];
    }
}
