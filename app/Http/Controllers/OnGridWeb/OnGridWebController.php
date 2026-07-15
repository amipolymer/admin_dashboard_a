<?php

namespace App\Http\Controllers\OnGridWeb;

use App\Http\Controllers\Controller;
use App\Models\EmployeesNewJoiner;
use App\Support\OnGrid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OnGridWebController extends Controller
{
    protected OnGrid $ongrid;

    public function __construct()
    {
        $this->ongrid = OnGrid::make();
    }

    public function index()
    {
        return view('pages.ongridweb.index');
    }

    public function bvgLink(Request $request)
    {
        $employee = EmployeesNewJoiner::find($request->employee_id);
        if (!$employee) {
            return redirect()->route('EmployeeJoiner.Show', ['id' => $request->employee_id])
                ->with(['error' => 'Employee not found.', 'bg-color' => 'danger']);
        }

        $codes = $request->input('offerings', []);
        if (!is_array($codes)) {
            $codes = [];
        }

        $empvDate = $request->input('empv_start_date');
        $joiningMin = null;

        if (!$employee->isFresher() && OnGrid::empvStartDateFormContext($employee) !== null) {
            $employment = ($employee->emp_profile_data ?? [])['information']['previous_employment'] ?? [];
            $empRecord = OnGrid::empvEmploymentRecord(is_array($employment) ? $employment : []);
            $joiningMin = OnGrid::formatEmploymentDate($empRecord['joining_date'] ?? null);

            if ($empvDate !== null && trim((string) $empvDate) !== '') {
                $validator = Validator::make(
                    ['empv_start_date' => $empvDate],
                    ['empv_start_date' => OnGrid::empvStartDateValidationRules($joiningMin)],
                    [
                        'empv_start_date.required' => 'Select the employment verification start date.',
                        'empv_start_date.after_or_equal' => 'Date must be within 45 days in the past (and not before the candidate\'s joining date at that employer).',
                        'empv_start_date.before_or_equal' => 'Date cannot be more than 3 months in the future.',
                    ]
                );
                if ($validator->fails()) {
                    return redirect()->route('EmployeeJoiner.Show', ['id' => $employee->id])
                        ->withErrors($validator)
                        ->withInput($request->only(['offerings', 'empv_start_date']))
                        ->with(['open_bgv_modal' => true, 'bg-color' => 'danger']);
                }
                OnGrid::saveEmpvStartDatePreference($employee, $empvDate);
                $employee->refresh();
            } elseif (in_array('EMPV', $codes, true) && $joiningMin) {
                $empvDate = $joiningMin;
                $request->merge(['empv_start_date' => $empvDate]);
                OnGrid::saveEmpvStartDatePreference($employee, $empvDate);
                $employee->refresh();
            } elseif (in_array('EMPV', $codes, true)) {
                return redirect()->route('EmployeeJoiner.Show', ['id' => $employee->id])
                    ->withInput($request->only(['offerings', 'empv_start_date']))
                    ->with([
                        'error' => 'Employment verification start date is required (defaults to candidate joining date at previous employer when available).',
                        'bg-color' => 'danger',
                        'open_bgv_modal' => true,
                    ]);
            }
        }

        if (($empvDate === null || trim((string) $empvDate) === '') && in_array('EMPV', $codes, true)) {
            $other = is_array($employee->emp_other) ? $employee->emp_other : [];
            $savedEmpv = OnGrid::formatEmploymentDate($other['empv_start_date'] ?? null);
            if ($savedEmpv !== null) {
                $empvDate = $savedEmpv;
            }
        }

        try {
            OnGrid::assertSelectedOfferingsReady($employee, $codes, $empvDate);

            $response = $this->ongrid->initiateBgv(
                $employee,
                $codes,
                $empvDate
            );
            $individualId = $response['individual']['id'] ?? null;
            $skipped = $response['skipped_offerings'] ?? [];
            $documentIds = $response['document_ids'] ?? [];
            unset($response['skipped_offerings'], $response['document_ids']);

            $previous = is_array($employee->ongrid_response) ? $employee->ongrid_response : [];
            $prevDocIds = OnGrid::storedDocumentIds($employee);
            $mergedVerifications = self::mergeVerificationHistory(
                $previous['verifications'] ?? [],
                $response['verifications'] ?? []
            );
            $response['verifications'] = $mergedVerifications;

            \App\Support\OnboardingDocumentReedit::clearBgvReadyFlag($employee);
            $employee->refresh();
            $employee->ongrid_id = $individualId ? (string) $individualId : ($employee->ongrid_id ?: null);
            $employee->ongrid_response = array_merge($previous, $response, [
                'initiated_at' => now()->toIso8601String(),
                'verification_codes' => $codes,
                'skipped_offerings' => $skipped,
                'deduplication_id' => OnGrid::deduplicationId($employee),
                'employee_id_sent' => OnGrid::ongridEmployeeId($employee),
                'empv_start_date' => $empvDate,
                'document_ids' => array_merge($prevDocIds, is_array($documentIds) ? $documentIds : []),
            ]);
            $employee->save();

            if (!in_array($employee->onboardingStep(), ['bgv_completed', 'end'], true)) {
                $employee->setOnboardingStep('bgv_started');
            }

            $count = count($response['verifications'] ?? []);
            $message = !empty($previous['initiated_at'])
                ? 'OnGrid BGV updated. Individual ID: ' . ($individualId ?? $employee->ongrid_id) . ". Checks on file: {$count}."
                : 'OnGrid BGV started. Individual ID: ' . ($individualId ?? 'N/A') . ". Checks: {$count}.";
            if ($skipped !== []) {
                $empvScheduled = $skipped['EMPV'] ?? null;
                if ($empvScheduled !== null && str_contains((string) $empvScheduled, 'Scheduled for')) {
                    $message .= ' Employment Verification is scheduled for a future date and was not started; other selected checks were started.';
                    unset($skipped['EMPV']);
                }
                $already = [];
                foreach ($skipped as $code => $reason) {
                    if (is_string($reason) && str_contains($reason, 'Already started/completed')) {
                        $already[] = $code;
                        unset($skipped[$code]);
                    }
                }
                if ($already !== []) {
                    $message .= ' Already on OnGrid (not re-requested): ' . implode(', ', $already) . '.';
                }
                if ($skipped !== []) {
                    $message .= ' Some offerings were skipped (missing data): '
                        . implode(', ', array_keys($skipped)) . '.';
                }
            }

            return redirect()->route('EmployeeJoiner.Show', ['id' => $employee->id])
                ->with(['success' => $message, 'bg-color' => 'success']);
        } catch (\Throwable $e) {
            Log::error('OnGrid initiate failed', ['employee_id' => $employee->id, 'message' => $e->getMessage()]);

            return redirect()->route('EmployeeJoiner.Show', ['id' => $employee->id])
                ->withInput($request->only(['offerings', 'empv_start_date']))
                ->with([
                    'error' => $e->getMessage(),
                    'bg-color' => 'danger',
                    'open_bgv_modal' => true,
                ]);
        }
    }

    /**
     * Keep prior verification rows when re-initiate skips already-active codes.
     *
     * @param  mixed  $previous
     * @param  mixed  $fresh
     * @return list<array<string, mixed>>
     */
    protected static function mergeVerificationHistory(mixed $previous, mixed $fresh): array
    {
        $byCode = [];
        foreach ([$previous, $fresh] as $list) {
            if (!is_array($list)) {
                continue;
            }
            foreach ($list as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $code = (string) ($row['code'] ?? '');
                if ($code === '') {
                    continue;
                }
                $byCode[$code] = $row;
            }
        }

        return array_values($byCode);
    }

    public function getStatus($id)
    {
        $employee = EmployeesNewJoiner::find($id);
        if (!$employee?->ongrid_id) {
            return response()->json(['error' => 'No OnGrid ID for this candidate.'], 404);
        }

        try {
            $live = $this->ongrid->getVerificationStatus($employee->ongrid_id);

            return response()->json([
                'message' => 'OK',
                'snapshot' => OnGrid::hrBgvSnapshot($employee),
                'live_status' => OnGrid::normalizeVerificationStatusRows($live),
                'data' => $live,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function ongridInviteList()
    {
        try {
            $individuals = collect($this->ongrid->listIndividuals()['individuals'] ?? []);
            // dd($individuals['0']['active'] === 'true'); // this is true data.
            // data active flase show olye true data.
            $activeIndividuals = $individuals->where('active', true)->values();
            // dd($activeIndividuals);
            return view('pages.ongridweb.index', ['individuals' => $activeIndividuals]);
        } catch (\Throwable $e) {
            return redirect()->route('EmployeeJoiner.ongridInviteList')
                ->with(['error' => $e->getMessage(), 'bg-color' => 'danger']);
        }
    }

    public function ongridInviteShow($invited)
    {
        try {
            $data = $this->ongrid->getIndividual($invited);

            return view('pages.ongridweb.show', compact('data'));
        } catch (\Throwable $e) {
            return redirect()->route('EmployeeJoiner.ongridInviteList')
                ->with(['error' => $e->getMessage(), 'bg-color' => 'danger']);
        }
    }

    public function getList()
    {
        try {
            return response()->json(['message' => 'OK', 'data' => $this->ongrid->listIndividuals()]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // public function deleteInvite($invited)
    // {
    //     // this is not used with initiate API.
    //     https://api-staging.ongrid.in/app/v1/community/78035/individual/181566
        
    //     $url = "{$this->ongrid->baseUrl}/community/{$this->ongrid->communityId}/individual/{$invited}";

    //     $response = Http::withToken($this->token)
    //         ->delete($url);
    
    //     return response()->json(
    //         $response->json(),
    //         $response->status()
    //     );
    //     // return response()->json(['error' => 'Not used with initiate API. Individual ID: ' . $invited], 400);
    // }

    public function getofferingList()
    {
        return response()->json([
            'message' => 'OK',
            'data' => OnGrid::offeringGroups(),
            'defaults' => OnGrid::defaultOfferingCodes(),
        ]);
    }
}
