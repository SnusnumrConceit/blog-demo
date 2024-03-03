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
                <span class="mx-2">
                    Редактирование категории
                </span>
            </h2>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.categories.update', ['category' => $category->id]) }}" method="POST">
                @csrf
                <div class="mb-5">
                    <label for="name" id="name" class="form-label">
                        <h5>Название</h5>
                    </label>
                    <input
                        class="form-control"
                        type="text"
                        name="name"
                        value="{{ old('name', $category->name) }}"
                        aria-describedby="nameHelp"
                    >
                    <div id="nameHelp" class="form-text">
                        Не более 100 символов
                    </div>
                </div>
                <div class="mb-3">
                    <label class=form-control" for="privacy" name="privacy">
                        <h5>Доступ</h5>
                    </label>
                    <select class="form-control" name="privacy">
                        @foreach($privacyItems as $privacy)
                            <option value="{{ $privacy }}" @selected(old('privacy', $category->privacy))>
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
                <hr>
                <div>
                    <button class="btn btn-outline-success" name="_method" value="PUT">
                        <i class="bi bi-disc"></i>
                        <span class="d-none d-sm-none d-md-inline">Сохранить</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
