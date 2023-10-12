<?php

namespace App\Services;

use App\Http\Requests\RequestCreateArticle;
use App\Http\Requests\RequestUpdateArticle;
use App\Repositories\ArticleInterface;
use App\Repositories\CategoryRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Throwable;

class ArticleService
{
    protected ArticleInterface $articleRepository;

    public function __construct(ArticleInterface $articleRepository)
    {
        $this->articleRepository = $articleRepository;
    }

    public function responseOK($status = 200, $data = null, $message = '')
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'status' => $status,
        ], $status);
    }

    public function responseError($status = 400, $message = '')
    {
        return response()->json([
            'message' => $message,
            'status' => $status,
        ], $status);
    }

    public function saveAvatar(Request $request)
    {
        if ($request->hasFile('thumbnail')) {
            $image = $request->file('thumbnail');
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_article_' . time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/image/thumbnail/articles/', $filename);

            return 'storage/image/thumbnail/articles/' . $filename;
        }
    }

    public function add(RequestCreateArticle $request)
    {
        try {
            $category = CategoryRepository::getCategory(['id' => $request->id_category])->first();
            if (empty($category)) {
                return $this->responseError(400, 'Danh mục không tồn tại !');
            }
            $article = $this->articleRepository->createArticle($request->all());
            $thumbnail = $this->saveAvatar($request);

            $id_user = null;
            $is_accept = true;
            $user = Auth::user();
            if (in_array($user->role, ['doctor', 'hospital'])) {
                $id_user = $user->id;
                $is_accept = false;
            }
            $data = [
                'thumbnail' => $thumbnail,
                'is_accept' => $is_accept,
                'is_show' => true,
                'id_user' => $id_user,
            ];
            $article = $this->articleRepository->updateArticle($article, $data);

            return $this->responseOK(200, $article, 'Thêm bài viết thành công !');
        } catch (Throwable $e) {
            return $this->responseError(400, $e->getMessage());
        }
    }

    public function edit(RequestUpdateArticle $request, $id)
    {
        try {
            $user = UserRepository::findUserById(auth('user_api')->user()->id);
            $article = $this->articleRepository->findById($id);
            if ($user->id != $article->id_user) {
                return $this->responseError(400, 'Bạn không có quyền chỉnh sửa bài viết này !');
            }
            if ($request->hasFile('thumbnail')) {
                if ($article->thumbnail) {
                    File::delete($article->thumbnail);
                }
                $thumbnail = $this->saveAvatar($request);
                $data = array_merge($request->all(), ['thumbnail' => $thumbnail]);
                $article = $this->articleRepository->updateArticle($article, $data);
            } else {
                $article = $this->articleRepository->updateArticle($article, $request->all());
            }

            return $this->responseOK(200, $article, 'Cập nhật bài viết thành công !');
        } catch (Throwable $e) {
            return $this->responseError(400, $e->getMessage());
        }
    }

    public function hideShow(Request $request, $id)
    {
        try {
            $article = $this->articleRepository->findById($id);
            $article = $this->articleRepository->updateArticle($article, ['is_show' => $request->is_show]);

            return $this->responseOK(200, $article, 'Thay đổi trạng thái hiển thị của bài viết thành công !');
        } catch (Throwable $e) {
            return $this->responseError(400, $e->getMessage());
        }
    }

    public function changeAccept(Request $request, $id)
    {
        try {
            $article = $this->articleRepository->findById($id);
            $article = $this->articleRepository->updateArticle($article, ['is_accept' => $request->is_accept]);

            return $this->responseOK(200, $article, 'Thay đổi trạng thái của bài viết thành công !');
        } catch (Throwable $e) {
            return $this->responseError(400, $e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        try {
            $id = $request->id;
            $user = Auth::user();
            $article = $this->articleRepository->findById($id);
            if(in_array($user->role, ['admin', 'superadmin', 'manager']) && $article->id_user == null){
                if ($article->thumbnail) {
                    File::delete($article->thumbnail);
                }
                $article->delete();
                return $this->responseOK(200, null, 'Xóa bài viết thành công !');
            }
            if ($user->id != $article->id_user) {
                return $this->responseError(400, 'Bạn không có quyền xóa bài viết này !');
            }
            
            if ($article->thumbnail) {
                File::delete($article->thumbnail);
            }
            $article->delete();
            return $this->responseOK(200, null, 'Xóa bài viết thành công !');
        } catch (Throwable $e) {
            return $this->responseError(400, $e->getMessage());
        }
    }

    public function all(Request $request)
    {
        try {
            // search theo name của category thì search theo select , option
            $name_category = '';
            if (!empty($request->name_category)) {
                $name_category = $request->name_category;
            }
            $search = $request->search;
            $orderBy = 'articles.id';
            $orderDirection = 'ASC';

            if ($request->sortlatest == 'true') {
                $orderBy = 'articles.id';
                $orderDirection = 'DESC';
            }

            if ($request->sortname == 'true') {
                $orderBy = 'articles.title';
                $orderDirection = ($request->sortlatest == 'true') ? 'DESC' : 'ASC';
            }

            $filter = (object) [
                'search' => $search,
                'name_category' => $name_category,
                'orderBy' => $orderBy,
                'orderDirection' => $orderDirection,
                'is_accept' => $request->is_accept ?? 'both',
                'is_show' => $request->is_show ?? 'both',
            ];

            if(!empty($request->id_user) && $request->id_user == 'admin'){
                $filter->id_user = 'admin';
            } 
            else if(!empty($request->id_user) && $request->id_user != 'admin') {
                $user = UserRepository::findUserById( $request->id_user);
                if(empty($user)) return $this->responseError(400, 'Không tìm thấy người dùng !');
                $filter->id_user = $request->id_user;
            } else {}

            if (!(empty($request->paginate))) {
                $articles = $this->articleRepository->searchAll($filter)->paginate($request->paginate);
            }
            else {
                $articles = $this->articleRepository->searchAll($filter)->get();
            }
            return $this->responseOK(200, $articles, 'Xem tất cả bài viết thành công !');
        } catch (Throwable $e) {
            return $this->responseError(400, $e->getMessage());
        }
    }

    public function details(Request $request, $id)
    {
        try {
            $filter = (object) [
                'id' => $id,
            ];
            $article = $this->articleRepository->searchAll($filter)->first();
            if ($article) {
                return $this->responseOK(200, $article, 'Xem bài viết chi tiết thành công !');
            } else {
                return $this->responseError(400, 'Không tìm thấy bài viết !');
            }
        } catch (Throwable $e) {
            return $this->responseError(400, $e->getMessage());
        }
    }
}
