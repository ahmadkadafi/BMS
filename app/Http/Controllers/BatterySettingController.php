<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BatterySettingController extends Controller
{
    public function batterysetting()
    {
        return view('page.batterysetting'); 
    }
}
