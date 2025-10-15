<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductRating;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    public function index(Request $request, $categorySlug = null, $subCategorySlug = null){
        $categorySelected = '';
        $subCategorySelected = '';
        $brandsArray = [];

        $categories = Category::orderBy('name','ASC')->with('sub_category')->where('status',1)->get();
        $brands = Brand::orderBy('name','ASC')->where('status',1)->get();

        //Apply filters here
        $products = Product::where('status',1);

        if(!empty($categorySlug)){
            $category = Category::where('slug', $categorySlug)->first();
            $products = $products->where('category_id', $category->id);
            $categorySelected = $category->id;
        }

        if(!empty($subCategorySlug)){
            $subCategory = SubCategory::where('slug', $subCategorySlug)->first();
            $products = $products->where('sub_category_id', $subCategory->id);
            $subCategorySelected = $subCategory->id;
        }

        if(!empty($request->get('brand'))){
            $brandsArray = explode(',', $request->get('brand'));
            $products = $products->whereIn('brand_id', $brandsArray);
        }

        if($request->get('price_max') != '' && $request->get('price_min') != ''){
            if($request->get('price_max') == 1000){
                $products = $products->whereBetween('price', [intval($request->get('price_min')), 1000000]);
            } else {
                $products = $products->whereBetween('price', [intval($request->get('price_min')), intval($request->get('price_max'))]);
            }
        }

        if(!empty($request->get('search'))){
            $products = $products->where('title','like','%'.$request->get('search').'%');
        }

        if($request->get('sort') != ''){
            if($request->get('sort') == 'latest'){
                $products = $products->orderBy('id','DESC');
            } else if($request->get('sort') == 'price_asc'){
                $products = $products->orderBy('price','ASC');
            } else{
                $products = $products->orderBy('price','DESC');
            }
        } else {
            $products = $products->orderBy('id','DESC');
        }

        $products = $products->paginate(6);

        $price_min = intval($request->get('price_min'));
        $price_max = (intval($request->get('price_max')) == 0) ? 1000 : $request->get('price_max');

        return view('front.shop',['categories'=>$categories,'brands'=>$brands,'products'=>$products,'categorySelected'=>$categorySelected,'subCategorySelected'=>$subCategorySelected,'brandsArray'=>$brandsArray,'price_max'=>$price_max,'price_min'=>$price_min,'sort'=>$request->get('sort')]);
    }

    public function product($slug){
        $product = Product::where('slug',$slug)
                    ->withCount('product_ratings')
                    ->withSum('product_ratings','rating')
                    ->with(['product_images','product_ratings'])->first();
        
        if($product == null){
            abort(404);
        }

        //Fetch related products
        $relatedProducts = [];
        if($product->related_products != ''){
            $productArray = explode(',',$product->related_products);
            $relatedProducts = Product::whereIn('id',$productArray)->where('status',1)->with('product_images')->get();
        }

        //Rating Calculation
        $avgRating = '0.00';
        $avgRatingPer = 0;
        if($product->product_ratings_count > 0){
            $avgRating = number_format(($product->product_ratings_sum_rating/$product->product_ratings_count),2);
            $avgRatingPer = ($avgRating*100)/5;
        }

        return view('front.product',['product'=>$product,'relatedProducts'=>$relatedProducts,'avgRating'=>$avgRating,'avgRatingPer'=>$avgRatingPer]);
    }

    public function saveRating($id,Request $request){
        $validator = Validator::make($request->all(),[
            'name' => 'required|min:5',
            'email' => 'required|email',
            'comment' => 'required|min:10',
            'rating' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['status'=>false,'errors'=>$validator->errors()]);
        } else {
            $count = ProductRating::where('email',$request->email)->where('product_id',$id)->count();

            if($count > 0){
                session()->flash('error','You already rated this product');
                return response()->json(['status'=>true]);
            }

            $productRating = new ProductRating;
            $productRating->product_id = $id;
            $productRating->username = $request->name;
            $productRating->email = $request->email;
            $productRating->comment = $request->comment;
            $productRating->rating = $request->rating;
            $productRating->status = 0;
            $productRating->save();

            session()->flash('success','Thanks for your rating');
            return response()->json(['status'=>true,'message'=>'Thanks for your rating']);
        }
    }
}
