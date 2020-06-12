<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @if ( isset($pageMeta) )
    <title>{{ $pageMeta->title }}</title>
    <meta name="description" content="{{ $pageMeta->description }}">
    <meta name="keywords" content="{{ $pageMeta->keywords }}">
    @endif
    <meta name="robots" content="{{ Request::is('impressum') ? 'noindex' : 'index' }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.slim.min.js" integrity="sha256-pasqAKBDmFT4eHoN2ndd6lN370kFiGUFyTiUHWhU7k8=" crossorigin="anonymous"></script>
    <script src="https://getbootstrap.com/docs/4.1/assets/js/vendor/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <h2 class="text-white">Horrorfilme / Jetzt ...</h2>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#horror" aria-controls="horror" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="horror">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item {{ Request::is('/') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('tv.index') }}">im Free TV <span class="sr-only">(current)</span></a>
            </li>
            <li class="nav-item {{ Request::is('netflix/neu-erschienen') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('netflix.new') }}">neu auf Netflix</a>
            </li>
            <li class="nav-item {{ Request::is('netflix/aktuell') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('netflix.current') }}">aktuell auf Netflix</a>
            </li>
            <li class="nav-item {{ Request::is('netflix/auslaufend') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('netflix.expire') }}">noch auf Netflix</a>
            </li>
            <li class="nav-item {{ Request::is('impressum') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('page.imprint') }}">Impressum</a>
            </li>
        </ul>
    </div>
</nav>
<div class="container">
    @yield('content')
</div>
</body>
</html>