<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 
use App\Models\User;
use App\Traits\Auditable;

class AnnualReportViewForm extends Model
{
  
use Auditable;
use SoftDeletes; // Add this

    protected $dates = ['deleted_at']; // Optional, helps with date casting

    protected $casts = [
    'remark' => 'array',
];

    public function viewer()
    {
        return $this->belongsTo(User::class, 'viewed_by', 'id');
    }
    public function approved()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }


}
