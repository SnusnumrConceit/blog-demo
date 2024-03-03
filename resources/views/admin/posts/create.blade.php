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
                <span class="mx-2">Создание поста</span>
            </h2>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.posts.store') }}" method="POST">
                @csrf
                <div class="mb-5">
                    <label for="title" class="form-label">
                        <h5>Название</h5>
                    </label>
                    <input class="form-control"
                           type="text"
                           id="title"
                           name="title"
                           value="{{ old('title') }}"
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
                    >{{ old('content') }}</textarea>
                </div>
                <div class="mb-5">
                    <label for="published_at" class="form-label">
                        Дата отложенной публикации
                    </label>
                    <input type="datetime-local"
                           class="form-control"
                           id="published_at"
                           name="published_at"
                           aria-describedby="published_atHelp"
                    >
                    <div id="nameHelp" class="form-text">
                        Не обязательное
                    </div>
                </div>
                <div class="mb-5">
                    <label for="privacy">
                        <h5>Доступ</h5>
                    </label>
                    <select name="privacy" id="privacy" class="form-control">
                        @foreach($privacyItems as $privacy)
                            <option value="{{ $privacy }}" @selected(old('privacy', $privacy))>
                                @switch($privacy)
                                    @case(null)
                                        <span>Публичный</span>
                                        @break
                                    @case(\App\Enums\PrivacyEnum::PROTECTED->value)
                                        <span>Скрыт от гостей</span>
                                        @break
                                    @case(\App\Enums\PrivacyEnum::PRIVATE->value)
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
                                           @checked(old(sprintf('categories.%d', $id)))
                                    >
                                    {{ $name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <hr>
                <div>
                    <button class="btn btn-outline-success">
                        <i class="bi bi-disc"></i>
                        <span class="d-sm-none d-none d-md-inline">Сохранить</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
