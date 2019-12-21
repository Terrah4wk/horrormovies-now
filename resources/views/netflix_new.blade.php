@extends('layout')

@section('content')

    <section class="jumbotron text-center">
        <div class="container">
            <h1 class="jumbotron-heading">Neu auf Netflix</h1>
            @if(empty($movies))
                <p class="lead text-muted">Im Moment gibt es keine neuen Horrorfilme.</p>
            @endif
        </div>
    </section>

    @if(!empty($movies))
        <div class="album py-5 bg-light">
            <div class="container">

                @foreach(array_chunk($movies, 3) as $chunk)
                    <div class="row">
                        @foreach($chunk as $movie)
                            <div class="col-md-4">
                                <div class="card mb-3">
                                    <h3 class="card-header">{{ $movie->title }}</h3>
                                    <div class="card-body">
                                        @if(strlen($movie->genre) < 36)
                                            <h6 class="card-subtitle text-muted">{{ str_replace(',', ', ', $movie->genre) }}<br><br></h6>
                                        @else
                                            <h6 class="card-subtitle text-muted">{{ str_replace(',', ', ', $movie->genre) }}</h6>
                                        @endif
                                    </div>
                                    <img class="movie_image" src="{{ $movie->image }}" alt="{{ $movie->title }}">
                                    <div class="card-body">
                                        <p class="card-text">{{ strip_tags($movie->description) ?? "n/a"}}</p>
                                    </div>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">&nbsp;</li>
                                        <li class="list-group-item">
                                            @for ($i = 1; $i <= $movie->rating; $i++)
                                                <i class="material-icons">
                                                    star
                                                </i>
                                            @endfor
                                            @for ($i = $movie->rating; $i < 10; $i++)
                                                <i class="material-icons">
                                                    star_border
                                                </i>
                                            @endfor
                                        </li>
                                        <li class="list-group-item">{{ $movie->type === "TV Series" ? "Serie" : "Film" }} aus dem Jahr {{ $movie->released }}</li>
                                        <li class="list-group-item">
                                            {{ $movie->type === "TV Series" ? "Serie" : "Film" }} aus dem Jahr {{ $movie->released }}
                                        </li>
                                        <li class="list-group-item">
                                            Verfügbar: {{ date('d.m.y', strtotime($movie->release_date)) }}
                                        </li>
                                        <li class="list-group-item">
                                            Laufzeit: {{ $movie->runtime !== "" ? $movie->runtime : "n/a" }}
                                        </li>
                                    </ul>
                                    @if(strlen($movie->imdb) > 4)
                                    <div class="card-body">
                                        <a rel="nofollow" href="https://www.imdb.com/title/{{ $movie->imdb }}/" class="card-link">Imdb</a>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endif

@endsection