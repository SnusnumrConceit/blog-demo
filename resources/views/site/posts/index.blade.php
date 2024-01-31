@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2>Посты</h2>
        </div>
        <div class="card-body">
            @if($posts->count())
                <div class="card">
                    <ul class="list-group list-group-flush">
                        @foreach($posts as $post)
                            <li class="list-group-item">
                                <a href="{{ route('site.posts.show', ['post' => $post->slug]) }}"
                                   class="nav-link"
                                >
                                    <h4>
                                        {{ $post->title }}
                                    </h4>
                                    @if($post->author_id)
                                        <div>
                                            <i class="bi bi-person"></i>
                                            <span>{{ $post->author->name }}</span>
                                        </div>
                                    @else
                                        <div>
                                            <i class="bi bi-alarm"></i>
                                            <span>Системное</span>
                                        </div>
                                    @endif
                                    <div class="form-text">
                                        <i class="bi bi-clock-history"></i>
                                        <span>{{ $post->published_at->format('d.m.Y H:i:s') }}</span>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                <div>
                    {{ $posts->links() }}
                </div>
            </div>
            @else
                Посты отсутствуют
            @endif
        </div>
    </div>
@stop
