@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white p-6 shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10 ' . $class]) }}>
    {{ $slot }}
</div>
