<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-ink leading-tight">{{ $recipe->name ?? 'Рецепт' }}</h2>
            <p class="text-sm text-muted">Згенеровано {{ $recipe->created_at?->format('d.m.Y H:i') }}</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            {{-- Тікет 3.10 замінить цю мінімальну сторінку на повноцінну картку рецепту. --}}
            @if ($recipe->generation_status !== \App\Enums\GenerationStatus::Completed)
                <div class="rounded-xl border border-amber-300 bg-amber-50 px-5 py-6 text-amber-800">
                    <p class="font-medium">Рецепт ще не готовий.</p>
                    <p class="mt-1 text-sm">Статус: {{ $recipe->generation_status->label() }}.</p>
                    @if ($recipe->generation_error)
                        <p class="mt-2 text-sm">{{ $recipe->generation_error }}</p>
                        <a href="{{ route('recipes.create') }}" class="mt-3 inline-block text-brand font-medium hover:underline">Спробувати ще раз</a>
                    @endif
                </div>
            @else
                @if ($recipe->description)
                    <p class="text-ink">{{ $recipe->description }}</p>
                @endif
                <p class="mt-1 text-sm text-muted">Порцій: {{ $recipe->servings }}</p>

                {{-- Інгредієнти --}}
                <div class="mt-6 bg-cream border border-beige rounded-xl overflow-hidden shadow-sm">
                    <div class="px-5 py-3 bg-brand text-cream font-medium">Інгредієнти</div>
                    <ul class="divide-y divide-beige">
                        @foreach ($recipe->ingredients_json ?? [] as $ingredient)
                            <li class="px-5 py-2.5 flex items-center justify-between gap-4 bg-white/50">
                                <span class="min-w-0 font-medium text-ink truncate">
                                    {{ $ingredient['name'] }}
                                    @if (($ingredient['in_pantry'] ?? false))
                                        <span class="ml-2 text-xs text-brand">є в коморі</span>
                                    @else
                                        <span class="ml-2 text-xs text-amber-700">треба купити</span>
                                    @endif
                                </span>
                                <span class="shrink-0 text-ink font-semibold">{{ $ingredient['quantity'] }} <span class="text-muted font-normal">{{ $ingredient['unit'] }}</span></span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Кроки --}}
                <div class="mt-6 bg-cream border border-beige rounded-xl overflow-hidden shadow-sm">
                    <div class="px-5 py-3 bg-brand text-cream font-medium">Приготування</div>
                    <ol class="list-decimal px-9 py-4 space-y-2 text-ink">
                        @foreach ($recipe->steps_json ?? [] as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>
                </div>

                {{-- КБЖУ --}}
                @php($kbju = $recipe->kbju_json ?? [])
                <div class="mt-6 bg-cream border border-beige rounded-xl overflow-hidden shadow-sm">
                    <div class="px-5 py-3 bg-brand text-cream font-medium">КБЖУ (на порцію)</div>
                    <div class="grid grid-cols-4 divide-x divide-beige text-center">
                        <div class="px-3 py-4"><div class="text-lg font-semibold text-ink">{{ $kbju['kcal'] ?? '—' }}</div><div class="text-xs text-muted">ккал</div></div>
                        <div class="px-3 py-4"><div class="text-lg font-semibold text-ink">{{ $kbju['protein'] ?? '—' }}</div><div class="text-xs text-muted">білки, г</div></div>
                        <div class="px-3 py-4"><div class="text-lg font-semibold text-ink">{{ $kbju['fat'] ?? '—' }}</div><div class="text-xs text-muted">жири, г</div></div>
                        <div class="px-3 py-4"><div class="text-lg font-semibold text-ink">{{ $kbju['carbs'] ?? '—' }}</div><div class="text-xs text-muted">вуглеводи, г</div></div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
