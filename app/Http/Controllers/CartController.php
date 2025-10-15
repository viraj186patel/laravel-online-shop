<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Country;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Models\DiscountCoupon;
use App\Models\ShippingCharge;
use Carbon\Carbon;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function addToCart(Request $request){
        $product = Product::with('product_images')->find($request->id);

        if($product == null){
            return response()->json(['status'=>false,'message'=>'Product not found']);
        }

        if(Cart::count() > 0){
            // Product found in cart
            // Check if this product already in the cart

            $cartContent = Cart::content();
            $productAlreadyExist = false;

            foreach ($cartContent as $item) {
                if($item->id == $product->id){
                    $productAlreadyExist = true;
                }
            }

            if($productAlreadyExist == false){
                Cart::add($product->id, $product->title, 1, $product->price, ['productImage' => (!empty($product->product_images)) ? $product->product_images->first() : '']);

                $status = true;
                $message = '<strong>'.$product->title.'</strong> added in your cart successfully';
                session()->flash('success',$message);
            } else {
                $status = false;
                $message = $product->title.' already added in Cart';
            }
        } else {
            // Cart is empty
            Cart::add($product->id, $product->title, 1, $product->price, ['productImage' => (!empty($product->product_images)) ? $product->product_images->first() : '']);

            $status = true;
            $message = '<strong>'.$product->title.'</strong> added in your cart successfully';
            session()->flash('success',$message);
        }

        return response()->json(['status'=>$status,'message'=>$message]);
    }

    public function cart(){
        $cartContent = Cart::content();
        return view('front.cart',['cartContent' => $cartContent]);
    }

    public function updateCart(Request $request){
        $rowId = $request->rowId;
        $qty = $request->qty;

        $itemInfo = Cart::get($rowId);
        $product = Product::find($itemInfo->id);

        //check qty available in stock
        if($product->track_qty == 'Yes'){
            if($product->qty >= $qty){
                Cart::update($rowId, $qty);
                $message = 'Cart updated successfully';
                $status = true;
                session()->flash('success',$message);
            } else {
                $message = 'Request qty('.$qty.') not available in stock';
                $status = false;
                session()->flash('error',$message);
            }
        } else {
            Cart::update($rowId, $qty);
            $message = 'Cart updated successfully';
            $status = true;
            session()->flash('success',$message);
        }
        
        return response()->json(['status'=>$status,'message'=>$message]);
    }

    public function deleteItem(Request $request){
        $itemInfo = Cart::get($request->rowId);

        if($itemInfo == null){
            session()->flash('error','Item not found in cart');
            return response()->json(['status'=>false,'message'=>'Item not found in cart']);
        }

        Cart::remove($request->rowId);

        session()->flash('success','Item removed from cart successfully');
        return response()->json(['status'=>true,'message'=>'Item removed from cart successfully']);
    }

    public function checkout(){
        $discount = 0;

        // if cart is empty then redirect to cart page
        if(Cart::count() == 0){
            return redirect()->route('front.cart');
        }

        // if user is not logged in then redirect to login page
        if(Auth::check() == false){
            return redirect()->guest(route('account.login'));
        }

        $customerAddress = CustomerAddress::where('user_id',Auth::user()->id)->first();

        $countries = Country::orderBy('name','ASC')->get();

        //Apply Discount here
        $subTotal = Cart::subtotal(2,'.','');
        if(session()->has('code')){
            $code = session()->get('code');

            if($code->type == 'percent'){
                $discount = ($code->discount_amount/100)*$subTotal;
            } else {
                $discount = $code->discount_amount;
            }
        }

        // Calculate shipping here
        if($customerAddress != ''){
            $userCountry  = $customerAddress->country_id;
            $shippingInfo = ShippingCharge::where('country_id', $userCountry)->first();
            $totalQty = 0;
            $totalShippingCharge = 0;
            $grandTotal = 0;
    
            foreach (Cart::content() as $item) {
                $totalQty += $item->qty;
            }
            
            if($shippingInfo == null){
                $shippingInfo = ShippingCharge::where('country_id', 'rest_of_world')->first();
            }

            $totalShippingCharge = $totalQty*$shippingInfo->amount;
            $grandTotal = ($subTotal-$discount) + $totalShippingCharge;
        } else {
            $totalShippingCharge = 0;
            $grandTotal = ($subTotal-$discount);
        }

        return view('front.checkout',['countries'=>$countries,'customerAddress'=>$customerAddress,'totalShippingCharge'=>$totalShippingCharge,'grandTotal'=>$grandTotal,'discount'=>$discount]);
    }

    public function processCheckout(Request $request){
        // Validation
        $validator = Validator::make($request->all(),[
            'first_name' => 'required|min:5',
            'last_name' => 'required',
            'email' => 'required|email',
            'country' => 'required',
            'address' => 'required|min:30',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required',
            'mobile' => 'required',
        ]);

        if($validator->fails()){
            return response()->json(['message'=>'Please fix the errors','status'=>false,'errors'=>$validator->errors()]);
        }

        // Store data in Customer Addresses table
        $user = Auth::user();

        CustomerAddress::updateOrCreate(
            ['user_id' => $user->id],
            [
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'country_id' => $request->country,
                'address' => $request->address,
                'apartment' => $request->apartment,
                'city' => $request->city,
                'state' => $request->state,
                'zip' => $request->zip
            ]
        ); 

        // Store data in Orders table
        if($request->payment_method == 'cod'){

            //Calculate Shipping
            $promoCode = '';
            $shippingCharge = 0;
            $discount = 0;
            $subTotal = Cart::subtotal(2,'.','');

            //Apply Discount here
            if(session()->has('code')){
                $code = session()->get('code');

                if($code->type == 'percent'){
                    $discount = ($code->discount_amount/100)*$subTotal;
                } else {
                    $discount = $code->discount_amount;
                }

                $promoCode = $code->code;
            }

            $shippingInfo = ShippingCharge::where('country_id', $request->country)->first();

            $totalQty = 0;
            foreach (Cart::content() as $item) {
                $totalQty += $item->qty;
            }

            if($shippingInfo != null){
                $shippingCharge = $totalQty*$shippingInfo->amount;
                $grandTotal = ($subTotal-$discount) + $shippingCharge;
            } else {
                $shippingInfo = ShippingCharge::where('country_id', 'rest_of_world')->first();

                $shippingCharge = $totalQty*$shippingInfo->amount;
                $grandTotal = ($subTotal-$discount) + $shippingCharge;
            }

            $order = new Order;
            $order->subtotal = $subTotal;
            $order->shipping = $shippingCharge;
            $order->grand_total = $grandTotal;
            $order->discount = $discount;
            $order->coupon_code = $promoCode;
            $order->payment_status = 'not paid';
            $order->status = 'pending';

            $order->user_id = $user->id;
            $order->first_name = $request->first_name;
            $order->last_name = $request->last_name;
            $order->email = $request->email;
            $order->mobile = $request->mobile;
            $order->address = $request->address;
            $order->apartment = $request->apartment;
            $order->state = $request->state;
            $order->city = $request->city;
            $order->zip = $request->zip;
            $order->notes = $request->order_notes;
            $order->country_id = $request->country;

            $order->save();

            // Store data in Order Items table
            foreach (Cart::content() as  $item) {
                $orderItem = new OrderItem;
                $orderItem->product_id = $item->id;
                $orderItem->order_id = $order->id;
                $orderItem->name = $item->name;
                $orderItem->qty = $item->qty;
                $orderItem->price = $item->price;
                $orderItem->total = $item->price*$item->qty;
                $orderItem->save();

                //Update product stock
                $productData = Product::find($item->id);

                if($productData->track_qty == 'Yes'){
                    $currentQty = $productData->qty;
                    $updatedQty = $currentQty - $item->qty;
                    $productData->qty = $updatedQty;
                    $productData->save();
                }
            }

            //Send Order Email 
            orderEmail($order->id, 'customer');

            session()->flash('success','You have successfully placed your order.');
            Cart::destroy();
            session()->forget('code');
            return response()->json(['message'=>'Order saved successfully','orderId'=>$order->id,'status'=>true]);
        } else {

        }
    }

    public function thankyou($id){
        return view('front.thanks',['id'=>$id]);
    }

    public function getOrderSummary(Request $request){
        $subTotal = Cart::subtotal(2,'.','');

        //Apply Discount here
        $discount = 0;
        $discountString = '';

        if(session()->has('code')){
            $code = session()->get('code');

            if($code->type == 'percent'){
                $discount = ($code->discount_amount/100)*$subTotal;
            } else {
                $discount = $code->discount_amount;
            }

            $discountString = '<div class="mt-4" id="discount-response">
                <strong>'.session()->get('code')->code.'</strong>
                <a class="btn btn-danger btn-sm" id="remove-coupon"><i class="fa fa-times"></i></a>
            </div>';
        }

        if($request->country_id > 0){
            $shippingInfo = ShippingCharge::where('country_id', $request->country_id)->first();

            $totalQty = 0;
            foreach (Cart::content() as $item) {
                $totalQty += $item->qty;
            }

            if($shippingInfo != null){
                $shippingCharge = $totalQty*$shippingInfo->amount;
                $grandTotal = ($subTotal-$discount) + $shippingCharge;

                return response()->json(['status'=>true,'grandTotal'=>number_format($grandTotal,2),'shippingCharge'=>number_format($shippingCharge,2),'discount'=>number_format($discount,2),'discountString'=>$discountString]);
            } else {
                $shippingInfo = ShippingCharge::where('country_id', 'rest_of_world')->first();

                $shippingCharge = $totalQty*$shippingInfo->amount;
                $grandTotal = ($subTotal-$discount) + $shippingCharge;

                return response()->json(['status'=>true,'grandTotal'=>number_format($grandTotal,2),'shippingCharge'=>number_format($shippingCharge,2),'discount'=>number_format($discount,2),'discountString'=>$discountString]);
            }
        } else {
            return response()->json(['status'=>true,'grandTotal'=>number_format(($subTotal-$discount),2),'shippingCharge'=>number_format(0,2),'discount'=>number_format($discount,2),'discountString'=>$discountString]);
        }
    }

    public function applyDiscount(Request $request){
        $code = DiscountCoupon::where('code',$request->code)->first();

        if($code == null){
            return response()->json(['status'=>false,'message'=>'Invalid Discount Coupon']);
        }

        //Check if coupon's start date and expire date are valid or not
        $now = Carbon::now();

        if($code->starts_at != ''){
            $startsAt = Carbon::createFromFormat('Y-m-d H:i:s',$code->starts_at);

            if($now->lt($startsAt)){
                return response()->json(['status'=>false,'message'=>'Invalid Discount Coupon']);
            }
        }

        if($code->expires_at != ''){
            $expiresAt = Carbon::createFromFormat('Y-m-d H:i:s',$code->expires_at);

            if($now->gt($expiresAt)){
                return response()->json(['status'=>false,'message'=>'Invalid Discount Coupon']);
            }
        }
        
        //Max Uses Check
        if($code->max_uses > 0){
            $couponUsed = Order::where('coupon_code', $code->code)->count();
    
            if($couponUsed >= $code->max_uses){
                return response()->json(['status'=>false,'message'=>'This coupon has reached its maximum number of uses']);
            }
        }

        //Max Uses User Check
        if($code->max_uses_user > 0){
            $couponUsedByUser = Order::where(['coupon_code' => $code->code,'user_id' => Auth::user()->id])->count();

            if($couponUsedByUser >= $code->max_uses_user){
                return response()->json(['status'=>false,'message'=>'You have already used this coupon code']);
            }
        }
        
        //Min amount condition check
        $subTotal = Cart::subtotal(2,'.','');
        if($code->min_amount > 0){
            if($subTotal < $code->min_amount){
                return response()->json(['status'=>false,'message'=>'Your min amount must be $'.$code->min_amount]);
            }
        }

        session()->put('code',$code);
        return $this->getOrderSummary($request);
    }

    public function removeCoupon(Request $request){
        session()->forget('code');
        return $this->getOrderSummary($request);
    }
}
