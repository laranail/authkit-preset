@props(['disabled' => false, 'error' => false])

<input
    {{ $disabled ? 'disabled' : '' }}
    {!! $attributes->merge([
        'class' => 'block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20' . ($error ? ' border-red-500 focus:border-red-600 focus:ring-red-500/20' : '')
    ]) !!}
>
