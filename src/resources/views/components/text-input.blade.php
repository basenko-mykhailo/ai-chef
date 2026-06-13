@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-beige focus:border-brand focus:ring-brand rounded-lg shadow-sm']) }}>
