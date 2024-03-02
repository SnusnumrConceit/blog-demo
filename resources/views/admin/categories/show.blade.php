@extends('layouts.admin')

@section('content')
    @session('success'))
        <div class="alert alert-success mb-4">
            <h5 class="mb-0">
                <i class="bi bi-check-circle"></i>
                {{ session('success') }}
            </h5>
        </div>
    @endsession

    <div class="card">
        <div class="card-header">
            <h2 class="d-flex align-items-center mb-0">
                <a class="btn btn-outline-secondary" href="{{ url()->previous() }}">
                    <i class="bi bi-arrow-bar-left"></i>
                </a>
                <span class="mx-2">
                    Категория {{ $category->name }}
                </span>
            </h2>
        </div>

        <div class="card-body">
            <div class="card mt-2 mb-4">
                <div class="card-header bg-body-tertiary">
                    <h4>
                        Название
                    </h4>
                </div>
                <div class="card-body">
                    {{ $category->name }}
                </div>
            </div>
            <div class="card my-4">
                <div class="card-header bg-body-tertiary">
                    <h4>
                        Доступ
                    </h4>
                </div>
                <div class="card-body">
                    @switch($category->privacy)
                        @case(null)
                            <i class="bi bi-eye text-success"></i>
                            <span>Публичная</span>
                            @break
                        @case(\App\Enums\Category\PrivacyEnum::PROTECTED)
                            <i class="bi bi-eye-slash"></i>
                            <span>Скрыта от гостей</span>
                            @break
                        @case(\App\Enums\Category\PrivacyEnum::PRIVATE)
                            <i class="bi bi-eye-slash-fill text-danger"></i>
                            <span>Скрыта от всех</span>
                            @break
                    @endswitch
                </div>
            </div>
            <hr>
            <div class="d-flex">
                <a class="btn btn-outline-primary" href="{{ route('admin.categories.edit', ['category' => $category]) }}">
                    <i class="bi bi-pen"></i>
                    <span class="d-md-inline d-none d-sm-none">
                        Редактировать
                    </span>
                </a>
                <form action="{{ route('admin.categories.destroy', ['category' => $category]) }}" method="POST">
                    @csrf
                    <button class="btn btn-outline-danger mx-4" name="_method" value="DELETE">
                        <i class="bi bi-trash"></i>
                        <span class="d-md-inline d-none d-sm-none">
                            Удалить
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>

@endsection
