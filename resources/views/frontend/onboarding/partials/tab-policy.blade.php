<h4 class="mb-4"><i class="bi bi-shield-check me-2"></i>Company Policy</h4>

@include('shared.policy-acceptance-panel', [
    'employee' => $employee,
    'policyDocuments' => $policyDocuments ?? [],
    'currentStep' => $currentStep,
])
