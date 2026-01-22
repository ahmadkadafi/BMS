<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LoggerController extends Controller
{
    public function logger()
    {
        return view('page.logger'); 
    }
}
