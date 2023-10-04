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
            $result->update($data);
            DB::commit();

            return $result;
        } catch (Throwable $e) {
            DB::rollback();
            throw $e;
        }
    }

    public static function createArticle($data)
    {
        DB::beginTransaction();
        try {
            $new = (new self)->model->create($data);
            DB::commit();

            return $new;
        } catch (Throwable $e) {
            DB::rollback();
            throw $e;
        }
    }

    public static function findById($id)
    {
        return (new self)->model->find($id);
    }

    public static function searchAll($filter)
    {
        // leftjoin để khi mà id_category trong articles null thì vẫn kết hợp với bản categories để lấy ra
        $filter = (object) $filter;
        $data = (new self)->model->selectRaw('articles.*, categories.*, articles.id AS id_article, 
            articles.thumbnail AS thumbnail_article, categories.thumbnail AS thumbnail_categorie, categories.id AS id_category, 
            articles.created_at AS created_at_article, categories.created_at AS created_at_category,
            articles.updated_at AS updated_at_article, categories.updated_at AS updated_at_category')
            ->leftJoin('categories', 'articles.id_category', '=', 'categories.id')

            // all
            ->when(!empty($filter->search), function ($q) use ($filter) {
                $q->where(function ($query) use ($filter) {
                    $query->where('title', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('content', 'LIKE', '%' . $filter->search . '%');
                });
            })
            ->when(!empty($filter->name_category), function ($query) use ($filter) {
                return $query->where('name', '=', $filter->name_category);
            })
            ->when(!empty($filter->orderBy), function ($query) use ($filter) {
                $query->orderBy($filter->orderBy, $filter->orderDirection);
            })

            // detail
            ->when(!empty($filter->id), function ($query) use ($filter) {
                $query->where('articles.id', '=', $filter->id);
            })

            // doctor , admin
            ->when(!empty($filter->id_user), function ($query) use ($filter) {
                $query->where('id_user', '=', $filter->id_user);
            });

        return $data;
    }
}
