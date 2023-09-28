<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateCategory;
use App\Http\Requests\RequestUpdateCategory;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;

class CategoryController extends Controller
{
    public function saveAvatar(Request $request){
        if ($request->hasFile('thumbnail')) {
            $image = $request->file('thumbnail');
            $filename =  pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_category_' . time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/image/thumbnail/categories/', $filename);
            return 'storage/image/thumbnail/categories/' . $filename;
        }
    }

    public function add(RequestCreateCategory $request){
        try {
            $category = Category::create($request->all());
            $thumbnail = $this->saveAvatar($request);
            $category->update([
                'thumbnail' => $thumbnail,
            ]);
            return response()->json([
                'message' => 'Add category successfully ',
                'category' => $category
            ], 201);
        } 
        catch (QueryException $e) {
            return response()->json([
                'error' => $e,
            ], 500);
        }
    }

    public function edit(RequestUpdateCategory $request,$id){
        try {
            $category = Category::find($id);
            if($request->hasFile('thumbnail')) {
                if ($category->thumbnail) {
                    File::delete($category->thumbnail);
                }
                $thumbnail = $this->saveAvatar($request);
                $category->update(array_merge($request->all(),['thumbnail' => $thumbnail]));
            } else {
                $category->update($request->all());
            }
            return response()->json([
                'message' => 'Update name category successfully ',
                'category' => $category
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
            $category =  Category::find($id);
            if ($category) {
                Article::where("id_category",$id)->update(['id_category'=>null]); 
                $category->delete();
                return response()->json([
                    'message' => 'Delete category successfully',
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Not found category !',
                ], 404);
            }
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Delete Category false ',
            ], 400);
        }
    }

    public function all(Request $request)
    {
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
                'message' => 'Get all categorys successfully !',
                'category' => $categorys,
            ], 201);
        }
        else { // lấy cho product 
            $categorys = Category::all();
            return response()->json([
                'message' => 'Get all categorys successfully !',
                'category' => $categorys,
            ], 201);
        }
    }
    

    public function details(Request $request, $id){
        try {
            $category = Category::find($id); 
            if ($category) {
                return response()->json([
                    'message' => 'Get category details successfully !',
                    'category' => $category
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Not found category !',
                ], 404);
            }
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Delete Category false ',
            ], 400);
        }
    }
}