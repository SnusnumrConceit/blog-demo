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
                <span class="mx-2">Создание категории</span>
            </h2>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="mb-5">
                    <label for="name" class="form-label">
                        <h5>Название</h5>
                    </label>
                    <input class="form-control"
                           type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           aria-describedby="nameHelp"
                    >
                    <div id="nameHelp" class="form-text">
                        Не более 100 символов
                    </div>
                </div>
                <div class="mb-3">
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
