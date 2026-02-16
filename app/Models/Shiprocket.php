<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class Shiprocket extends Model
{
    use PreventDemoModeChanges;
    protected $guarded = [];
}
