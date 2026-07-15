<?php

namespace App\Models;
use App\Models\RouteURLList;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class RoleRoutePermission extends Model
{
    use Auditable;
    protected $auditAllFields = true;         // log everything
    protected $auditExclude = []; // except this field

    protected $fillable = [
        'role_name',
        'status',
        'url_ids',
    ];

    protected $casts = [
        'url_ids' => 'array', // Automatically cast to array
    ];
       public function urlTitle()
    {
        return $this->belongsTo(RouteURLList::class, 'url_ids');
    }

 
}
