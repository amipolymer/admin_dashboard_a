<?php

namespace App\Support;

use App\Data\DocumentNamesList;
use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;

class OnboardingSrHrDocumentList
{
    /**
     * @return list<array{id: int, label: string, file: string, status: string, uploaded_at: ?string, view_url: string, key: string}>
     */
    public static function items(EmployeesNewJoiner $employee, string $token): array
    {
        $labels = DocumentNamesList::collapsedLabels();

        return NewEmployeesDocument::query()
            ->where('emp_id', $employee->id)
            ->where('emp_document_status', '!=', 'upload')
            ->whereNotNull('emp_document_file_path')
            ->where('emp_document_file_path', '!=', '-')
            ->where('emp_select_document', '!=', 'emergency_contact')
            ->orderByDesc('emp_doc_date')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (NewEmployeesDocument $doc) => OnboardingLetterDocument::absolutePath($doc) !== null)
            ->map(function (NewEmployeesDocument $doc) use ($labels, $token) {
                return [
                    'id' => (int) $doc->id,
                    'label' => $labels[$doc->emp_select_document] ?? ($doc->emp_select_document ?? 'Document'),
                    'file' => (string) ($doc->emp_document_file ?? basename((string) $doc->emp_document_file_path)),
                    'status' => (string) ($doc->emp_document_status ?? '—'),
                    'uploaded_at' => $doc->emp_doc_date ? (string) $doc->emp_doc_date : null,
                    'view_url' => route('sr-hr.approval.document', [
                        'token' => $token,
                        'document' => $doc->id,
                    ]),
                    'key' => (string) ($doc->emp_select_document ?? ''),
                ];
            })
            ->values()
            ->all();
    }
}
