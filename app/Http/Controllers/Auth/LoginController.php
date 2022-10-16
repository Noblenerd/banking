<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Browser;
use Session;
use Image;
use Redirect;

class LoginController extends Controller
{
    public function loginsubmit(Request $request) {
        $validate = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validate->errors()
            ]);
        }
        else {
            if(Auth::attempt([
                'email' => $request->email,
                'password' => $request->password
            ])) {
                $user = User::whereid(Auth::user()->id)->first();       
                if($user->email_verify_status == 0) {
                            Auth::guard('user')->logout();
                            
                            return response()->json([
                                'status' => 'Error',
                                'message' => 'Email not verified'
                            ]);
                        }
                        else {
                    
                        
                        $data = array(
                            'user' => $user
                        );
                        return response()->json([
                            'status' => 'Success',
                            'message' => 'Login Successful',
                            'data' => $data
                            
                        ]);
                    }
                
                    }
                else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Invalid Credentials'
                    ]);
                }
        }
    }

    public function adminloginsubmit(Request $request) {
        $validate = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validate->errors()
            ]);
        }
        else {
            if(Auth::guard('admin')->attempt([
                'username' => $request->username,
                'password' => $request->password
            ])) {
                        
                        
                        $data = array(
                            'admin' => Auth::guard('admin')->user()
                        );
                        return response()->json([
                            'status' => 'Success',
                            'message' => 'Login Successful',
                            'data' => $data
                            
                        ]);
                    
                
                    }
                else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Invalid Credentials'
                    ]);
                }
        }
    }

    public function superadminloginsubmit(Request $request) {
        $validate = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validate->errors()
            ]);
        }
        else {
            if(Auth::guard('superadmin')->attempt([
                'username' => $request->username,
                'password' => $request->password
            ])) {
                        
                        
                        $data = array(
                            'admin' => Auth::guard('superadmin')->user()
                        );
                        return response()->json([
                            'status' => 'Success',
                            'message' => 'Login Successful',
                            'data' => $data
                            
                        ]);
                    
                
                    }
                else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Invalid Credentials'
                    ]);
                }
        }
    }
}
