<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateArticle;
use App\Http\Requests\RequestCreateDepartment;
use App\Http\Requests\RequestUpdateArticle;
use App\Http\Requests\RequestUpdateDepartment;
use App\Models\Article;
use App\Models\Department;
use App\Models\HospitalDepartment;
use App\Models\InforDoctor;
use App\Models\InforHospital;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;

class ArticleController extends Controller
{
    public function saveAvatar(Request $request){
        if ($request->hasFile('thumbnail')) {
            $image = $request->file('thumbnail');
            $filename =  pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_article_' . time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/image/thumbnail/articles/', $filename);
            return 'storage/image/thumbnail/articles/' . $filename;
        }
    }

    public function add(RequestCreateArticle $request){
        try {
            $article = Article::create(array_merge(
                $request->all()
            ));
            $thumbnail = $this->saveAvatar($request);
    
            $id_user = null;
            $is_accept = true;
            $user = Auth::user();
            if (in_array($user->role, ['doctor', 'hospital'])) {
                $id_user = $user->id;
                $is_accept = false;
            }
            $article->update([
                'thumbnail' => $thumbnail,
                'is_accept' => $is_accept,
                'is_show' => true,
                'id_user' => $id_user
            ]);
            return response()->json([
                'message' => 'Add article successfully ',
                'article' => $article
            ], 201);
        } 
        catch (QueryException $e) {
            return response()->json([
                'error' => $e,
            ], 500);
        }
    }

    public function edit(RequestUpdateArticle $request,$id){
        try {
            $user = User::find(auth('user_api')->user()->id);
            $article = Article::find($id);
            if($user->id != $article->id_user) {
                return response()->json([
                    'message' => 'You do not have permission to edit this article !',
                ], 403);
            }
            if($request->hasFile('thumbnail')) {
                if ($article->thumbnail) {
                    File::delete($article->thumbnail);
                }
                $thumbnail = $this->saveAvatar($request);
                $article->update(array_merge($request->all(),['thumbnail' => $thumbnail]));
            } else {
                $article->update($request->all());
            }
            return response()->json([
                'message' => 'Update article successfully ',
                'article' => $article
            ], 201);
        } 
        catch (QueryException $e) {
            return response()->json([
                'error' => $e,
            ], 500);
        }
    }

    public function hideShow(Request $request, $id) {
        try {
            $article = Article::find($id);
            $article->update(['is_show' => $request->is_show]);
            return response()->json([
                'message' => 'Change Status Article Successfully ',
                'article' => $article
            ], 201);
        } 
        catch (QueryException $e) {
            return response()->json([
                'error' => $e,
            ], 500);
        }
    }

    public function changeAccept(Request $request, $id) {
        try {
            $article = Article::find($id);
            $article->update(['is_accept' => $request->is_accept]);
            return response()->json([
                'message' => 'Change Status Accept Successfully ',
                'article' => $article
            ], 201);
        } 
        catch (QueryException $e) {
            return response()->json([
                'error' => $e,
            ], 500);
        }
    }
    

    public function delete($id)
    {
        try {
            $user = User::find(auth('user_api')->user()->id);
            $article = Article::find($id);
            if($user->id != $article->id_user) {
                return response()->json([
                    'message' => 'You do not have permission to delete this article !',
                ], 403);
            }
            if ($article->thumbnail) {
                File::delete($article->thumbnail);
            }
            $article->delete();
            return response()->json([
                'message' => 'Delete article successfully ',
            ], 201);
        } 
        catch (QueryException $e) {
            return response()->json([
                'error' => $e,
            ], 500);
        }
    }

    public function all(Request $request)
    {
        // search theo name của category thì search theo select , option 
        $name_category = '';
        if(!empty($request->name_category)) $name_category = $request->name_category; 

        $search = $request->search;
        $orderBy = 'id';
        $orderDirection = 'ASC';
    
        if ($request->sortlatest == 'true') {
            $orderBy = 'id';
            $orderDirection = 'DESC';
        }
    
        if ($request->sortname == 'true') {
            $orderBy = 'title';
            $orderDirection = ($request->sortlatest == 'true') ? 'DESC' : 'ASC';
        }
    
        // leftjoin để khi mà id_category trong articles null thì vẫn kết hợp với bản categories để lấy ra 
        $articles = Article::selectRaw('articles.*, categories.*, articles.id AS id_article, 
        articles.thumbnail AS thumbnail_article, categories.thumbnail AS thumbnail_categorie, categories.id AS id_category, 
        articles.created_at AS created_at_article, categories.created_at AS created_at_category,
        articles.updated_at AS updated_at_article, categories.updated_at AS updated_at_category')
        ->leftJoin('categories', 'articles.id_category', '=', 'categories.id')
        ->where(function ($query) use ($search) {
            $query->where('title', 'LIKE', '%' . $search . '%')
                ->orWhere('content', 'LIKE', '%' . $search . '%');
        })
        // chỉ khi chọn name_category thì mới search , còn không thì vẫn lấy ra những bài viết có name_category null 
        ->when($name_category, function ($query, $name_category) {
            return $query->where('name', 'LIKE', '%' . $name_category . '%');
        })
        ->orderBy('articles.id', $orderDirection)
        ->paginate(6);
    
        return response()->json([
            'message' => 'Get all articles successfully !',
            'article' => $articles,
        ], 201);
    }

    public function details(Request $request, $id){
        $article = Article::selectRaw('articles.*, categories.*, articles.id AS id_article, 
        articles.thumbnail AS thumbnail_article, categories.thumbnail AS thumbnail_categorie, categories.id AS id_category, 
        articles.created_at AS created_at_article, categories.created_at AS created_at_category,
        articles.updated_at AS updated_at_article, categories.updated_at AS updated_at_category')
        ->leftJoin('categories', 'articles.id_category', '=', 'categories.id')
        ->where('articles.id', '=', $id)
        ->first();
        if($article) {
            return response()->json([
                'message' => 'Get article details successfully !',
                'article' => $article
            ], 201);
        }
        else {
            return response()->json([
                'message' => 'Not found article !',
                'article' => $article
            ], 404);
        }
    }

    public function articleOfDoctor(Request $request, $id)
    {
        // search theo name của category thì search theo select , option 
        $name_category = '';
        if(!empty($request->name_category)) $name_category = $request->name_category; 

        $search = $request->search;
        $orderBy = 'id';
        $orderDirection = 'ASC';
    
        if ($request->sortlatest == 'true') {
            $orderBy = 'id';
            $orderDirection = 'DESC';
        }
    
        if ($request->sortname == 'true') {
            $orderBy = 'title';
            $orderDirection = ($request->sortlatest == 'true') ? 'DESC' : 'ASC';
        }
    
        // leftjoin để khi mà id_category trong articles null thì vẫn kết hợp với bản categories để lấy ra 
        $articles = Article::selectRaw('articles.*, categories.*, articles.id AS id_article, 
        articles.thumbnail AS thumbnail_article, categories.thumbnail AS thumbnail_categorie, categories.id AS id_category, 
        articles.created_at AS created_at_article, categories.created_at AS created_at_category,
        articles.updated_at AS updated_at_article, categories.updated_at AS updated_at_category')
        ->leftJoin('categories', 'articles.id_category', '=', 'categories.id')
        ->where(function ($query) use ($search) {
            $query->where('title', 'LIKE', '%' . $search . '%')
                ->orWhere('content', 'LIKE', '%' . $search . '%');
        })
        // chỉ khi chọn name_category thì mới search , còn không thì vẫn lấy ra những bài viết có name_category null 
        ->when($name_category, function ($query, $name_category) {
            return $query->where('name', 'LIKE', '%' . $name_category . '%');
        })
        ->where('id_user', '=', $id)
        ->orderBy('articles.id', $orderDirection)
        ->paginate(6);
    
        return response()->json([
            'message' => 'Get all articles successfully !',
            'article' => $articles,
        ], 201);
    }

    public function articleOfAdmin(Request $request)
    {
        // search theo name của category thì search theo select , option 
        $name_category = '';
        if(!empty($request->name_category)) $name_category = $request->name_category; 

        $search = $request->search;
        $orderBy = 'id';
        $orderDirection = 'ASC';
    
        if ($request->sortlatest == 'true') {
            $orderBy = 'id';
            $orderDirection = 'DESC';
        }
    
        if ($request->sortname == 'true') {
            $orderBy = 'title';
            $orderDirection = ($request->sortlatest == 'true') ? 'DESC' : 'ASC';
        }
    
        // leftjoin để khi mà id_category trong articles null thì vẫn kết hợp với bản categories để lấy ra 
        $articles = Article::selectRaw('articles.*, categories.*, articles.id AS id_article, 
        articles.thumbnail AS thumbnail_article, categories.thumbnail AS thumbnail_categorie, categories.id AS id_category, 
        articles.created_at AS created_at_article, categories.created_at AS created_at_category,
        articles.updated_at AS updated_at_article, categories.updated_at AS updated_at_category')
        ->leftJoin('categories', 'articles.id_category', '=', 'categories.id')
        ->where(function ($query) use ($search) {
            $query->where('title', 'LIKE', '%' . $search . '%')
                ->orWhere('content', 'LIKE', '%' . $search . '%');
        })
        // chỉ khi chọn name_category thì mới search , còn không thì vẫn lấy ra những bài viết có name_category null 
        ->when($name_category, function ($query, $name_category) {
            return $query->where('name', 'LIKE', '%' . $name_category . '%');
        })
        ->where('id_user', null)
        ->orderBy('articles.id', $orderDirection)
        ->paginate(6);
    
        return response()->json([
            'message' => 'Get all articles successfully !',
            'article' => $articles,
        ], 201);
    }
    
}