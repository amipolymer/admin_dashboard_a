<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class QuickLink extends Model
{
    // use Auditable;
    
    // protected $auditAllFields = true;         // log everything
    // protected $auditExclude = []; // except this field

    protected $table = 'quick_links';

    protected $fillable = [
        'srno',
        'name',
        'url',
        'logo',
        'openurl',
        'status',
        'added_by',
        'delete_at',
    ];

  
}
