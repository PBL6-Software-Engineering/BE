<?php

namespace App\Http\Controllers;

use App\Models\Province;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    public function all() {
        return response()->json([
            'provinces' => Province::all(),
        ],200);
    }
}
