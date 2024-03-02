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
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="mb-0">
                Категории
            </h2>
            <a class="btn btn-outline-success" href="{{ route('admin.categories.create') }}">
                <i class="bi bi-plus-circle"></i>
                <span class="d-md-inline d-none">Создать</span>
            </a>
        </div>

        <div class="card-body">
            @if($categories->count())
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th></th>
                            <th>
                                Название
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
                        @foreach($categories as $category)
                            <tr>
                                <td>
                                    @switch($category->privacy)
                                        @case(null)
                                            <i class="bi bi-eye text-success"></i> @break
                                        @case(\App\Enums\Category\PrivacyEnum::PROTECTED)
                                            <i class="bi bi-eye-slash"></i> @break
                                        @case(\App\Enums\Category\PrivacyEnum::PRIVATE)
                                            <i class="bi bi-eye-slash-fill text-danger"></i>@break
                                    @endswitch
                                </td>
                                <td>
                                    <a href="{{ route('admin.categories.show', ['category' => $category->id]) }}">
                                        {{ $category->name }}
                                    </a>
                                </td>
                                <td>
                                    {{ $category->created_at->format('d.m.Y H:i:s') }}
                                </td>
                                <td>
                                    {{ $category->updated_at->format('d.m.Y H:i:s') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div>
                    {{ $categories->links() }}
                </div>
            @else
                Категории отсутствуют
            @endif
        </div>
    </div>
@endsection
