<?php

namespace App\Repositories;

use App\Models\Article;
use Illuminate\Support\Facades\DB;
use Throwable;

class ArticleRepository extends BaseRepository implements ArticleInterface
{
    public function getModel()
    {
        return Article::class;
    }

    public static function getArticle($filter)
    {
        $filter = (object) $filter;
        $data = (new self)->model
            ->when(!empty($filter->id_category), function ($q) use ($filter) {
                $q->where('id_category', $filter->id_category);
            });

        return $data;
    }

    public static function updateArticle($result, $data)
    {
        DB::beginTransaction();
        try {
            $newResult = $result->update($data);
            DB::commit();

            return $newResult;
        } catch (Throwable $e) {
            DB::rollback();
            throw $e;
        }
    }
}
