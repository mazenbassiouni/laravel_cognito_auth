<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LoginController extends Controller
{
    public function signin(Request $request){
        if($request->provider == "FACEBOOK" && $request->authToken){
            return $this->facebookLogin($request->authToken);
        }elseif($request->provider == "GOOGLE" && $request->idToken){
            return $this->googleLogin($request->idToken);
        }elseif($request->provider == "COGNITO" && $request->idToken){
            return $this->cognitoLogin($request->idToken);
        }
    }

    private function facebookLogin($token){
        $response = Http::get('https://graph.facebook.com/me',[
            'access_token' => $token,
            'fields' => 'name,email',
        ]);

        if($response->status() == 200 ){
            $FBUser = $response->object();

            $user = User::whereFacebookId($FBUser->id)->first();
            if($user){
                $accessToken = $user->createToken('personal')->accessToken;

                return response()->json([
                    'success' => true,
                    'access_token' => $accessToken,
                    'token_type' => 'Bearer'
                ]);
                
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'This email is not registered!'
                ]);
            }
        }else{
            return $response->object();
        }
    }

    private function googleLogin($token){
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo?id_token='.$token);

        if($response->status() == 200 ){
            $GUser = $response->object();

            $user = User::whereGoogleId($GUser->sub)->first();
            if($user){
                $accessToken = $user->createToken('personal')->accessToken;

                return response()->json([
                    'success' => true,
                    'access_token' => $accessToken,
                    'token_type' => 'Bearer'
                ]);
                
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'This email is not registered!'
                ]);
            }
        }else{
            return $response->object();
        }
    }

    public function cognitoLogin($token){

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

        $keysResponse = Http::get('https://cognito-idp.'.env('COGNITO_REGION').'.amazonaws.com/'.env('COGNITO_POOL_ID').'/.well-known/jwks.json');
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
            $accessToken = $user->createToken('personal')->accessToken;

            return response()->json([
                'success' => true,
                'access_token' => $accessToken,
                'token_type' => 'Bearer'
            ]);

        }else{
            return response()->json([
                'success' => false,
                'message' => 'This email is not registered!'
            ]);
        }
    }
}