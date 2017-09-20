@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <a href="#">
                        {{ $thread->creator->name }}
                    </a> posted: {{ $thread->title }}</div>

                <div class="panel-body">
                    {{ $thread->body }}
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            @foreach ($thread->replies as $reply)
                @include ('threads.reply')
            @endforeach
        </div>
    </div>

    @auth
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <form method="POST" action="{{ $thread->path() . '/replies' }}">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <textarea name="body" class="form-control" rows="3" placeholder="Have something to say?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-large btn-block btn-primary">Post</button>
                </form>
            </div>
        </div>
    @endauth

    @guest
        <p class="text-center">Please <a href="{{ route('login') }}">Sign-in</a> to Participate in this discussion</p>
    @endguest
</div>
@endsection
