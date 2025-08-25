<?php

namespace Mllexx\IFS\Models;

use Illuminate\Database\Eloquent\Model;
    
class MyModel extends Model
{
    protected $table = 'my_models';

    protected $fillable = [
        'name',
        'email',
    ];

    public function getUpperCaseNameAttribute()
    {
        return strtoupper($this->name);
    }
}