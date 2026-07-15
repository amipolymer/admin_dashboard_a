<?php

namespace App\Models;
// RoleRoutePermission
use App\Models\RoleRoutePermission;
use App\Traits\Auditable;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use Auditable;
    protected $auditAllFields = true;         // log everything
    protected $auditExclude = []; // except this field

    protected $table = 'labours';

    protected $fillable = [
        'emp_id',
        'name',
        'role',
        'email',
        'phoneno',   
        'status',
        'remark',
        'addedBy',
    ];

    // Relationship with LabourRole model
    public function labourRole()
    {
        return $this->belongsTo(RoleRoutePermission::class, 'role', 'id');
    }

 
}
