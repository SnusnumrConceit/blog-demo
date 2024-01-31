@extends('layouts.admin')

@section('content')
    <div class="card">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="my-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card-header">
            <h2 class="d-flex align-items-center mb-0">
                <a class="btn btn-outline-secondary" href="{{ url()->previous() }}">
                    <i class="bi bi-arrow-bar-left"></i>
                </a>
                <span class="mx-2">Редактирование поста</span>
            </h2>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.posts.update', ['post' => $post->id]) }}" method="POST">
                @csrf
                <div class="mb-5">
                    <label for="title" class="form-label">
                        <h5>Название</h5>
                    </label>
                    <input class="form-control"
                           type="text"
                           id="title"
                           name="title"
                           value="{{ old('name', $post->title) }}"
                           aria-describedby="titleHelp"
                    >
                    <div id="titleHelp" class="form-text">
                        Не более 100 символов
                    </div>
                </div>
                <div class="mb-5">
                    <label for="content" class="form-label">
                        <h5>Содержание</h5>
                    </label>
                    <textarea class="form-control"
                              type="text"
                              id="content"
                              name="content"
                    >{{ old('content', $post->content) }}</textarea>
                </div>
                <div class="mb-5">
                    <label for="privacy">
                        <h5>Доступ</h5>
                    </label>
                    <select name="privacy" id="privacy" class="form-control">
                        @foreach($privacyItems as $privacy)
                            <option value="{{ $privacy }}" @selected(old('privacy', $post->$privacy))>
                                @switch($privacy)
                                    @case(null)
                                        <span>Публичный</span>
                                        @break
                                    @case(\App\Enums\Post\PrivacyEnum::PROTECTED)
                                        <span>Скрыт от гостей</span>
                                        @break
                                    @case(\App\Enums\Post\PrivacyEnum::PRIVATE)
                                        <span>Скрыт от всех</span>
                                        @break
                                @endswitch
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-5">
                    <div class="row">
                        @foreach ($categories as $id => $name)
                            <div class="col-3">
                                <label for="categories[{{ $id }}]">
                                    <input type="checkbox"
                                           name="categories[{{ $id }}]"
                                           id="categories[{{ $id }}]"
                                           value="{{ $id }}"
                                           @checked(
                                                $post->categories->contains(fn (\App\Models\Category $category) => $category->id === $id)
                                           )
                                    >
                                    {{ $name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <hr>
                <div>
                    <button class="btn btn-outline-success" name="_method" value="PUT">
                        <i class="bi bi-disc"></i>
                        <span class="d-sm-none d-none d-md-inline">Сохранить</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
