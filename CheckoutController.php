<?
/*
    Version History
    ====================================
    Version.    Modi.Date       Modi.By    Description
    1.1         2023-06-06      Nimesh     Task#9261 : We need to make changes on front end to support different product types. In first step, we plan to support two new product types other than current "Physical Product"(Code = "P") and these are below:
													   "S" - Service,"D" - Seller Delivered
	1.0         2023-06-06      Admin      Program registered under version history.
  ====================================
*/
?>
<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\Entityaddress;
use App\Models\EntityProfile;
use App\Models\GPSlocqueue;
use App\Models\PaymentMethods;
use App\Models\Route;
use App\Models\System;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use PayPal\Api\Details;

/** All Paypal Details class **/

use Redirect;
use Session;
use URL;

class CheckoutController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        /** PayPal api context **/
        // $paypal_conf = \Config::get('paypal');
        // $this->_api_context = new ApiContext(new OAuthTokenCredential(
        // $paypal_conf['client_id'],
        // $paypal_conf['secret'])
        // );
        // $this->_api_context->setConfig($paypal_conf['settings']);

    }

    public function checkoutSetpOne(Request $request)
    {
        if (session()->has('userDetail')) {
            $uid = session()->get('userDetail')['id'];
        } else {
            //$uid = 0;
            return redirect('/');
        }
        //$uid = 101;
        $primary_address_id = 0;
        $billing_address_id = 0;
        $entity_id = 0;

        $url = url()->previous();
        $url = substr($url, strrpos($url, '/') + 1);

        $param = array();
        $param['uid'] = $uid;
        $param['url'] = $url;
        $responses = callApi("post", $param, "checkoutstepone");
        $dataAry = $responses['data'];
        $redirect = $dataAry['redirect'];
        if ($redirect == "checkoutstepfour") {
            return redirect()->route('checkoutSetpFour');
        }else
		{
			 
			$service_prod_flag = getproducttypeflag($uid, true, array());
			//PRX($service_prod_flag);
			if ($service_prod_flag == 1){
				return redirect()->route('checkoutSetpThree');
			}
			
			
		}
        $addressData = $dataAry['addressData'];
        $primary_address_id = $dataAry['primary_address_id'];
        $billing_address_id = $dataAry['billing_address_id'];

        $is_display = 0;
        return view('web.checkout-step-one', compact('addressData', 'primary_address_id', 'billing_address_id', 'is_display','service_prod_flag'));
	
    }

    public function checkout_addaddress(Request $request)
    {
        if (session()->has('userDetail')) {
            $uid = session()->get('userDetail')['id'];
        } else {
            //$uid = 0;
            return redirect('/');
        }

        $e_data = Entity::select('entity_id', 'primary_address_id', 'billingaddressid')->where('user_id', $uid)->first();
        $entity_id = $primary_address_id = $billingaddressid = 0;
        if (!is_null($e_data)) {
            $entity_id = $e_data->entity_id;
            $primary_address_id = $e_data->primary_address_id;
            $billingaddressid = $e_data->billingaddressid;
            if (!is_numeric($primary_address_id)) {
                $primary_address_id = 0;
            }
            if (!is_numeric($billingaddressid)) {
                $billingaddressid = 0;
            }
        }

        $this->validate($request, [
            'name' => 'required',
            'address1' => 'required',
            'city' => 'required',
            'state' => 'required',
            'postalcode' => 'required',
        ]);

        $input = $request->all();
        //echo '<pre>';print_r($input);echo '</pre>';
        //prx('adad');

        $name = $request->input('name');
        $address1 = $request->input('address1');
        $address2 = $request->input('address2');
        $city = $request->input('city');
        $state = $request->input('state');
        $postalcode = $request->input('postalcode');
        $primaryphone = $request->input('primaryphone');

        $entityaddress = new Entityaddress;
        $entityaddress->entity_id = $entity_id;
        $entityaddress->name = $name;
        $entityaddress->address1 = $address1;
        $entityaddress->address2 = $address2;
        $entityaddress->city = $city;
        $entityaddress->state = $state;
        $entityaddress->postalcode = $postalcode;
        $entityaddress->primaryphone = $primaryphone;
        $entityaddress->country = "US";
        $entityaddress->save();
        $entity_address_id = $entityaddress->id;

        if ($primary_address_id == 0) {
            Entity::where('entity_id', $entity_id)->update(array('primary_address_id' => $entity_address_id));
        }
        if ($billingaddressid == 0) {
            Entity::where('entity_id', $entity_id)->update(array('billingaddressid' => $entity_address_id));
            $billingaddressid = $entity_address_id;
            //$cartarr = array("shipping_address_id"=>$entity_address_id,"billing_address_id"=>$entity_address_id);
        }

        $cartarr = array("shipping_address_id" => $entity_address_id, "billing_address_id" => $billingaddressid);
        $test = updateCartinfo($cartarr);

        $curDate = date('Y-m-d');
        $gpslocqueue = new GPSlocqueue;
        $gpslocqueue->entity_id = $entity_id;
        $gpslocqueue->entity_address_id = $entity_address_id;
        $gpslocqueue->reccreatedt = $curDate;
        $gpslocqueue->lastactivitydt = $curDate;
        $gpslocqueue->status = 'N';
        $gpslocqueue->save();

        return redirect()->route('checkoutSetpTwo');
    }
    public function checkout_editaddress(Request $request, $entity_address_id)
    {

        $this->validate($request, [
            'name' => 'required',
            'address1' => 'required',
            'city' => 'required',
            'state' => 'required',
            'postalcode' => 'required',
        ]);

        $input = $request->all();
        // echo '<pre>';print_r($input);echo '</pre>';
        $name = $request->input('name');
        $address1 = $request->input('address1');
        $address2 = $request->input('address2');
        $city = $request->input('city');
        $state = $request->input('state');
        $postalcode = $request->input('postalcode');
        $primaryphone = $request->input('primaryphone');

        Entityaddress::where('entity_address_id', $entity_address_id)->update(['name' => $name, 'address1' => $address1, 'address2' => $address2, 'city' => $city, 'state' => $state, 'postalcode' => $postalcode, 'primaryphone' => $primaryphone]);

        $entity_id = Entityaddress::where('entity_address_id', $entity_address_id)->value('entity_id');

        $curDate = date('Y-m-d');
        $gpslocqueue = new GPSlocqueue;
        $gpslocqueue->entity_id = $entity_id;
        $gpslocqueue->entity_address_id = $entity_address_id;
        $gpslocqueue->reccreatedt = $curDate;
        $gpslocqueue->lastactivitydt = $curDate;
        $gpslocqueue->status = 'N';
        $gpslocqueue->save();

        $e_data = Entity::select('entity_id', 'billingaddressid')->where('entity_id', $entity_id)->first();

        $billingaddressid = 0;
        if (!is_null($e_data)) {
            $billingaddressid = $e_data->billingaddressid;
            if (!is_numeric($billingaddressid)) {
                $billingaddressid = 0;
            }
        }

        if ($billingaddressid == 0) {
            Entity::where('entity_id', $entity_id)->update(array('billingaddressid' => $entity_address_id));
            $billingaddressid = $entity_address_id;
            //$cartarr = array("shipping_address_id"=>$entity_address_id,"billing_address_id"=>$entity_address_id);
        }

        $cartarr = array("shipping_address_id" => $entity_address_id, "billing_address_id" => $billingaddressid);
        $test = updateCartinfo($cartarr);

        return redirect()->route('checkoutSetpTwo');
    }

    public function checkout_changeaddress($entity_address_id)
    {

        $entity_id = Entityaddress::where('entity_address_id', $entity_address_id)->value('entity_id');

        Entity::where('entity_id', $entity_id)->update(array('billingaddressid' => $entity_address_id));

        $cartarr = array("billing_address_id" => $entity_address_id);
        updateCartinfo($cartarr);

        return redirect()->route('checkoutSetpFour');
    }
    public function checkout_deliveryaddress(Request $request, $entity_address_id)
    {

        if (session()->has('userDetail')) {
            $uid = session()->get('userDetail')['id'];
        } else {
            return redirect('/');
        }

        $param = array();
        $param['uid'] = $uid;
        $param['entity_address_id'] = $entity_address_id;
        $responses = callApi("post", $param, "checkoutstepone_submit");

        return redirect()->route('checkoutSetpTwo');
    }
    public function checkoutSetpTwo(Request $request)
    {
        $shippinginfo = array();
        $localdelivery = 0;
        $is_display = 0;

        if (session()->has('userDetail')) {
            $uid = session()->get('userDetail')['id'];
            $param = array();
            $param['uid'] = $uid;
            $responses = callApi("post", $param, "checkoutsteptwo");
            $dataAry = $responses['data'];
            $shippinginfo = $dataAry['shippinginfo'];
            $localdelivery = $dataAry['localdelivery'];
        } else {
            return redirect('/');
        }
        #prX($shippinginfo);
		$service_prod_flag = getproducttypeflag($uid, true, array());
		//PRX($service_prod_flag);
		if ($service_prod_flag == 2){
			return redirect()->route('checkoutSetpThree');
		}
		
        return view('web.checkout-step-two', compact('shippinginfo', 'is_display', 'localdelivery'));
    }
    public function checkoutSetpTwoSubmit(Request $request)
    {
        $input = $request->all();
        $shippinginfo = $request->input('shippingradio');

        if (session()->has('userDetail')) {
            $uid = session()->get('userDetail')['id'];
        } else {
            return redirect('/');
        }
        $param = array();
        $param['uid'] = $uid;
        $param['shippinginfo'] = $shippinginfo;
        $responses = callApi("post", $param, "checkoutsteptwo_submit");

        #$cartarr = array("shippinginfo"=>$shippinginfo);
        #updateCartinfo($cartarr);
        return redirect()->route('checkoutSetpThree');
    }
    public function checkoutSetpThree(Request $request, $cc_flag = "")
    {
        $is_display = 0;
        if (session()->has('userDetail')) {
            $uid = session()->get('userDetail')['id'];
            $email = session()->get('userDetail')['email'];
            $entity_id = Entity::where('user_id', $uid)->value('entity_id');
			
            $editpaymentid = 0;
            $paymentinfo = DB::table('cart')->where('user_id', $uid)->value('paymentinfo');
            $paymentinfoarr = explode('~', $paymentinfo);
            $editpaymentid = $paymentinfoarr[0];

            $data = System::select('strvar')
                ->where('system_id', '=', 'CPROC')
                ->first();
            $strvar_CPROC = $authorizenetname = $authorizenetkey = $strValidationMode = "";
            if (!is_null($data)) {
                $strvar_CPROC = $data->strvar;
            }
            $arr_strvar_CPROC = explode("~", $strvar_CPROC);
            $authorizenetname = $arr_strvar_CPROC[0];
            $authorizenetkey = $arr_strvar_CPROC[1];
            $strValidationMode = $arr_strvar_CPROC[2];
            //prx(session()->get('userDetail')['accessToken']);
            $param = array();
            $responses = callApiwithToken("post", $param, "getsavedcards", session()->get('userDetail')['accessToken']);

            $ep_data_ary = array();
            $strValidationMode = $token = "";
            if ($responses['success'] == 1) {
                $strValidationMode = $responses['data']['strValidationMode'];
                $token = $responses['data']['token'];
                $ep_data = $responses['data']['ep_data_ary'];
            }
            
            $data = PaymentMethods::select('payment_name', 'payment_gateway', 'rank')
                ->where('status', '1')
                ->orderBy('rank')
                ->get();
            $pm_data = $data;
            //prx(data);
            $paypalprofile = EntityProfile::select('entity_profile_id', 'paymentprofileid')
                ->where('entity_id', '=', $entity_id)
                ->where('type', '=', 'P')
                ->first();
            $paypal_entity_profileid = 0;
            $paypal_paymentprofileid = 0;
            if (!is_null($paypalprofile)) {
                $paypal_entity_profileid = $paypalprofile->entity_profile_id;
                $paypal_paymentprofileid = $paypalprofile->paymentprofileid;
            }
				//$service_prod_flag = 0;
			
			// $param = array();
			 // $param['uid'] = $uid;
			 // $responses = callApi("post", $param, "getCartTotalItem");
			 // $result = $responses['data'];
			
			 
				// foreach ($result as  $key => $value) {
					// $prod_type = $value['prod_type'];
					// if ($prod_type != 'D' && $prod_type != 'S' && $prod_type != 'C' && $prod_type != 'U') {
						// $service_prod_flag = 0;
						// break;
					// }else{
						// $service_prod_flag = 1;
					// }
				// }
				$service_prod_flag = getproducttypeflag($uid, true, array());
            #prx($ep_data);
            return view('web.checkout-step-three', compact('strvar_CPROC', 'authorizenetname', 'authorizenetkey', 'strValidationMode', 'token', 'ep_data', 'is_display', 'editpaymentid', 'pm_data', 'cc_flag', 'paypal_paymentprofileid', 'paypal_entity_profileid','service_prod_flag'));
            // return view('web.save-card');
        } else {
            return redirect('/');
        }
    }
    public function checkoutSetpThreeSubmit(Request $request)
    {
        $input = $request->all();
        $paymentinfo = $request->input('paymentradio');

        if (session()->has('userDetail')) {
            $uid = session()->get('userDetail')['id'];
        } else {
            return redirect('/');
        }
        $param = array();
        $param['uid'] = $uid;
        $param['paymentinfo'] = $paymentinfo;
        $responses = callApi("post", $param, "checkoutstepthree_submit");

        #$cartarr = array("paymentinfo"=>$paymentinfo);
        #updateCartinfo($cartarr);
        return redirect()->route('checkoutSetpFour');
    }
    public function checkoutSetpFour(Request $request)
    {
        if (session()->has('userDetail')) {
            $uid = session()->get('userDetail')['id'];
        } else {
            return redirect('/');
        }

        //$uid = session()->get('userDetail')['id'];
        $param = array();
        $param['uid'] = $uid;
        $responses = callApi("post", $param, "checkoutstepfour");
        //prx($responses->json());
        $result = $shippingdata = $shippingaddressData = $paymentdata = $billingaddressData = $tax = $prod_variant_id = $addressData = $delivefess = $helpcontactList = $subscriptioninfo = array();
        if ($responses) {
            if ($responses['success'] == 1) {
                $dataAry = $responses['data'];
                $result = $dataAry['result'];
                $shippingdata = $dataAry['shippingdata'];
                $shippingaddressData = $dataAry['shippingaddressData'];
                $paymentdata = $dataAry['paymentdata'];
                $billingaddressData = $dataAry['billingaddressData'];
                $tax = $dataAry['tax'];
                $tax_rates_id = $dataAry['tax_rates_id'];
                $prod_variant_id = $dataAry['prod_variant_id'];
                $addressData = $dataAry['addressData'];
                $delivefess = $dataAry['delivefess'];
                $helpcontactList = $dataAry['helpcontactList'];
				$service_prod_flag = $dataAry['service_prod_flag'];
				$subscriptioninfo = $dataAry['subscriptioninfo'];
            } else {
                if ($responses['status'] == 299) {
                    return redirect()->route('cart');
                } else {
                    return redirect()->route('checkoutSetpTwo');
                }
            }
        }

        //prx($subscriptioninfo);
        $is_display = 0;

        $payment_method = 'Authorize.net';
        $cc_flag = 'cc';
        if (isset($paymentdata[3]) && $paymentdata[3] == 'PayPal') {
            $payment_method = 'PayPal';
            $cc_flag = 'p';
        }
         
        return view('web.checkout-step-four', compact('result', 'is_display', 'shippingdata', 'shippingaddressData', 'paymentdata', 'billingaddressData', 'tax', 'prod_variant_id', 'addressData', 'delivefess', 'helpcontactList', 'tax_rates_id', 'payment_method', 'cc_flag','service_prod_flag','subscriptioninfo'));
    }
    public function placeorder(Request $request)
    {
        //All input filed
        $input = $request->all();
        $total_prod_amt = $input['order_subtotal_amount'];
        $deliveryfees = $input['deliveryfees'];
        $tax_amt = $input['tax_amount'];
        $coupon_discount_amount = $input['coupon_discount_amount'];
        $total_amt = $input['order_total_amount'];
        $tax_rates_id = $input['tax_rates_id'];
        $address_id = $input['address_id'];
        $billing_address_id = $input['billing_address_id'];
        $entity_profile_id = $request->input('entity_profile_id');
        $prod_variant_ary = $request->input('prod_variant_id');
        $quantityAry = $input['quantity'];
        $shipping_method = $input['shipping_method'];
        $delivery_est = $input['delivery_est'];
		$service_prod_flag = $input['service_prod_flag'];
		$subscriptioninfo = $input['subscriptioninfo'];
        // echo '<pre>';
        // print_r($input);
        // echo '</pre>';
        // prx('adad');

        if (session()->has('userDetail')) {
            $uid = session()->get('userDetail')['id'];

            $param = array();
            $param['uid'] = $uid;
            $param['total_prod_amt'] = $total_prod_amt;
            $param['deliveryfees'] = $deliveryfees;
            $param['tax_amt'] = $tax_amt;
            $param['coupon_discount_amount'] = $coupon_discount_amount;
            $param['total_amt'] = $total_amt;
            $param['tax_rates_id'] = $tax_rates_id;
            $param['address_id'] = $address_id;
            $param['billing_address_id'] = $billing_address_id;
            $param['entity_profile_id'] = $entity_profile_id;
            $param['prod_variant_ary'] = $prod_variant_ary;
            $param['quantityAry'] = $quantityAry;
            $param['shipping_method'] = $shipping_method;
            $param['delivery_est'] = $delivery_est;
			$param['service_prod_flag'] = $service_prod_flag;
			$param['subscriptioninfo']  = $subscriptioninfo;
           
//prx($$param);
            $responses = callApi("post", $param, "placeorder");
            //prx($responses);
            $dataAry = $responses['data'];
            $status = $dataAry['status'];
            $msg = $dataAry['msg'];
            $type = $dataAry['type'];
            if ($responses['success'] == 1) {
                $ORDER_ID = $dataAry['ORDER_ID'];
                $ORDER_NO = $dataAry['ORDER_NO'];
                $request->session()->put('ORDER_ID', $ORDER_ID);
                $request->session()->put('ORDER_NO', $ORDER_NO);
            } else {
            }
        } else {
            $status = 'error';
            $msg = "Please login";
            $type = '';
        }
        return response()->json(['status' => $status, 'msg' => $msg, 'type' => $type]);
    }
    public function orderconfirm(Request $request)
    {
        if (session()->has('ORDER_NO')) {
            $order_no = session()->get('ORDER_NO');
        } else {
            $order_no = 0;
        }
        $SERVER_NAME = $_SERVER['SERVER_NAME'];
        $int_TIMEO = System::getSystemval('TIMEO', 'intvar');
        if ($int_TIMEO == '') {
            $int_TIMEO = 0;
        }

        $date = date("m/d/Y H:i:s", strtotime("+$int_TIMEO hours"));
        return view('web.orderconfirm', compact('date', 'order_no', 'SERVER_NAME'));
    }

    public function checkout(Request $request)
    {
        if (session()->has('userDetail')) {
            $uid = session()->get('userDetail')['id'];
        } else {
            //$uid = 0;
            return redirect('/');
        }
        //$uid = 101;
        $primary_address_id = 0;
        $billing_address_id = 0;
        $entity_id = 0;
        $entitydata = Entity::select('primary_address_id', 'entity_id', 'billingaddressid')
            ->where('user_id', '=', $uid)
            ->first();

        if (isset($entitydata)) {
            $primary_address_id = $entitydata->primary_address_id;
            $entity_id = $entitydata->entity_id;
            $billing_address_id = $entitydata->billingaddressid;
        }

        $addressData = Entityaddress::select('entity_address_id', 'name', DB::raw('CONCAT_WS(" ", NULLIF(address1, ""), NULLIF(address2, "")) address'), 'city', 'state', 'postalcode', 'primaryphone')
            ->where('entity_id', '=', $entity_id)
            ->orderby('entity_address_id')
            ->get();
        //PRX($addressData);
        $is_display = 0;
        return view('web.checkout', compact('addressData', 'primary_address_id', 'billing_address_id', 'is_display'));
    }
}
