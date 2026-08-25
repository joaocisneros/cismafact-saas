@props(['id', 'title'])

<div x-data="{ open: false }"
     x-on:open-modal.window="if ($event.detail === '{{ $id }}') open = true"
     x-on:close-modal.window="if ($event.detail === '{{ $id }}') open = false"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-modal="true">

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-gray-900/50 transition-opacity"
         x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-on:click="open = false">
    </div>

    {{-- Modal --}}
    {{-- relative: el fondo es fixed y sin esto se pintaba por encima del contenido --}}
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg transform transition-all"
             x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             x-on:click.stop>

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="text-lg font-semibold text-gray-800">{{ $title }}</h3>
                <button x-on:click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-4">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
