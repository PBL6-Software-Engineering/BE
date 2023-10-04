<?php

namespace App\Services;

use App\Http\Requests\RequestCreateCategory;
use App\Http\Requests\RequestUpdateCategory;
use App\Models\Category;
use App\Repositories\ArticleRepository;
use App\Repositories\CategoryInterface;
use App\Repositories\CategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Throwable;

class CategoryService
{
    protected CategoryInterface $categoryRepository;

    public function __construct(
        CategoryInterface $categoryRepository
    ) {
        $this->categoryRepository = $categoryRepository;
    }

    public function saveAvatar(Request $request)
    {
        if ($request->hasFile('thumbnail')) {
            $image = $request->file('thumbnail');
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_category_' . time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/image/thumbnail/categories/', $filename);

            return 'storage/image/thumbnail/categories/' . $filename;
        }
    }

    public function add(RequestCreateCategory $request)
    {
        try {
            $category = CategoryRepository::createCategory($request->all());
            $thumbnail = $this->saveAvatar($request);
            $data = [
                'thumbnail' => $thumbnail,
            ];
            $category = CategoryRepository::updateCategory($category->id, $data);

            return response()->json([
                'message' => 'Thêm danh mục thành công !',
                'category' => $category,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function edit(RequestUpdateCategory $request, $id)
    {
        try {
            $category = CategoryRepository::getCategory(['id' => $id])->first();
            if ($request->hasFile('thumbnail')) {
                if ($category->thumbnail) {
                    File::delete($category->thumbnail);
                }
                $thumbnail = $this->saveAvatar($request);
                $data = array_merge($request->all(), ['thumbnail' => $thumbnail]);
                $category = CategoryRepository::updateCategory($category->id, $data);
            } else {
                $category = CategoryRepository::updateCategory($category->id, $request->all());
            }

            return response()->json([
                'message' => 'Cập nhật thông tin danh mục thành công !',
                'category' => $category,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function delete($id)
    {
        try {
            $category = CategoryRepository::getCategory(['id' => $id])->first();
            if ($category) {
                $article = ArticleRepository::getArticle(['id_category' => $id]);
                ArticleRepository::updateArticle($article, ['id_category' => null]);

                if ($category->thumbnail) {
                    File::delete($category->thumbnail);
                }
                $category->delete();

                return response()->json([
                    'message' => 'Xóa danh mục thành công !',
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Không tìm thấy danh mục !',
                ], 404);
            }
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function all(Request $request)
    {
        try {
            if ($request->paginate == true) { // lấy cho category
                $search = $request->search;
                $orderBy = 'id';
                $orderDirection = 'ASC';

                if ($request->sortlatest == 'true') {
                    $orderBy = 'id';
                    $orderDirection = 'DESC';
                }

                if ($request->sortname == 'true') {
                    $orderBy = 'name';
                    $orderDirection = ($request->sortlatest == 'true') ? 'DESC' : 'ASC';
                }

                $filter = (object) [
                    'orderBy' => $orderBy,
                    'orderDirection' => $orderDirection,
                    'search' => $search,
                ];
                $categorys = CategoryRepository::searchCategory($filter)->paginate(15);

                return response()->json([
                    'message' => 'Xem tất cả danh mục thành công !',
                    'category' => $categorys,
                ], 201);
            } else {
                $categorys = CategoryRepository::getCategory([])->get();

                return response()->json([
                    'message' => 'Xem tất cả danh mục thành công !',
                    'category' => $categorys,
                ], 201);
            }
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function details(Request $request, $id)
    {
        try {
            $category = CategoryRepository::getCategory(['id' => $id])->first();
            if ($category) {
                return response()->json([
                    'message' => 'Xem danh mục chi tiết thành công !',
                    'category' => $category,
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Không tìm thấy danh mục !',
                ], 404);
            }
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
