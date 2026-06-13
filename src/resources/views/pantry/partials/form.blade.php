{{-- Shared pantry add/edit form. Expects: $item (PantryItem, may be unsaved), $action (URL), $units (value=>label). --}}
<form method="post" action="{{ $action }}" class="space-y-5"
      x-data="pantryForm({
        name: @js(old('ingredient_name', $item->ingredient?->name)),
        id: @js(old('ingredient_id', $item->ingredient_id)),
        url: @js(route('ingredients.search')),
      })">
    @csrf
    @if ($item->exists)
        @method('patch')
    @endif

    {{-- Назва продукту з автокомплітом по довіднику --}}
    <div class="relative">
        <label for="ingredient_name" class="block text-sm font-medium text-ink">Назва продукту</label>
        <input id="ingredient_name" name="ingredient_name" type="text" autocomplete="off" required
               x-model="query" @input.debounce.250ms="search()" @focus="results.length && (open = true)"
               @keydown.escape="open = false" @click.outside="open = false"
               placeholder="Напр. Картопля, Куряче філе…"
               class="mt-1 block w-full rounded-lg border-beige shadow-sm focus:border-brand focus:ring-brand">
        <input type="hidden" name="ingredient_id" :value="selectedId">

        <ul x-show="open && results.length" x-cloak
            class="absolute z-20 mt-1 w-full bg-white border border-beige rounded-md shadow-lg max-h-56 overflow-auto">
            <template x-for="r in results" :key="r.id">
                <li @click="choose(r)"
                    class="px-3 py-2 cursor-pointer hover:bg-sand flex items-center justify-between gap-2">
                    <span class="text-ink" x-text="r.name"></span>
                    <span class="text-xs text-muted" x-text="r.category"></span>
                </li>
            </template>
        </ul>
        <p x-show="open && query.trim().length && ! results.length" x-cloak class="mt-1 text-xs text-muted">
            Немає в довіднику — створимо новий продукт «<span x-text="query"></span>».
        </p>
        @error('ingredient_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Категорія — лише для нового (custom) продукту --}}
    <div x-show="! selectedId" x-cloak>
        <label for="category" class="block text-sm font-medium text-ink">Категорія <span class="text-muted">(необов'язково)</span></label>
        <input id="category" name="category" type="text" maxlength="50" value="{{ old('category') }}"
               placeholder="Напр. Овочі, Молочні продукти"
               class="mt-1 block w-full rounded-lg border-beige shadow-sm focus:border-brand focus:ring-brand">
        @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Кількість + одиниця --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="quantity" class="block text-sm font-medium text-ink">Кількість</label>
            <input id="quantity" name="quantity" type="number" step="0.001" min="0.001" required
                   value="{{ old('quantity', $item->quantity ? rtrim(rtrim((string) $item->quantity, '0'), '.') : '') }}"
                   class="mt-1 block w-full rounded-lg border-beige shadow-sm focus:border-brand focus:ring-brand">
            @error('quantity') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="unit" class="block text-sm font-medium text-ink">Одиниця</label>
            <select id="unit" name="unit" required
                    class="mt-1 block w-full rounded-lg border-beige shadow-sm focus:border-brand focus:ring-brand">
                @foreach ($units as $value => $label)
                    <option value="{{ $value }}" @selected(old('unit', $item->unit?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('unit') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit"
                class="inline-flex items-center px-5 py-2.5 bg-brand-accent text-ink text-sm font-semibold rounded-lg shadow-sm hover:bg-brand-accent-light focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 focus:ring-offset-cream transition">
            Зберегти продукт
        </button>
        <a href="{{ route('pantry.index') }}" class="text-sm text-muted hover:text-ink">Скасувати</a>
    </div>
</form>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pantryForm', ({ name, id, url }) => ({
            query: name || '',
            selectedId: id || '',
            results: [],
            open: false,
            async search() {
                this.selectedId = ''; // typing invalidates a prior pick → may become a custom product
                const q = this.query.trim();
                if (q.length < 1) { this.results = []; this.open = false; return; }
                try {
                    const res = await fetch(url + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
                    this.results = res.ok ? await res.json() : [];
                } catch (e) { this.results = []; }
                this.open = true;
            },
            choose(r) { this.query = r.name; this.selectedId = r.id; this.results = []; this.open = false; },
        }));
    });
</script>
