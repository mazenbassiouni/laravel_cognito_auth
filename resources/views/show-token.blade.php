@extends('layouts.app')

@section('css')
    <style>
        p{
            border: 1px solid black;
            border-radius: 5px;
            padding: 1rem;
            line-break: anywhere;
            width: 50vw;
        }
        h4{
            margin: 0;
        }
    </style>
@endsection

@section('content')
    <h2>Welcome {{auth()->user()->name}}!</h2>

    <h3>
        @if (auth()->user()->facebook_id)
            Login via Facebook
        @elseif(auth()->user()->google_id)
            login via Google
        @elseif(auth()->user()->cognito_id)
            login via Cognito
        @endif
    </h3>
    
    @if (isset($token))
        <h4>Your Token:</h4>
        <p>{{$token}}</p>
    @endif
@endsection