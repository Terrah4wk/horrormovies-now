@extends('layout')

@section('content')

    <section class="jumbotron text-center">
        <div class="container">
            <h1 class="jumbotron-heading">Jetzt im Free TV</h1>
            @if(!in_array('active', $active_movie))
                <p class="lead text-muted">Im Moment laufen keine Horrorstreifen.</p>
            @endif
        </div>
    </section>

    @if (! empty($movies) )
    <table class="table table-hover">
        <thead>
        <tr>
            <th scope="col">Sendezeit</th>
            <th scope="col">Kanal</th>
            <th scope="col">Titel</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($movies as $movie)
        @if ($active_movie[$movie->id] === 'active')
        <tr class="table-active" title="{{ $movie->comment_long }}">
        @else
        <tr title="{{ $movie->comment_long }}">
        @endif
            <th scope="row">{{ date('d.m.y H:i', strtotime($movie->starttime)) }}</th>
            <td>{{ $movie->name }}</td>
            <td>{{ $movie->title }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif
    
@endsection
