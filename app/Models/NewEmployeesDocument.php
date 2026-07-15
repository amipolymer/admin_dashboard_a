<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class NewEmployeesDocument extends Model
{
    use Auditable;

   protected $auditAllFields = true;         // log everything
   protected $auditExclude = []; // except this field
 
   protected $table = 'new_employees_documents';

    protected $fillable = [
        'emp_id',
        'emp_document_file',
        'emp_doc_date',
        'emp_select_document',
        'emp_document_file_path',
        'emp_document_status',
        'emp_hr_id',

    ];

    protected $casts = [
        'emp_document_file',
        'emp_select_document',
        'emp_document_file_path',
        'emp_document_status',
        'approval_date',
        'rejection_reason',
    ];


}