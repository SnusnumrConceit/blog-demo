@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="mb-0">
                Категории
            </h2>
        </div>

        <div class="card-body">
            @if($categories->count())
                <div class="card">
                    <ul class="list-group list-group-flush">
                    @foreach($categories as $category)
                        <li class="list-group-item">
                            <a href="{{ route('site.categories.show', ['category' => $category->slug]) }}"
                               class="nav-link d-flex justify-content-between align-items-center"
                            >
                                {{ $category->name }}
                                <span class="badge bg-primary">{{ $category->posts_count }}</span>
                            </a>
                        </li>
                    @endforeach
                    </ul>
                <div>
                    {{ $categories->links() }}
                </div>
                </div>
            @else
                Категории отсутствуют
            @endif
        </div>
    </div>
@endsection
