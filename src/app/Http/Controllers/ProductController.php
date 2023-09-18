<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct() {
        $this->middleware('auth:admin_api', ['except' => ['upfile']]);
    }

    public function getCategory(Request $request){
        $search = $request->search;
        $category = Category::where('name','LIKE', '%'.$search.'%')->get();
        return response()->json([
            'message' => 'Get Category successfully !',
            'category' => $category
        ], 201);
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'=>'required|string',
            'warranty_period'=>'required|date',
            'description'=>'required|string',
            'category_id'=>'numeric|nullable',
            'price'=>'required|numeric',  
            'material'=>'required|string',
            'dimension'=>'required|string'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        $bytes = random_bytes(10);
        $transaction_id = (bin2hex($bytes)); // hash (random ra uri)

        $product = Product::create(array_merge(
            $validator->validated(),
            ['quantity' => 0,'uri' => $transaction_id] // tạo mới sản phẩm (chưa nhập kho thì cho số lượng là 0)  
        ));

        return response()->json([
            'message' => 'Add Product successfully !',
            'product' => $product
        ], 201);
    }

    // thực tế thì dưới client có 10 cái ảnh thì mình phải gọi 10 lệnh api upfile từng cái ảnh một 
    public function upfile(Request $request) {// photo là dưới client gửi lên 
        $pathToFile = $request->file('photo')->store('products','public'); // lưu vào folder products
        DB::table('images')->insert([
            'product_id'=>$request->id,
            'image_path'=> 'storage/'.$pathToFile
        ]);
        return response()->json([
            "link"=> 'storage/'.$pathToFile
        ],200);
    } 

    public function delete($id)
    {
        try {
            $product =  Product::find($id);
            if ($product) {
                OrderDetail::where("product_id",$id)->update(['product_id'=>null]); // set null cho OrderDetail
                ImportDetail::where("product_id",$id)->update(['product_id'=>null]);  // set null cho ImportDetail
                $imgs = Image::where("product_id",$id)->get(); // với ảnh thì không set null mà xóa hết các ảnh liên quan của product đó
                if($imgs){ // lấy ra các img có product_id đó (nếu có) và lặp qua từng cái 
                    foreach($imgs as $img){
                        File::delete($img->image_path); // xóa ảnh 
                        $i = Image::find($img->id);     // xóa hàng dữ liệu đó 
                        $i->delete();
                    }
                }
                $product->delete(); // xong hết rồi mới xóa product đó 
                return response()->json([
                    'message' => 'Delete Product successfully !',
                ], 201);
            }
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Delete Product false !',
            ], 400);
        }
    }

    public function update(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'name'=>'required|string',
            'warranty_period'=>'required|date',
            'description'=>'required|string',
            'category_id'=>'numeric|nullable', 
            'price'=>'required|numeric',  
            'material'=>'required|string',
            'dimension'=>'required|string'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        Product::find($id)->update(array_merge(
            $validator->validated()
        ));

        $removeimages = $request->removeimages;
        if($removeimages){ // nếu có tồn tại mảng các ảnh bị xóa thì mới xóa còn nếu string rỗng thì không có mảng 
            $idimgs = explode(',', $request->removeimages);
            foreach($idimgs as $idimg){ // lượt qua tất cả id của ảnh 
                $i = Image::find($idimg); // lấy ra dòng data của ảnh đó 
                File::delete($i->image_path); // xóa ảnh đó 
                $i->delete(); // xóa data của ảnh đó 
            }
        }


        return response()->json([
            'message' => 'Edit Product successfully ',
        ], 201); 
    }

    public function getProduct(Request $request,$uri){
        $product = Product::where('uri',$uri)->first();
        if($product) {
            $imgs = Image::where('product_id',$product->id)->get();
            return response()->json([
                'message' => 'Get Product successfully !',
                'product' => $product,
                'images' => $imgs
            ], 201);
        }
        else {
            return response()->json([
                'message' => 'Get Product false or product no found !',
            ], 201);
        }
    }

    public function allProducts(Request $request) {

        // mặc định 
        $col1='products.id';
        $col2='products.name';
        $orderb1='ASC';
        $orderb2='ASC';

        $sortlatest = $request->sortlatest;
        $sortname = $request->sortname;
        
        if($sortlatest == 'true' && $sortname == 'true'){
            // Tên z-a 
            $col1='products.name';
            $col2='products.id';
            $orderb1='DESC';
        }
        else {
            // Mới nhất 
            if($sortlatest == 'true') $orderb1='DESC';

            // Tên a-z
            if($sortname == 'true'){
                $col1='products.name';
                $col2='products.id';
            }
        }

        $search = $request->search;
        $products = Product::leftJoin('categories', function($join) {
            $join->on('products.category_id', '=', 'categories.id');
          })->orderBy($col1,$orderb1)->orderBy($col2,$orderb2)->where(function($query) use($search) {
            $query->where('products.name','LIKE', '%'.$search.'%')
            ->orWhere('categories.name','LIKE', '%'.$search.'%')
            ->orWhere('quantity','LIKE', '%'.$search.'%')
            ->orWhere('warranty_period','LIKE', '%'.$search.'%')
            ->orWhere('description','LIKE', '%'.$search.'%')
            ->orWhere('price','LIKE', '%'.$search.'%')
            ->orWhere('material','LIKE', '%'.$search.'%')
            ->orWhere('dimension','LIKE', '%'.$search.'%');
        })->select(
            'products.*','products.id as product_id','products.name as product_name',
            'categories.*','categories.id as category_id','categories.name as category_name'
        )->paginate(10);

        // vì products và categories đều có các cột id và name nên ta dùng AS 

        $products2 = Product::leftJoin('categories', function($join) {
            $join->on('products.category_id', '=', 'categories.id');
          })->orderBy($col1,$orderb1)->orderBy($col2,$orderb2)->where(function($query) use($search) {
            $query->where('products.name','LIKE', '%'.$search.'%')
            ->orWhere('categories.name','LIKE', '%'.$search.'%')
            ->orWhere('quantity','LIKE', '%'.$search.'%')
            ->orWhere('warranty_period','LIKE', '%'.$search.'%')
            ->orWhere('description','LIKE', '%'.$search.'%')
            ->orWhere('price','LIKE', '%'.$search.'%')
            ->orWhere('material','LIKE', '%'.$search.'%')
            ->orWhere('dimension','LIKE', '%'.$search.'%');
        })->select(
            'products.*','products.id as product_id','products.name as product_name',
            'categories.*','categories.id as category_id','categories.name as category_name'
        )->get();

        // vừa là chưa được phân loại nhưng trong những cái chưa được phân loại category đó
        // ta cũng phải search các thông tin của product được  
        if($request->unclassified == 'true'){ 
            $products = Product::leftJoin('categories', function($join) {
                $join->on('products.category_id', '=', 'categories.id');
            })->orderBy($col1,$orderb1)->orderBy($col2,$orderb2)->where(function($query) use($search) {
            $query->where('products.name','LIKE', '%'.$search.'%')
            ->orWhere('quantity','LIKE', '%'.$search.'%')
            ->orWhere('warranty_period','LIKE', '%'.$search.'%')
            ->orWhere('description','LIKE', '%'.$search.'%')
            ->orWhere('price','LIKE', '%'.$search.'%')
            ->orWhere('material','LIKE', '%'.$search.'%')
            ->orWhere('dimension','LIKE', '%'.$search.'%');
            })->whereNull('products.category_id')
            ->select(
                'products.*','products.id as product_id','products.name as product_name',
                'categories.*','categories.id as category_id','categories.name as category_name'
            )->paginate(10);
    
            $products2 = Product::leftJoin('categories', function($join) {
                $join->on('products.category_id', '=', 'categories.id');
            })->orderBy($col1,$orderb1)->orderBy($col2,$orderb2)->where(function($query) use($search) {
                $query->where('products.name','LIKE', '%'.$search.'%')
                ->orWhere('quantity','LIKE', '%'.$search.'%')
                ->orWhere('warranty_period','LIKE', '%'.$search.'%')
                ->orWhere('description','LIKE', '%'.$search.'%')
                ->orWhere('price','LIKE', '%'.$search.'%')
                ->orWhere('material','LIKE', '%'.$search.'%')
                ->orWhere('dimension','LIKE', '%'.$search.'%');
            })->whereNull('products.category_id')
            ->select(
                'products.*','products.id as product_id','products.name as product_name',
                'categories.*','categories.id as category_id','categories.name as category_name'
            )->get();
        }
        $idps = []; 

        foreach ($products as $product) { 
            array_push($idps,$product->product_id); 
        }

        $imgs = []; // mảng lưu các bộ ảnh 
        foreach($idps as $idp){ // lặp qua mảng id các sản phẩm 
            $image = Image::where('product_id',$idp)->get(); // ứng với mỗi id của product thì có bộ ảnh product đó 
            array_push($imgs,$image); // thêm bộ ảnh vào mảng ảnh 
        }

        $n = count($products2); 
        return response()->json([
            'quantity' => $n,
            'message' => 'Get all providers successfully !',
            'product' => $products,
            // 'image' => $images,
            // 'arr' => $arr,
            'img' => $imgs,
        ], 201);
    }

    public function allProducts2(Request $request) {

        // mặc định 
        $col1='products.quantity';
        $col2='products.name';
        $orderb1='ASC';
        $orderb2='ASC';

        $sortquantity = $request->sortquantity;
        $sortname = $request->sortname;
        
        if($sortquantity == 'true' && $sortname == 'true'){
            // Tên z-a 
            $col1='products.name';
            $col2='products.quantity';
            $orderb1='DESC';
        }
        else {
            // Mới nhất 
            if($sortquantity == 'true') $orderb1='DESC';

            // Tên a-z
            if($sortname == 'true'){
                $col1='products.name';
                $col2='products.quantity';
            }
        }

        $search = $request->search;
        $products = Product::leftJoin('categories', function($join) {
            $join->on('products.category_id', '=', 'categories.id');
          })->orderBy($col1,$orderb1)->orderBy($col2,$orderb2)->where(function($query) use($search) {
            $query->where('products.name','LIKE', '%'.$search.'%')
            ->orWhere('categories.name','LIKE', '%'.$search.'%')
            ->orWhere('quantity','LIKE', '%'.$search.'%')
            ->orWhere('warranty_period','LIKE', '%'.$search.'%')
            ->orWhere('description','LIKE', '%'.$search.'%')
            ->orWhere('price','LIKE', '%'.$search.'%')
            ->orWhere('material','LIKE', '%'.$search.'%')
            ->orWhere('dimension','LIKE', '%'.$search.'%');
        })->select(
            'products.*','products.id as product_id','products.name as product_name',
            'categories.*','categories.id as category_id','categories.name as category_name'
        )->paginate(20);


        $products2 = Product::leftJoin('categories', function($join) {
            $join->on('products.category_id', '=', 'categories.id');
          })->orderBy($col1,$orderb1)->orderBy($col2,$orderb2)->where(function($query) use($search) {
            $query->where('products.name','LIKE', '%'.$search.'%')
            ->orWhere('categories.name','LIKE', '%'.$search.'%')
            ->orWhere('quantity','LIKE', '%'.$search.'%')
            ->orWhere('warranty_period','LIKE', '%'.$search.'%')
            ->orWhere('description','LIKE', '%'.$search.'%')
            ->orWhere('price','LIKE', '%'.$search.'%')
            ->orWhere('material','LIKE', '%'.$search.'%')
            ->orWhere('dimension','LIKE', '%'.$search.'%');
        })->select(
            'products.*','products.id as product_id','products.name as product_name',
            'categories.*','categories.id as category_id','categories.name as category_name'
        )->get();

        if($request->unclassified == 'true'){ 
            $products = Product::leftJoin('categories', function($join) {
                $join->on('products.category_id', '=', 'categories.id');
            })->orderBy($col1,$orderb1)->orderBy($col2,$orderb2)->where(function($query) use($search) {
            $query->where('products.name','LIKE', '%'.$search.'%')
            ->orWhere('quantity','LIKE', '%'.$search.'%')
            ->orWhere('warranty_period','LIKE', '%'.$search.'%')
            ->orWhere('description','LIKE', '%'.$search.'%')
            ->orWhere('price','LIKE', '%'.$search.'%')
            ->orWhere('material','LIKE', '%'.$search.'%')
            ->orWhere('dimension','LIKE', '%'.$search.'%');
            })->whereNull('products.category_id')
            ->select(
                'products.*','products.id as product_id','products.name as product_name',
                'categories.*','categories.id as category_id','categories.name as category_name'
            )->paginate(20);
    
            $products2 = Product::leftJoin('categories', function($join) {
                $join->on('products.category_id', '=', 'categories.id');
            })->orderBy($col1,$orderb1)->orderBy($col2,$orderb2)->where(function($query) use($search) {
                $query->where('products.name','LIKE', '%'.$search.'%')
                ->orWhere('quantity','LIKE', '%'.$search.'%')
                ->orWhere('warranty_period','LIKE', '%'.$search.'%')
                ->orWhere('description','LIKE', '%'.$search.'%')
                ->orWhere('price','LIKE', '%'.$search.'%')
                ->orWhere('material','LIKE', '%'.$search.'%')
                ->orWhere('dimension','LIKE', '%'.$search.'%');
            })->whereNull('products.category_id')
            ->select(
                'products.*','products.id as product_id','products.name as product_name',
                'categories.*','categories.id as category_id','categories.name as category_name'
            )->get();
        }
        $idps = []; 

        foreach ($products as $product) {
            array_push($idps,$product->product_id); 
        }

        $imgs = []; 
        foreach($idps as $idp){ 
            $image = Image::where('product_id',$idp)->get(); 
            array_push($imgs,$image); 
        }

        $n = count($products2); 
        return response()->json([
            'quantity' => $n,
            'message' => 'Get all providers successfully !',
            'product' => $products,
            'img' => $imgs,
        ], 201);

    }
}
