@extends('layouts.admin')

@section('content')
    @session('success')
        <div class="alert alert-success mb-4">
            <h5 class="mb-0">
                <i class="bi bi-check-circle"></i>
                {{ session('success') }}
            </h5>
        </div>
    @endsession
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="mb-0">
                Публикации
            </h2>
            <a class="btn btn-outline-success" href="{{ route('admin.posts.create') }}">
                <i class="bi bi-plus-circle"></i>
                <span class="d-md-inline d-none">Создать</span>
            </a>
        </div>

        <div class="card-body">
            @if($posts->count())
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th></th>
                        <th>
                            Заголовок
                        </th>
                        <th>
                            Дата публикации
                        </th>
                        <th>
                            Дата и время создания
                        </th>
                        <th>
                            Дата и время обновления
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($posts as $post)
                        <tr>
                            <td>
                                @switch($post->privacy)
                                    @case(null)
                                        <i class="bi bi-eye text-success"></i> @break
                                    @case(\App\Enums\Post\PrivacyEnum::PROTECTED)
                                        <i class="bi bi-eye-slash"></i> @break
                                    @case(\App\Enums\Post\PrivacyEnum::PRIVATE)
                                        <i class="bi bi-eye-slash-fill text-danger"></i>@break
                                @endswitch
                            </td>
                            <td>
                                <a href="{{ route('admin.posts.show', ['post' => $post->id]) }}">
                                    {{ $post->title }}
                                </a>
                            </td>
                            <td>
                                {{ $post->published_at->format('d.m.Y H:i:s') }}
                            </td>
                            <td>
                                {{ $post->created_at->format('d.m.Y H:i:s') }}
                            </td>
                            <td>
                                {{ $post->updated_at->format('d.m.Y H:i:s') }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div>
                    {{ $posts->links() }}
                </div>
            @else
                Публикации отсутствуют
            @endif
        </div>
    </div>
@endsection
