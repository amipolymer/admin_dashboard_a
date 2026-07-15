<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteURLList extends Model
{
    protected $table = 'route_u_r_l_lists';

    protected $fillable = [
        'url_name',
        'title',
    ];
}
