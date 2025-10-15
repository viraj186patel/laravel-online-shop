<?php

namespace App\Http\Controllers\admin;

use App\Models\ProductImage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProductImageController extends Controller
{
    public function update(Request $request){
        $image = $request->image;
        $ext = $image->getClientOriginalExtension();
        $sPath = $image->getPathName();

        $productImage = new ProductImage();
        $productImage->product_id = $request->product_id;
        $productImage->image = 'NULL';
        $productImage->save();

        $imageName = $request->product_id.'-'.$productImage->id.'-'.time().'.'.$ext;
        $productImage->image = $imageName;
        $productImage->save();

        //Generate Product thumbnail
        //large
        $dPath = public_path().'/uploads/product/large/'.$imageName;
        $imageManager = new ImageManager(new Driver());
        $img = $imageManager->read($sPath);
        $img->scaleDown(1400);
        $img->save($dPath);

        //small
        $dPath = public_path().'/uploads/product/small/'.$imageName;
        $imageManager = new ImageManager(new Driver());
        $img = $imageManager->read($sPath);
        $img->cover(300,300);
        $img->save($dPath);

        return response()->json(['status'=>true,'image_id'=>$productImage->id,'ImagePath'=>asset('uploads/product/small/'.$productImage->image),'message'=>'Image saved successfully.']);
    }

    public function destroy(Request $request){
        $productImage = ProductImage::find($request->id);

        if(empty($productImage)){
            return response()->json(['status'=>false,'message'=>'Image not found.']);
        }

        //Delete images from dir
        File::delete(public_path('uploads/product/large/'.$productImage->image));
        File::delete(public_path('uploads/product/small/'.$productImage->image));

        $productImage->delete();

        return response()->json(['status'=>true,'message'=>'Image deleted successfully.']);
    }
}
