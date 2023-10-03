<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateCategory;
use App\Http\Requests\RequestUpdateCategory;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Throwable;

class CategoryController extends Controller
{
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
            $category = Category::create($request->all());
            $thumbnail = $this->saveAvatar($request);
            $category->update([
                'thumbnail' => $thumbnail,
            ]);

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
            $category = Category::find($id);
            if ($request->hasFile('thumbnail')) {
                if ($category->thumbnail) {
                    File::delete($category->thumbnail);
                }
                $thumbnail = $this->saveAvatar($request);
                $category->update(array_merge($request->all(), ['thumbnail' => $thumbnail]));
            } else {
                $category->update($request->all());
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
            $category = Category::find($id);
            if ($category) {
                Article::where('id_category', $id)->update(['id_category' => null]);
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
                $categorys = Category::orderBy($orderBy, $orderDirection)
                    ->where('name', 'LIKE', '%' . $search . '%')
                    ->paginate(15);

                return response()->json([
                    'message' => 'Xem tất cả danh mục thành công !',
                    'category' => $categorys,
                ], 201);
            } else {
                $categorys = Category::all();

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
            $category = Category::find($id);
            if ($category) {
                return response()->json([
                    'message' => 'Lấy danh mục chi tiết thành công !',
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
