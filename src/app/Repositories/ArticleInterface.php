<?php

namespace App\Repositories;

/**
 * Interface ExampleRepository.
 */
interface ArticleInterface extends RepositoryInterface
{
    public static function getArticle($filter);

    public static function updateArticle($result, $data);
}
