<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Deposit;
use App\Models\Transfer;
use App\Models\Transaction;
use App\Models\Beneficiary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Browser;
use Session;
use Image;
use Redirect;

class UserController extends Controller
{
    //User Dashboard
    public function dashboard() {
        $data['user'] = Auth::user();
        $today = date('Y-m-d');
        $from = date("Y-m-d", strtotime('-7 days', strtotime($today)));
        $data['trx_in'] = Transfer::whererecipient_acc_number(Auth::user()->int_acc_number)->get();
        $data['trx_out'] = Transfer::whereuser_id(Auth::user()->id)->get();
        $data['deposits'] = Deposit::whereuser_id(Auth::user()->id)->get();
        $data['trx'] = Transaction::whereuser_id(Auth::user()->id)->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Logged in to Dashboard',
            'data' => $data
        ]);
    }

    public function getUserss() {
        $user = User::whereid(1)->first();
        $user->pin = 1234;
        $user->save();
        return response()->json([
            'status' => 'Success',
            'message' => 'Retrieved',
            'data' => $user,
            'auth' => Auth::guard('api')->user(),
            'auths' => Auth::user()
        ]);
    }
    //Deposit
    public function initiateDeposit(Request $request) {

    }
    //Get User
    public function getUser() {
        return response()->json([
            'status' => 'Success',
            'message' => Auth::user(),
        ]);
    }
    //Get all Banks
    public function getAllBanks() {
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/bank",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer sk_live_ee2a89605a7d763ba8a0200695fe501beb3019ab",
            "Cache-Control: no-cache",
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        $res = json_decode($response, true);

        
        return response()->json([
            'status' => 'Success',
            'message' => 'Retrieved Successfully',
            'data' => $res
        ]);
    }

    //Check Account Name 
    public function getAccName(Request $request) {
        $curl = curl_init();
  
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/bank/resolve?account_number=".$request->acc_num."&bank_code=".$request->bank,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer sk_live_ee2a89605a7d763ba8a0200695fe501beb3019ab",
            "Cache-Control: no-cache",
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        $res = json_decode($response, true);
        if($res['status'] == 'Success') {
            return response()->json([
                'status' => 'Success',
                'message' => 'Resolved Successfully',
                'data' => $res['data']
            ]);
        }
        else {
            return response()->json([
                'status' => 'Error',
                'message' => $res['message']
                
            ]);
        }
        
    }

    //Check Account Name 
    public function getIntAccName(Request $request) {
        $check = User::whereint_acc_number($request->acc_num)->count();
        if($check<1) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Invalid Account Number'
                
            ]); 
        }
        else {
            $user = User::whereint_acc_number($request->acc_num)->first();
            return response()->json([
                'status' => 'Success',
                'message' => 'Account Name Resolved',
                'data' => $user->first_name .' '.$user->last_name
                
            ]); 
        }
    }
    //Transfer

    //Internal Transfer
    public function submitIntTransfer(Request $request) {
        $user = User::whereid(Auth::user()->id)->first();

        if($user->balance < $request->amount) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Insufficient Balance'
            ]);
        }
        else {
            
                $trns = new Transfer();
                $trns->user_id = $user->id;
                $trns->recipient_bank = 'Internal Bank';
                $trns->recipient_acc_name = $request->acc_name;
                $trns->recipient_acc_number = $request->acc_number;
                $trns->amount = $request->amount;
                $trns->type = 'Internal';
                $trns->status = 0;
                $trns->trx_id = Str::random(8);
                $trns->save();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Proceed to Validate PIN',
                    'data' => $trns->trx_id
                ]);
            
        }
    }
        //Complete Internal Transfer
    public function completeIntTransfer(Request $request) {
        $check = Transfer::wheretrx_id($request->trx_id)->count();
        if($check<1) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Invalid Transaction ID'
            ]);
        }
        else{
            $trx = Transfer::wheretrx_id($request->trx_id)->first();
            $user = User::whereid(Auth::user()->id)->first();
            if($user->pin != $request->pin) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Invalid PIN'
                ]);
            }
            else {
                if($user->transfer_type == 'Auto') {
                //Run the transfer
                $reci = User::whereint_acc_number($trx->recipient_acc_number)->first();
                
                    //Debit sender balance
                        $user->balance = $user->balance-$trx->amount;
                        $user->save();
                    //Credit recipient balance
                    $reci->balance = $reci->balance + $trx->amount;
                    
                        $reci->save();
                            
                        //Update Transfer status
                        $trx->status = 1;
                        $trx->save();
                        
                        //Record Transaction for sender
                        $trv = new Transaction();
                        $trv->user_id = $user->id;
                        $trv->amount = $trx->amount;
                        $trv->type = 'Internal Transfer';
                        $trv->nature = 'Debit';
                        $trv->description = 'Transferred '.$trx->amount.'NGN to '.$trx->recipient_acc_name.' via Internal Transfer';
                        $trv->trx_id = $request->trx_id;
                        $trv->status = '1';
                        $trv->save();

                        //Record Transaction for recipient
                        $trv = new Transaction();
                        $trv->user_id = $reci->id;
                        $trv->amount = $trx->amount;
                        $trv->type = 'Internal Transfer';
                        $trv->nature = 'Credit';
                        $trv->description = $trx->amount.'NGN was transferred to you from '.$user->first_name.' via Internal Transfer';
                        $trv->trx_id = $request->trx_id;
                        $trv->status = '1';
                        $trv->save();
                        return response()->json([
                            'status' => 'Success',
                            'message' => 'Transfer Complete',
                            'data' => $trx
                        ]);
                    }
                    else {

                    }
                }
                
            }
        
    }
    
    //External Transfer
    public function submitExTransfer(Request $request) {
        $user = User::whereid(Auth::user()->id)->first();

        if($user->balance < $request->amount) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Insufficient Balance'
            ]);
        }
        else {
            
                
                $trns = new Transfer();
                $trns->user_id = $user->id;
                $trns->recipient_bank = $request->bank;
                $trns->recipient_acc_name = $request->acc_name;
                $trns->recipient_acc_number = $request->acc_number;
                $trns->amount = $request->amount;
                $trns->type = 'External';
                $trns->status = 0;
                $trns->trx_id = Str::random(8);
                $trns->save();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Proceed to Validate PIN',
                    'data' => $trns->trx_id
                ]);
            
        }
    }
        //Complete External Transfer
    public function completeExTransfer(Request $request) {
        $check = Transfer::wheretrx_id($request->trx_id)->count();
        if($check<1) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Invalid Transaction ID'
            ]);
        }
        else{
            $trx = Transfer::wheretrx_id($request->trx_id)->first();
            $user = User::whereid(Auth::user()->id)->first();
            if($user->pin != $request->pin) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Invalid PIN'
                ]);
            }
            else {
                
                if($user->transfer_type == 'Auto') {
                //Run the transfer
                


                    //Debit user balance
                    $user->balance = $user->balance-$trx->amount;
                    $user->save();
                    
                    //Update Transfer status
                    $trx->status = 1;
                    $trx->save();
                    
                    //Record Transaction
                    $trv = new Transaction();
                    $trv->user_id = $user->id;
                    $trv->amount = $trx->amount;
                    $trv->type = 'External Transfer';
                    $trv->description = 'Transferred '.$trx->amount.'NGN to '.$trx->recipient_acc_name;
                    $trv->nature = 'Debit';
                    $trv->trx_id = $request->trx_id;
                    $trv->status = '1';
                    $trv->save();
                    return response()->json([
                        'status' => 'Success',
                        'message' => 'Transfer Complete',
                        'data' => $trx
                    ]);
                }
                else {

                }
            }
        }
    }
    
    /*Add Beneficiary
    public function addBeneficiary(Request $request) {
        
        $check = Beneficiary::wherename($request->name)->orWhere('acc_num', '==', $request->acc_num)->count();
        if($check>0) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Beneficiary already exist'
            ]);
        }
        else {
            $bene = new Beneficiary();
            $bene->user_id = $user->id;
            $bene->name = $request->name;
            $bene->bank = $request->bank;
            $bene->acc_name = $request->acc_name;
            $been->acc_num = $request->acc_num;
            $bene->status = 1;
            $bene->save();

            return response()->json([
                'status' => 'Success',
                'message' => 'Beneficiary added successfully',
                'data' => $bene
            ]);
        }
    }

    //Get Beneficiaries
    public function getBeneficiaries() {
        $user = User::whereid(Auth::user()->id)->first();
        $bene = Beneficiary::whereuser_id($user->id)->get();
        return response()->json([
            'status' => 'Success',
            'message' => 'Beneficiaries retrieved successfully',
            'data' => $bene
        ]);
    }
    */
    //reset Password
    public function sendResetPassword(Request $request){
        // If email does not exist
        $chk = User::whereemail($request->email)->count();
        if($chk<1) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Email does not exist.'
            ]);
        } 
        else {
            // If email exists
            $user = User::whereemail($request->email)->first();
            
            $token = Str::random(18);
            $user->rememberToken = $token;
            $user->save();
            $subject = 'Reset Password';
            $msg = 'Hello '.$user->first_name .', your reset token is <b>'.$token.'</b><br>';
            //$msg = json_encode($msg);
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.sendinblue.com/v3/smtp/email',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "sender":{  
                        "name":"Banking",
                        "email":"info@gmail.com"
                    },
                    "to":[  
                        {  
                            "email":"'.$user->email.'",
                            "name":"'.$user->first_name.'"
                        }
                    ],
                    "subject":"'.$subject.'",
                    "htmlContent":"<html><head></head><body><p>'.$msg.'</p></body></html>"
                    }',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json',
                'api-key: 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'
            ),
            ));
            
            $response = curl_exec($curl);
            $res = json_decode($response, true);
            curl_close($curl);
            return response()->json([
                'status' => 'Success',
                'message' => 'Token Created successfully.',
                'token'   =>  $token,
                'mg' => $response
            ]);
        }
    }

    public function verifyToken(Request $request){
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validator->errors()->first()
            ]);
        }
        $user = User::whereemail($request->email)->first();
        if (!$user) {
            return response()->json([
                "status" => 'Error',
                "message"=> "User account does not exist"
            ]);
        }
        $usert = User::whereemail($request->email)->whererememberToken($request->token)->count();
        if ($usert<1) {
            return response()->json([
                "status" => 'Error',
                "message"=> "Invalid Token"
            ]);
        }
        else {
            return response()->json([
                "status" => 'Success',
                "message"=> "Token Verified"
            ]);
        }
    }
    public function resetPassword(Request $request){
        $validator = Validator::make($request->all(), [
            'password' => ['min:6|required_with:password_confirmation|same:password_confirmation', 'string'],
            'password_confirmation' => 'min:6',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validator->errors()->first()
            ]);
        }
        $user = User::whereemail($request->email)->first();
        if (!$user) {
            return response()->json([
                "status" => 'Error',
                "message"=> "User account does not exist"
            ]);
        }
        $usert = User::whereemail($request->email)->first();
        if (!$usert) {
            return response()->json([
                "status" => 'Error',
                "message"=> "Invalid Email"
            ]);
        }
        if(Hash::check($request->password,$user->password)){
            return response()->json([
                "status" => 'Error',
                "message"=> "Password is the same as old password!"
            ]);
        }
        else {
            $user->password = Hash::make($request->password);
            $user->save();
            return response()->json([
                "status" => 'Success',
                "message"=> "Successful, Password reset completed."
            ], 200);
        }
        
    }

    

    
}

