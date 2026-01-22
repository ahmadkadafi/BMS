<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LineController extends Controller
{
    public function line()
    {
        return view('page.line'); 
    }
}
