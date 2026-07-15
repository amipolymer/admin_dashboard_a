<?php

return [
    /*
    | SR-HR team emails for offer / appointment letter approval (comma-separated in .env).
    | Example: ONBOARDING_SR_HR_EMAILS="sr1@abc.com,sr2@abc.com,sr3@abc.com"
    */
    'sr_hr_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ONBOARDING_SR_HR_EMAILS', ''))
    ))),

    'sr_hr_approval_subject_offer' => 'Offer letter Approval for SR-HR team',
    'sr_hr_approval_subject_appointment' => 'Appointment letter Approval for SR-HR team',

    /*
    | HR / SR-HR authorized signatory images (PNG/JPG). Map emails in form_join/sr-hr-team.json.
    | signature_file in JSON e.g. "/hr-signatures/hr-1.png" or "hr-1.png".
    | Checked in order: storage/app/public/onboarding/hr-signatures, public/hr-signatures, public/onboarding/hr-signatures.
    */
    'hr_signatures_dirs' => array_values(array_filter([
        storage_path('app/public/onboarding/hr-signatures'),
        public_path('hr-signatures'),
        public_path('onboarding/hr-signatures'),
    ])),

    'appointment_accept_days' => (int) env('ONBOARDING_APPOINTMENT_DAYS', 2),
    'offer_accept_days' => (int) env('ONBOARDING_OFFER_DAYS', 3),
    'registration_upload_days' => (int) env('ONBOARDING_REGISTRATION_DAYS', 5),
    /** HR "Start Join Process": allowed join date window relative to today. */
    'join_process_past_days' => (int) env('ONBOARDING_JOIN_PROCESS_PAST_DAYS', 5),
    'join_process_future_days' => (int) env('ONBOARDING_JOIN_PROCESS_FUTURE_DAYS', 5),

    /** OnGrid HTTP timeout (seconds) for initiate + status calls. */
    'ongrid_timeout' => (int) env('ONGRID_HTTP_TIMEOUT', 120),

    /*
    | When true, candidate must upload every document type before Confirm & Submit.
    | When false, candidate may submit with only the documents they have (at least one).
    | Example: ONBOARDING_ALL_DOCUMENTS_REQUIRED=false
    */
    'all_documents_required' => filter_var(env('ONBOARDING_ALL_DOCUMENTS_REQUIRED', true), FILTER_VALIDATE_BOOLEAN),

    /*
    | Optional CC on every onboarding email (candidate, HR, SR-HR, reminders).
    | Comma-separated — up to 2 or more addresses. Recipients can see other CC addresses.
    | Example: ONBOARDING_MAIL_CC="hr-copy@company.com,onboarding-archive@company.com"
    | Legacy: ONBOARDING_MAIL_BCC is still read if ONBOARDING_MAIL_CC is empty (applied as CC).
    */
    'mail_cc' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ONBOARDING_MAIL_CC', env('ONBOARDING_MAIL_BCC', '')))
    ))),
];
