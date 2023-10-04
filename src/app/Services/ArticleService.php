<?php

namespace App\Services;

use App\Repositories\ArticleInterface;

class ArticleService
{
    protected ArticleInterface $articleRepository;

    public function __construct(ArticleInterface $articleRepository)
    {
        $this->articleRepository = $articleRepository;
    }
}
