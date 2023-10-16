<?php

namespace App\Http\Controllers;

use App\Services\PublicService;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    protected PublicService $publicService;

    public function __construct(PublicService $publicService)
    {
        $this->publicService = $publicService;
    }

    public function readSearch(Request $request, $name, $id)
    {
        return $this->publicService->readSearch($request, $name, $id);
    }

}
