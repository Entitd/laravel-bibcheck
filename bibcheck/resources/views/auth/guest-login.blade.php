<x-layouts.app>
    @section('breadcrumb', 'Вход')

    <div class="min-h-[80vh] flex items-center justify-center">
        <div class="max-w-md w-full">
            {{-- Карточка входа --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-lg p-8">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-3xl">
                        👋
                    </div>
                    <h1 class="text-2xl font-bold mb-2">Добро пожаловать!</h1>
                    <p class="text-gray-600">Войдите или создайте аккаунт для работы с редактором BibTeX</p>
                </div>

                <div class="space-y-4">
                    {{-- Кнопка входа как гость --}}
                    <div class="p-4 rounded-xl bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200">
                        <h3 class="font-bold text-lg mb-2">🔓 Быстрый вход</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Войдите как гость для быстрого доступа. Сессия действует 24 часа.
                        </p>
                        <form action="{{ route('guest.login.submit') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-all transform hover:scale-105">
                                Войти как гость
                            </button>
                        </form>
                        <p class="text-xs text-gray-500 mt-2 text-center">
                            ⏱️ Сессия истекает через 24 часа
                        </p>
                    </div>

                    {{-- Разделитель --}}
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white text-gray-500">или</span>
                        </div>
                    </div>

                    {{-- Кнопка регистрации --}}
                    <a href="{{ route('register') }}" class="block w-full bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-all transform hover:scale-105 text-center">
                        Создать аккаунт
                    </a>

                    {{-- Кнопка входа --}}
                    <a href="{{ route('login') }}" class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-3 px-6 rounded-xl shadow transition-all text-center">
                        Войти в существующий аккаунт
                    </a>
                </div>
            </div>

            {{-- Информационный блок --}}
            <div class="mt-6 p-4 rounded-xl bg-blue-50 border border-blue-200">
                <h4 class="font-bold text-sm text-blue-900 mb-2">💡 Преимущества регистрации:</h4>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>✅ Сохранение API ключа OpenAlex</li>
                    <li>✅ История ваших проектов</li>
                    <li>✅ Нет ограничений по времени</li>
                    <li>✅ Персональные настройки</li>
                </ul>
            </div>
        </div>
    </div>
</x-layouts.app>
