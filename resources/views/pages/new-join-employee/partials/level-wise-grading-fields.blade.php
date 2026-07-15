@php
    use App\Data\LevelWiseGrading;
    $employee = $employee ?? null;
    $gradingOptions = LevelWiseGrading::selectOptions();
    $selectedGrading = old('emp_level_grading', ($employee && ($employee->emp_grade ?? '') !== '' && ($employee->emp_category ?? '') !== '')
        ? LevelWiseGrading::optionValue($employee->emp_grade, $employee->emp_category)
        : '');
    $selectedDesignation = old('emp_role', $employee->emp_role ?? '');
@endphp

<div class="col-md-3 form-group">
    <label>Level Wise Grading <sup class="text-danger">*</sup></label>
    <select name="emp_level_grading" id="emp_level_grading" class="form-control" required>
        <option value="">Select grading level</option>
        @foreach ($gradingOptions as $option)
            <option value="{{ $option['value'] }}" @selected($selectedGrading === $option['value'])>
                {{ $option['label'] }}
            </option>
        @endforeach
    </select>
    @error('emp_level_grading')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

<div class="col-md-3 form-group">
    <label>Designation <sup class="text-danger">*</sup></label>
    <select name="emp_role" id="emp_role" class="form-control" required>
        <option value="">Select designation</option>
        @if ($selectedGrading !== '' && $selectedDesignation !== '')
            <option value="{{ $selectedDesignation }}" selected>{{ $selectedDesignation }}</option>
        @endif
    </select>
    @error('emp_role')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

<input type="hidden" name="emp_grade" id="emp_grade" value="{{ old('emp_grade', $employee?->emp_grade ?? '') }}">
<input type="hidden" name="emp_category" id="emp_category" value="{{ old('emp_category', $employee?->emp_category ?? '') }}">

@once
    @push('js')
        <script>
            (function () {
                const designationsByGrading = @json(collect($gradingOptions)->mapWithKeys(fn ($o) => [$o['value'] => $o['designations']])->all());
                const gradingSelect = document.getElementById('emp_level_grading');
                const designationSelect = document.getElementById('emp_role');
                const gradeInput = document.getElementById('emp_grade');
                const categoryInput = document.getElementById('emp_category');
                const initialDesignation = @json($selectedDesignation);

                function parseGrading(value) {
                    if (!value || value.indexOf('|') === -1) return { grade: '', category: '' };
                    const parts = value.split('|');
                    return { grade: parts[0] || '', category: parts[1] || '' };
                }

                function fillDesignations() {
                    const value = gradingSelect ? gradingSelect.value : '';
                    const parsed = parseGrading(value);
                    if (gradeInput) gradeInput.value = parsed.grade;
                    if (categoryInput) categoryInput.value = parsed.category;
                    if (!designationSelect) return;

                    const list = designationsByGrading[value] || [];
                    const current = designationSelect.value;
                    designationSelect.innerHTML = '<option value="">Select designation</option>';
                    list.forEach(function (item) {
                        const opt = document.createElement('option');
                        opt.value = item;
                        opt.textContent = item;
                        if (item === current || (list.length === 1 && item === initialDesignation)) {
                            opt.selected = true;
                        }
                        designationSelect.appendChild(opt);
                    });
                    if (current && list.indexOf(current) === -1 && initialDesignation && list.indexOf(initialDesignation) !== -1) {
                        designationSelect.value = initialDesignation;
                    }
                }

                if (gradingSelect) {
                    gradingSelect.addEventListener('change', function () {
                        if (designationSelect) designationSelect.value = '';
                        fillDesignations();
                    });
                    fillDesignations();
                }
            })();
        </script>
    @endpush
@endonce
