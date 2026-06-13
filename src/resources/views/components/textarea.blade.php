@props(['disabled' => false])

<textarea @disabled($disabled) {{ $attributes->merge(['class' => 'border-beige focus:border-brand focus:ring-brand rounded-lg shadow-sm']) }}>{{ $slot }}</textarea>
