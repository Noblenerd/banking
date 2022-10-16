<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Browser;
use Image;
use Redirect;

class RegisterController extends Controller
{
    //use RegistersUsers;

  public function registersubmit(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['min:6|required', 'string'],
            'phone' => ['required', 'string', 'max:255'],
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validate->errors()
            ]);
            
        }
        else {
            
            $checkem = User::whereemail($request->email)->count();
            $checkph = User::wherephone($request->phone)->count();
            if($checkem >0) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'The email has been used by another user. Try another'
                ]);
                
            }
            else if($checkph >0) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'The phone number has been used by another user. Try another'
                ]);
                
            }
            else {
                
                $user = new User();
                $user->email = $request->email;
                $user->phone = $request->phone;
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->dob = $request->dob;
                $user->gender = $request->gender;
                $user->transfer_type = 'Auto';
                $user->int_acc_number = mt_rand(1000000000, 9999999999);
                $user->password = Hash::make($request->password);
                $fer = mt_rand(100000,999999);
                $user->email_verify_code = $fer;
                $user->email_verify_status = 0;
                $user->balance = 0;
                $ty = 10;
                $user->expires_at = Carbon::now()->addMinutes($ty);
                $user->save();
                
                
                $from = 'Banking';
                $subject = 'Email Verification';
                $msg = 'Hello '.$user->name .', your OTP is <h3><b>'.$fer.'.</b></h3><br><br> OTP expires in 10 minutes!';
                
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
                                    "email":"'.$request->email.'",
                                    "name":"'.$request->phone.'"
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
                    
                    curl_close($curl);
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Registration was successful. OTP was sent to your mail',
                    'data' => $user
                ]);
            }
        }
    }

    public function resendotp(Request $request) {
        $user = User::whereemail($request->email)->first();
        $fer = mt_rand(100000,999999);
        $user->email_verify_code = $fer;
        $user->expires_at = Carbon::now()->addMinutes('10');
        $user->save();
        $from = 'Banking';
                $subject = 'Email Verification';
                $msg = 'Hello '.$user->name .', your OTP is <h3><b>'.$fer.'.</b></h3><br><br> OTP expires in 10 minutes!';
                
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
                                    "name":"'.$user->phone.'"
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
                    
                    curl_close($curl);
        return response()->json([
            'status' => 'Success',
            'message' => 'OTP has been resent to your mail'
        ]);
    }

    public function verifyotp(Request $request) {
        $user = User::whereemail($request->email)->first();
        
        if($user->email_verify_code == $request->otp) {
            
            if(Carbon::now() < $user->expires_at) {
                $user->email_verify_status = 1;
                $user->save();
                
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Email Verified'
                ]);
                
            }
            else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Code is no longer valid'
                ]);
            }
        }
        else {
            return response()->json([
                'status' => 'Error',
                'message' => 'Invalid OTP code'
            ]);
        }
    }

    //get All Users
    public function getUsers() {
        $user = User::all();

        return response()->json([
            'status' => 'Success',
            'message' => 'Retrieved',
            'data' => $user
        ]);
    }

    
}
