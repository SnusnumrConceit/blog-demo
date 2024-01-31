@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">{{ $post->title }}</h2>
        </div>
        <div class="card-body">
            <p>
                {{ $post->censored_content }}
            </p>
        </div>
        <div class="card-footer">
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
                <span>{{ $post->published_at?->format('d.m.Y H:i:s') }}</span>
            </div>
        </div>
    </div>
@endsection
