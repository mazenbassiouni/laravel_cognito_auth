@extends('layouts.app')

@section('content')
    <h2>Login with</h2>
    <a href="{{route('facebook-login')}}"><button>Facebook</button></a>
    <a href="{{route('google-login')}}"><button>Google</button></a>
    <a href="{{route('cognito-login')}}"><button>Cognito</button></a>
@endsection