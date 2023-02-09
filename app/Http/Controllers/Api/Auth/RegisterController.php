<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RegisterController extends Controller
{
    public function signup(Request $request){
        if($request->provider == "FACEBOOK" && $request->authToken){
            return $this->facebookRegister($request->authToken);
        }elseif($request->provider == "GOOGLE" && $request->idToken){
            return $this->googleRegister($request->idToken);
        }elseif($request->provider == "COGNITO" && $request->idToken){
            return $this->cognitoRegister($request->idToken);
        }
    }

    private function facebookRegister($token){
        $response = Http::get('https://graph.facebook.com/me',[
            'access_token' => $token,
            'fields' => 'name,email',
        ]);

        if($response->status() == 200 ){
            $FBUser = $response->object();

            $user = User::whereFacebookId($FBUser->id)->first();
            if($user){
                return response()->json([
                    'success' => false,
                    'message' => 'User already registerd!'
                ]);
            }else{
                $user                   = new User();
                $user->name             = $FBUser->name;
                $user->email            = $FBUser->email;
                $user->facebook_id      = $FBUser->id;
                $user->provider         = 'facebook';
                $user->password         = bcrypt(rand(1,1000 ));
                $user->save();

                $accessToken = $user->createToken('personal')->accessToken;

                return response()->json([
                    'success' => true,
                    'access_token' => $accessToken,
                    'token_type' => 'Bearer'
                ]);
            }
        }else{
            return $response->object();
        }
    }

    private function googleRegister($token){
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo?id_token='.$token);

        if($response->status() == 200 ){
            $GUser = $response->object();

            $user = User::whereGoogleId($GUser->sub)->first();
            if($user){
                return response()->json([
                    'success' => false,
                    'message' => 'User already registerd!'
                ]);
            }else{                
                $user                   = new User();
                $user->name             = $GUser->name;
                $user->email            = $GUser->email;
                $user->google_id        = $GUser->sub;
                $user->provider         = 'google';
                $user->password         = bcrypt(rand(1,1000 ));
                $user->save();

                $accessToken = $user->createToken('personal')->accessToken;

                return response()->json([
                    'success' => true,
                    'access_token' => $accessToken,
                    'token_type' => 'Bearer'
                ]);
            }
        }else{
            return $response->object();
        }
    }

    public function cognitoRegister($token){

        $sections = explode(".", $token);

        $header = json_decode(base64_decode($sections[0]));
        $payload = json_decode(base64_decode($sections[1]));
        $signature = $sections[2];

        if($payload->aud != env('COGNITO_CLIENT_ID')){
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'Invalid client Id'
            ]);
        }

        if($payload->iss != ('https://cognito-idp.'.env('COGNITO_REGION').'.amazonaws.com/'.env('COGNITO_POOL_ID'))){
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'Invalid pool domain'
            ]);
        }

        $expireAt = Carbon::create(gmdate("Y-m-d H:i:s", $payload->exp));
        if(Carbon::now() > $expireAt){
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'Token expired'
            ]);
        }

        $keysResponse = Http::get('https://cognito-idp.us-east-1.amazonaws.com/us-east-1_brssXjLW9/.well-known/jwks.json');
        $publicKeys = collect($keysResponse->object()->keys);
        $key = $publicKeys->where('kid', $header->kid)->first();
        if(!$key){
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'Invalid public key'
            ]);
        }

        $user = User::whereCognitoId($payload->sub)->first(); 
        if($user){
            return response()->json([
                'success' => false,
                'message' => 'User already registerd!'
            ]);
        }else{        
            $user                   = new User();
            $user->name             = $payload->{'cognito:username'};
            $user->email            = $payload->email;
            $user->cognito_id       = $payload->sub;
            $user->provider         = 'cognito';
            $user->password         = bcrypt(rand(1,1000 ));
            $user->save();

            $accessToken = $user->createToken('personal')->accessToken;

            return response()->json([
                'success' => true,
                'access_token' => $accessToken,
                'token_type' => 'Bearer'
            ]);
        }
    }
}
