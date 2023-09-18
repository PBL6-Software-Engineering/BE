<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateCategory;
use App\Http\Requests\RequestUpdateCategory;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class CategoryController extends Controller
{
    public function add(RequestCreateCategory $request){

        $category = Category::create(array_merge(
            $request->all()
        ));

        return response()->json([
            'message' => 'Add Name Category successfully ',
            'category' => $category
        ], 201);
    }

    public function edit(RequestUpdateCategory $request,$id){
        $category = Category::where("id",$id)->first();
        $category->update(array_merge(
            $request->all()
        ));
        return response()->json([
            'message' => 'Edit Name Category successfully ',
            'category' => $category
        ], 201);
    }

    public function delete($id)
    {
        try {
            $category =  Category::find($id);
            if ($category) {
                Product::where("category_id",$id)->update(['category_id'=>null]); 
                $category->delete();
                return response()->json([
                    'message' => 'Delete Category successfully',
                ], 201);
            }
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Delete Category false ',
            ], 400);
        }
    }

    public function all(Request $request)
    {
        if ($request->paginate) { // lấy cho category 
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
                ->paginate(21);
        
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
        $category = Category::find($id); 
        return response()->json([
            'message' => 'Get category details successfully !',
            'category' => $category
        ], 201);
    }
}