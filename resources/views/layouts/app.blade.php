<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Laravel Auth</title>
    <style>
        .btn-container{
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        button{
            padding: .5rem;
            margin: .5rem;
            font-size: 1rem;
            width: 10rem;
            font-weight: bold;
            cursor: pointer;
        }

        h2,h3{
            text-align: center;
        }

        #logoutForm{
            position: absolute;
            top: 0;
            right: 0;
        }
    </style>
    @yield('css')
</head>
<body>
    @auth
        <form action="{{url('logout')}}" method="POST" id="logoutForm">
            @csrf
            <button>logout</button>
        </form>
    @endauth
    <div class="btn-container">
        <div>
            @yield('content')
        </div>
    </div>
</body>
</html>