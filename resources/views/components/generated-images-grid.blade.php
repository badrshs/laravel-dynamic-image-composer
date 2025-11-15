{{-- resources/views/components/generated-images-grid.blade.php --}}
@props(['images', 'itemLabel' => 'Image'])

<div class="fi-section space-y-6">
    <div class="fi-section-header">
        <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
            Generated {{ $itemLabel }}s
        </h3>
    </div>

    <div class="grid gap-6" style="grid-template-columns: repeat(2, 1fr);">
        @foreach ($images as $image)
            <div
                class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex flex-col p-4">
                    <div class="relative aspect-[3/4] w-full overflow-hidden rounded-lg">
                        <img src="{{ $image['url'] }}" alt="{{ $itemLabel }} for {{ $image['name'] ?? 'Item' }}"
                            class="h-full w-full object-contain transition duration-300 hover:scale-105">
                    </div>

                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-950 dark:text-white text-center">
                            {{ $image['name'] ?? 'Generated ' . $itemLabel }}
                        </h4>

                        @if (isset($image['metadata']))
                            <p class="text-xs text-gray-600 dark:text-gray-400 text-center mt-1">
                                {{ $image['metadata'] }}
                            </p>
                        @endif

                        <div class="mt-2 flex justify-center gap-2">
                            <a href="{{ $image['url'] }}" target="_blank"
                                class="fi-btn fi-btn-size-sm inline-flex items-center justify-center rounded-lg bg-gray-50 px-3 py-2 text-sm font-medium text-gray-950 outline-none transition duration-75 hover:bg-gray-100 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                                <svg class="fi-icon w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" />
                                    <path fill-rule="evenodd"
                                        d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                View
                            </a>

                            <a href="{{ $image['url'] }}" target="_blank"
                                download="{{ $image['filename'] ?? 'image.png' }}"
                                class="fi-btn fi-btn-size-sm inline-flex items-center justify-center rounded-lg bg-gray-50 px-3 py-2 text-sm font-medium text-gray-950 outline-none transition duration-75 hover:bg-gray-100 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                                <svg class="fi-icon w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path
                                        d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" />
                                    <path
                                        d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                                </svg>
                                Download
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
