@blaze(fold: true)

@php
$classes = Flux::classes()
    ->add('[:where(&)]:min-w-48 p-[.3125rem]')
    ->add('rounded-lg shadow-xs')
    ->add('border border-app-outline dark:border-app-outline')
    ->add('bg-app-surface-low dark:bg-app-surface-low text-accent-foreground')
    ->add('focus:outline-hidden')
    ;
@endphp

<ui-menu
    {{ $attributes->class($classes) }}
    popover="manual"
    data-flux-menu
>
    {{ $slot }}
</ui-menu>
