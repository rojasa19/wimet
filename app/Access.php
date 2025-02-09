<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Access extends Model
{
    protected $fillable = ['id', 'nombre', 'icon'];
    protected $hidden = ['pivot'];
}
