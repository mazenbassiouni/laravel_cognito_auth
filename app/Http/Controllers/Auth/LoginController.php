<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class LoginController extends Controller
{
    public function facebookLogin(){
        $user = Socialite::driver('facebook')->user();

        $appsecret_proof= hash_hmac('sha256', $user->token, env('FACEBOOK_CLIENT_SECRET'));

        $response = Http::get('https://graph.facebook.com/me',[
            'access_token' => $user->token,
            'appsecret_proof' => $appsecret_proof,
            'fields' => 'name,email',
        ]);

        if($response->status() == 200 ){
            $FBUser = $response->object();

            $user = User::whereFacebookId($FBUser->id)->first();
            if($user){
                // sign in if already a user
                auth()->login($user);

                $token = $user->createToken('personal')->accessToken;

                return view('show-token')->with(['token' => $token]);
            }else{
                //sign up if not a user
                
                $user                   = new User();
                $user->name             = $FBUser->name;
                $user->email            = $FBUser->email;
                $user->facebook_id      = $FBUser->id;
                $user->password         = bcrypt(rand(1,1000 ));
                $user->save();

                auth()->login($user);

                $token = $user->createToken('personal')->accessToken;

                return view('show-token')->with(['token' => $token]);
            }
        }else{
            return $response->object();
        }
    }

    public function googleLogin(){
        $user = Socialite::driver('google')->user();
        
        $response = Http::get('https://www.googleapis.com/oauth2/v3/userinfo',[
            'access_token' => $user->token,
        ]);

        if($response->status() == 200 ){
            $GUser = $response->object();

            $user = User::whereGoogleId($GUser->sub)->first();
            if($user){
                // sign in if already a user
                auth()->login($user);

                $token = $user->createToken('personal')->accessToken;

                return view('show-token')->with(['token' => $token]);
            }else{
                //sign up if not a user
                
                $user                   = new User();
                $user->name             = $GUser->name;
                $user->email            = $GUser->email;
                $user->google_id        = $GUser->sub;
                $user->password         = bcrypt(rand(1,1000 ));
                $user->save();

                auth()->login($user);

                $token = $user->createToken('personal')->accessToken;

                return view('show-token')->with(['token' => $token]);
            }
        }else{
            return $response->object();
        }
    }

    public function cognitoLogin(){
        $user = Socialite::driver('cognito')->user();

        $response = Http::withToken($user->token)->post( env('COGNITO_HOST') .'/oauth2/userInfo');
        
        if($response->status() == 200 ){
            $CUser = $response->object();

            $user = User::whereCognitoId($CUser->sub)->first();
            if($user){
                // sign in if already a user
                auth()->login($user);

                $token = $user->createToken('personal')->accessToken;

                return view('show-token')->with(['token' => $token]);
            }else{
                //sign up if not a user
                
                $user                   = new User();
                $user->name             = $CUser->username;
                $user->email            = $CUser->email;
                $user->cognito_id        = $CUser->sub;
                $user->password         = bcrypt(rand(1,1000 ));
                $user->save();

                auth()->login($user);

                $token = $user->createToken('personal')->accessToken;

                return view('show-token')->with(['token' => $token]);
            }
        }else{
            return $response->object();
        }
    }
}