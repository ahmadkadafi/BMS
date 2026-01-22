<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GraphicController extends Controller
{
    public function graphic()
    {
        return view('page.graphic'); 
    }
}
