<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DiscountCoupon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class DiscountCodeController extends Controller
{
    public function index(Request $request){
        $discountCoupons = DiscountCoupon::latest();

        if(!empty($request->get('keyword'))){
            $discountCoupons = $discountCoupons->where('name','like','%'.$request->get('keyword').'%');
            $discountCoupons = $discountCoupons->orWhere('code','like','%'.$request->get('keyword').'%');
        }

        $discountCoupons = $discountCoupons->paginate(10);
        return view('admin.coupon.list',['discountCoupons'=>$discountCoupons]);
    }

    public function create(){
        return view('admin.coupon.create');
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(),[
            'code' => 'required',
            'type' => 'required',
            'discount_amount' => 'required|numeric',
            'status' => 'required'
        ]);

        if($validator->passes()){
            //Start date must be greater than current date
            if(!empty($request->starts_at)){
                $now = Carbon::now();
                $startsAt = Carbon::createFromFormat('Y-m-d H:i:s',$request->starts_at);

                if($startsAt->lte($now) == true){
                    return response()->json(['status'=>false,'errors'=>['starts_at'=>'Start date can not be less than current date']]);
                }
            }

            //Expire date must be greater than start date
            if(!empty($request->expires_at) && !empty($request->starts_at)){
                $startsAt = Carbon::createFromFormat('Y-m-d H:i:s',$request->starts_at);
                $expiresAt = Carbon::createFromFormat('Y-m-d H:i:s',$request->expires_at);

                if($expiresAt->gt($startsAt) == false){
                    return response()->json(['status'=>false,'errors'=>['expires_at'=>'Expiry date must be greater than start date']]);
                }
            }

            $discountCode = new DiscountCoupon();
            $discountCode->code = $request->code;
            $discountCode->name = $request->name;
            $discountCode->description = $request->description;
            $discountCode->max_uses = $request->max_uses;
            $discountCode->max_uses_user = $request->max_uses_user;
            $discountCode->type = $request->type;
            $discountCode->discount_amount = $request->discount_amount;
            $discountCode->min_amount = $request->min_amount;
            $discountCode->status = $request->status;
            $discountCode->starts_at = $request->starts_at;
            $discountCode->expires_at = $request->expires_at;
            $discountCode->save();

            session()->flash('success','Discount Coupon added successfully');
            return response()->json(['status'=>true,'message'=>'Discount Coupon added successfully']);
        } else {
            return response()->json(['status'=>false,'errors'=>$validator->errors()]);
        }
    }

    public function edit(Request $request, $id){
        $coupon = DiscountCoupon::find($id);

        if($coupon == null){
            session()->flash('error','Record not found');
            return redirect()->route('coupons.index');
        }

        return view('admin.coupon.edit',['coupon'=>$coupon]);
    }

    public function update(Request $request, $id){
        $discountCode = DiscountCoupon::find($id);

        if($discountCode == null){
            session()->flash('error','Record not found');
            return response()->json(['status'=>true]);
        }

        $validator = Validator::make($request->all(),[
            'code' => 'required',
            'type' => 'required',
            'discount_amount' => 'required|numeric',
            'status' => 'required'
        ]);

        if($validator->passes()){

            //Expire date must be greater than start date
            if(!empty($request->expires_at) && !empty($request->starts_at)){
                $startsAt = Carbon::createFromFormat('Y-m-d H:i:s',$request->starts_at);
                $expiresAt = Carbon::createFromFormat('Y-m-d H:i:s',$request->expires_at);

                if($expiresAt->gt($startsAt) == false){
                    return response()->json(['status'=>false,'errors'=>['expires_at'=>'Expiry date must be greater than start date']]);
                }
            }

            $discountCode->code = $request->code;
            $discountCode->name = $request->name;
            $discountCode->description = $request->description;
            $discountCode->max_uses = $request->max_uses;
            $discountCode->max_uses_user = $request->max_uses_user;
            $discountCode->type = $request->type;
            $discountCode->discount_amount = $request->discount_amount;
            $discountCode->min_amount = $request->min_amount;
            $discountCode->status = $request->status;
            $discountCode->starts_at = $request->starts_at;
            $discountCode->expires_at = $request->expires_at;
            $discountCode->save();

            session()->flash('success','Discount Coupon updated successfully');
            return response()->json(['status'=>true,'message'=>'Discount Coupon updated successfully']);
        } else {
            return response()->json(['status'=>false,'errors'=>$validator->errors()]);
        }
    }

    public function destroy(Request $request, $id){
        $discountCode = DiscountCoupon::find($id);

        if($discountCode == null){
            session()->flash('error','Record not found');
            return response()->json(['status'=>true]);
        }

        $discountCode->delete();

        session()->flash('success','Discount Coupon deleted successfully');
        return response()->json(['status'=>true,'message'=>'Discount Coupon deleted successfully']);
    }
}
