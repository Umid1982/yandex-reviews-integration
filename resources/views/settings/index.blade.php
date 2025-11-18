@extends('layouts.app')

@section('title', 'Настройки')

@section('content')
<div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Подключить Яндекс</h2>
    
    <p class="text-gray-600 mb-4">
        Укажите ссылку на Яндекс, пример https://yandex.ru/maps/org/ishop/1234567890/
    </p>

    <form action="{{ route('settings.store') }}" method="POST">
        @csrf
        
        <div class="mb-4">
            <label for="yandex_map_url" class="block text-sm font-medium text-gray-700 mb-2">
                Ссылка на Яндекс Карты
            </label>
            <input type="url" 
                   id="yandex_map_url" 
                   name="yandex_map_url" 
                   value="{{ old('yandex_map_url', $setting->yandex_map_url) }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                   placeholder="https://yandex.ru/maps/org/ishop/1234567890/">
            @error('yandex_map_url')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" 
                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Сохранить
        </button>
    </form>
</div>
@endsection

