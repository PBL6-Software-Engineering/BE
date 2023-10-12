<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateArticle;
use App\Http\Requests\RequestUpdateArticle;
use App\Services\ArticleService;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    protected ArticleService $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    public function add(RequestCreateArticle $request)
    {
        return $this->articleService->add($request);
    }

    public function edit(RequestUpdateArticle $request, $id)
    {
        return $this->articleService->edit($request, $id);
    }

    public function hideShow(Request $request, $id)
    {
        return $this->articleService->hideShow($request, $id);
    }

    public function changeAccept(Request $request, $id)
    {
        return $this->articleService->changeAccept($request, $id);
    }

    public function delete($id)
    {
        return $this->articleService->delete($id);
    }

    public function all(Request $request)
    {
        return $this->articleService->all($request);
    }

    public function details(Request $request, $id)
    {
        return $this->articleService->details($request, $id);
    }
}
