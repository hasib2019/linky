<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class ShiprocketCredential extends Model
{
    use PreventDemoModeChanges;
    protected $guarded = [];
}
