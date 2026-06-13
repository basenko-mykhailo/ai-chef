<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-brand-accent border border-transparent rounded-lg font-semibold text-sm text-ink shadow-sm hover:bg-brand-accent-light focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 focus:ring-offset-cream transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
