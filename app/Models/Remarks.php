<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remarks extends Model
{
    protected $fillable = [
        'remarkable_id',
        'remarkable_type',
        'remark',
        'created_by',
        'updated_by'
    ];

    public function remarkable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function scopeOfRemarkableType($query, $type)
    {
        return $query->where('remarkable_type', $type);
    }
}
