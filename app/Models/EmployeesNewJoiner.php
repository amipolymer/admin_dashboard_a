<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 
use App\Models\User;
use App\Traits\Auditable; 

class EmployeesNewJoiner extends Model
{
     use SoftDeletes, Auditable; 

    protected $auditAllFields = true;         // log everything
    protected $auditExclude = []; // except this field

    protected $table = 'employees_new_joiners';

    protected $fillable = [
        'emp_name',
        'emp_email',
        'emp_phone',
        'emp_role',
        'emp_grade',
        'emp_category',
        'emp_application_source',
        'emp_location',
        'emp_hr_id',
        'emp_date',
        'emp_status',
        'emp_url',
        'emp_document_status',
        'emp_last_updated_at',
        'emp_document_due_date',
        'emp_other',
        'emp_sr_hr_approval',
        'emp_offer_sr_hr_status',
        'emp_appointment_sr_hr_status',
        'emp_department',
        'emp_dob',
        'emp_mrf_no',
        'emp_folder',
        'emp_folder_path',
        'emergency_contact',
        'emp_onboarding_step',
        'emp_onboarding_status',
        'emp_profile_data',
        'emp_signature',
        'emp_employee_id',
        'emp_joining_date',
        'emp_joining_requirements',
        'emp_policy_accepted_at',
        'emp_policy_signature',
        'emp_offer_sent_at',
        'emp_offer_due_date',
        'emp_registration_sent_at',
        'emp_registration_due_date',
        'emp_appointment_sent_at',
        'emp_appointment_due_date',
        'emp_archived_at',
        'emp_offer_reject_reason',
        'emp_appointment_reject_reason',
        'emp_offer_letter_status',
        'emp_appointment_letter_status',
        'ongrid_id',
        'ongrid_response',
    ];

    protected $casts = [
        'emp_other' => 'array',
        'emp_sr_hr_approval' => 'array',
        'emp_profile_data' => 'array',
        'emp_joining_requirements' => 'array',
        'ongrid_response' => 'array',
        'emp_date' => 'date',
        'emp_document_due_date' => 'date',
        'emp_joining_date' => 'date',
        'emp_appointment_due_date' => 'date',
        'emp_offer_sent_at' => 'datetime',
        'emp_offer_due_date' => 'date',
        'emp_registration_sent_at' => 'datetime',
        'emp_registration_due_date' => 'date',
        'emp_appointment_sent_at' => 'datetime',
        'emp_policy_accepted_at' => 'datetime',
        'emp_archived_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'emp_hr_id', 'id');
    }

    public function onboardingStep(): string
    {
        return $this->emp_onboarding_step ?: 'start';
    }

    public function setOnboardingStep(string $step): void
    {
        $this->emp_onboarding_step = $step;
        $this->save();
    }

    public function isProfileComplete(): bool
    {
        return \App\Support\OnboardingProfileDraft::isSubmitted($this);
    }

    public function profileReeditAllowed(): bool
    {
        return \App\Support\OnboardingProfileReedit::isAllowed($this);
    }

    public function documentReeditAllowed(): bool
    {
        return \App\Support\OnboardingDocumentReedit::isAllowed($this);
    }

    /** First submit: open form. After submit: locked until HR allows re-update. */
    public function canEditPortalProfile(): bool
    {
        if ($this->emp_onboarding_status === 'completed') {
            return false;
        }

        if (\App\Support\OnboardingArchive::isFinalized($this)) {
            return false;
        }

        if ($this->profileReeditAllowed()) {
            return true;
        }

        if (\App\Support\OnboardingStepGate::isOnboardingLocked($this)) {
            return false;
        }

        return !$this->isProfileComplete();
    }

    public function resignationLetterDocument(): ?\App\Models\NewEmployeesDocument
    {
        return \App\Models\NewEmployeesDocument::query()
            ->where('emp_id', $this->id)
            ->where('emp_select_document', \App\Support\OnboardingLetterDocument::RESIGNATION_UPLOAD)
            ->first();
    }

    /** Candidate confirmed document upload in the portal (HR review or later). */
    public function hasCandidateSubmittedDocuments(): bool
    {
        return in_array($this->onboardingStep(), [
            'hr_review',
            'documents_submitted',
            'documents_approved',
            'documents_rejected',
            'offer_pending_sr_hr',
            'offer_sr_rejected',
            'offer_sent',
            'offer_accepted',
            'offer_rejected',
            'registration_sent',
            'registration_submitted',
            'registration_verified',
            'bgv_started',
            'bgv_completed',
            'join_started',
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

    /** HR may send offer letter only after profile, candidate doc submit, and HR approval. */
    public function canProceedToOfferLetter(): bool
    {
        return $this->isProfileComplete()
            && $this->hasCandidateSubmittedDocuments()
            && $this->emp_document_status === 'completed';
    }

    public function offerLetterBlockedReason(): ?string
    {
        if ($this->canProceedToOfferLetter()) {
            return null;
        }

        $reasons = [];
        if (!$this->isProfileComplete()) {
            $reasons[] = 'Candidate has not submitted basic information.';
        }
        if (!$this->hasCandidateSubmittedDocuments()) {
            $reasons[] = 'Candidate has not submitted documents.';
        }
        if ($this->emp_document_status !== 'completed') {
            $reasons[] = 'Approve all documents first.';
        }

        return implode(' ', $reasons);
    }

    public function displayEmployeeId(): string
    {
        if ($this->emp_employee_id) {
            return $this->emp_employee_id;
        }
        if ($this->emp_folder && $this->emp_folder !==  $this->id) {
        // if ($this->emp_folder && $this->emp_folder !== 'EMP_' . $this->id) {
            return $this->emp_folder;
        }
        return  $this->id;
        // return 'EMP' . $this->id;
    }

    public function scheduledJoinDate(): ?Carbon
    {
        $req = $this->emp_joining_requirements ?? [];
        $fromDetails = $req['joining_details']['confirmed_join_date'] ?? null;
        if ($fromDetails) {
            return Carbon::parse($fromDetails);
        }
        if (!empty($req['confirmed_join_date'])) {
            return Carbon::parse($req['confirmed_join_date']);
        }
        if ($this->emp_joining_date) {
            return $this->emp_joining_date instanceof Carbon
                ? $this->emp_joining_date
                : Carbon::parse($this->emp_joining_date);
        }
        $offerDate = ($this->emp_other ?? [])['offer_letter']['joining_date'] ?? null;
        if ($offerDate) {
            return Carbon::parse($offerDate);
        }

        return null;
    }

    public function joinDateIsToday(): bool
    {
        $date = $this->scheduledJoinDate();

        return $date && $date->isToday();
    }

    public function employmentType(): string
    {
        return \App\Support\CandidateEmploymentType::resolve($this);
    }

    public function isFresher(): bool
    {
        return \App\Support\CandidateEmploymentType::isFresher($this);
    }

    public function isExperienced(): bool
    {
        return !$this->isFresher();
    }
}
