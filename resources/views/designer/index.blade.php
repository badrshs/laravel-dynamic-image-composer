{{-- resources/views/designer/index.blade.php --}}
@php
    // Precompute payloads to avoid Blade parsing issues
    $tplPayload = $template->only(['id', 'name', 'width', 'height', 'background_image']);
    $elsPayload = $template->elements
        ->map(function ($e) {
            return [
                'id' => $e->id,
                'name' => $e->name,
                'type' => $e->element_type,
                'image_path' => $e->image_path,
                'x_position' => (int) $e->x_position,
                'y_position' => (int) $e->y_position,
                'width' => (int) $e->width,
                'height' => (int) $e->height,
                'z_index' => (int) ($e->z_index ?? 1),
                'opacity' => (float) ($e->opacity ?? 1),
                'rotation' => (float) ($e->rotation ?? 0),
            ];
        })
        ->values()
        ->all();
    
    // Transform field configuration for the designer
    $fieldConfiguration = collect($template->field_configuration['fields'] ?? [])
        ->map(function ($config, $key) {
            return [
                'key' => $key,
                'centerX' => $config['centerX'] ?? false,
                'x' => $config['x'] ?? 0,
                'y' => $config['y'] ?? 0,
                'fontSize' => $config['fontSize'] ?? 64,
                'color' => $config['color'] ?? 'black',
                'alignment' => $config['alignment'] ?? 'center',
                'font' => $config['font'] ?? 'default',
            ];
        })
        ->values()
        ->all();
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Template Designer — {{ $template->name }}</title>

    <!-- Tailwind (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Vue 3 (CDN) -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>

    <!-- Interact.js -->
    <script src="https://unpkg.com/interactjs/dist/interact.min.js"></script>

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <style>
        .template-canvas {
            position: relative;
            overflow: hidden;
            background: #f8f9fa;
            border: 2px dashed #e5e7eb;
            user-select: none;
            touch-action: none;
        }

        .bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: fill;
            pointer-events: none;
            user-select: none;
        }

        .template-element {
            position: absolute;
            border: 2px solid transparent;
            will-change: transform, width, height, left, top;
            cursor: grab;
            touch-action: none;
            transform-origin: center;
        }

        .template-element.is-selected {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .2);
        }

        .template-element.is-dragging {
            cursor: grabbing;
        }

        .template-element img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            pointer-events: none;
        }

        .resize-handle {
            position: absolute;
            width: 10px;
            height: 10px;
            right: -5px;
            bottom: -5px;
            background: #3b82f6;
            border: 1px solid #fff;
            border-radius: 50%;
            cursor: se-resize;
            pointer-events: auto;
        }

        .drop-zone.drag-over {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, .06);
        }

        .scrollbars::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        .scrollbars::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 999px;
        }

        .fade-enter-active,
        .fade-leave-active {
            transition: opacity .2s;
        }

        .fade-enter-from,
        .fade-leave-to {
            opacity: 0;
        }

        /* --- field markers (placeholders) --- */
        .field-marker {
            position: absolute;
            transform: translate(-50%, -50%);
            background: rgba(59, 130, 246, .08);
            border: 1px dashed #3b82f6;
            padding: 2px 6px;
            font-size: 12px;
            border-radius: 6px;
            color: #1f2937;
            white-space: nowrap;
            cursor: grab;
            user-select: none;
            touch-action: none;
        }

        .field-marker.is-selected {
            background: rgba(59, 130, 246, .2);
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, .3);
        }

        .field-marker.is-dragging {
            cursor: grabbing;
            background: rgba(59, 130, 246, .15);
            border-color: #2563eb;
        }

        .field-marker .dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            display: inline-block;
            margin-right: 4px;
            background: #3b82f6;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div id="app" class="h-screen flex flex-col">

        <!-- Top bar -->
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h1 class="text-lg sm:text-xl font-semibold truncate">{{ $template->name }} — Designer</h1>
                <div class="hidden sm:flex items-center gap-1">
                    <button @click="zoomOut" class="p-2 rounded hover:bg-gray-100" title="Zoom out"><i
                            class="fa-solid fa-magnifying-glass-minus"></i></button>
                    <span class="text-sm w-12 text-center">@{{ Math.round(scale * 100) }}%</span>
                    <button @click="zoomIn" class="p-2 rounded hover:bg-gray-100" title="Zoom in"><i
                            class="fa-solid fa-magnifying-glass-plus"></i></button>
                    <button @click="resetZoom"
                        class="px-3 py-1.5 rounded bg-gray-200 hover:bg-gray-300 text-sm">Reset</button>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="preview" class="px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                    <i class="fa-solid fa-eye mr-2"></i> Preview
                </button>
                <button @click="generate" class="px-3 py-2 rounded bg-green-600 text-white hover:bg-green-700">
                    <i class="fa-solid fa-download mr-2"></i> Generate
                </button>
            </div>
        </div>

        <!-- Main -->
        <div class="flex-1 grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-0">
            <!-- Canvas area -->
            <div class="p-4 sm:p-8 overflow-auto scrollbars">
                <div ref="canvas" class="template-canvas drop-zone mx-auto" :style="canvasStyle"
                    @click="clearSelections" @dragenter.prevent @dragover.prevent="onDragOver"
                    @dragleave.prevent="onDragLeave" @drop.prevent="onDrop">
                    <!-- Background image drives exact pixel size & onload natural size -->
                    <img class="bg" :src="storageBase + '/' + template.background_image" alt="Template background"
                        @load="onBgLoaded" />

                    <!-- Elements (images) -->
                    <div v-for="el in elements" :key="el.id" :id="'el-' + el.id" class="template-element"
                        :class="{ 'is-selected': selectedId === el.id }" :style="elementStyle(el)"
                        @mousedown="handleElementMouseDown(el.id, $event)">
                        <img :src="storageBase + '/' + el.image_path" :alt="el.name" draggable="false" />
                        <div v-if="selectedId===el.id" class="resize-handle"></div>
                    </div>

                    <!-- Placeholder field markers (draggable) -->
                    <div v-for="(f, i) in fields" :key="'field-' + i" :id="'field-' + i" class="field-marker"
                        :class="{ 'is-selected': selectedFieldIndex === i }" :style="fieldStyle(f)"
                        @mousedown="handleFieldMouseDown(i, $event)">
                        <span class="dot"></span>@{{ f.key }}
                    </div>
                </div>

                <p class="mt-4 text-center text-gray-500 text-sm">
                    Tip: drag & drop images onto the canvas, drag field markers to position them, use <kbd>Delete</kbd>
                    to remove elements, and arrow keys for nudging.
                </p>
            </div>

            <!-- Sidebar -->
            <aside class="bg-white border-l border-gray-200 p-4 sm:p-6 space-y-6">

                <!-- Live Preview -->
                <section>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold">Live Preview</h3>
                        <span v-if="previewLoading" class="text-xs text-gray-500 flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Updating…
                        </span>
                    </div>
                    <div class="border rounded overflow-hidden bg-gray-50">
                        <div class="w-full" style="aspect-ratio: 16 / 10;">
                            <img v-if="previewUrl" :src="previewUrl" alt="Live preview"
                                class="w-full h-full object-contain" @load="onPreviewLoaded"
                                @error="onPreviewError" />
                            <div v-else
                                class="w-full h-full flex items-center justify-center text-gray-400 text-sm">
                                Preview will appear here
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Updates automatically. Uses sample data.</p>
                </section>

                <!-- Elements list -->
                <section>
                    <h3 class="font-semibold mb-3">Elements</h3>
                    <div v-if="!elements.length" class="text-gray-500 text-sm">No elements yet.</div>
                    <div v-else class="space-y-2">
                        <div v-for="el in elements" :key="'list-' + el.id"
                            class="flex items-center gap-2 p-2 rounded border cursor-pointer"
                            :class="selectedId === el.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
                            @click="select(el.id)">
                            <img :src="storageBase + '/' + el.image_path" :alt="el.name"
                                class="w-10 h-10 object-contain rounded" />
                            <span class="flex-1 text-sm truncate">@{{ el.name }}</span>
                            <button @click.stop="zUp(el)" class="text-gray-600 hover:text-blue-600" title="Bring forward">
                                <i class="fa-solid fa-arrow-up text-xs"></i>
                            </button>
                            <button @click.stop="zDown(el)" class="text-gray-600 hover:text-blue-600" title="Send backward">
                                <i class="fa-solid fa-arrow-down text-xs"></i>
                            </button>
                            <button @click.stop="remove(el)" class="text-red-600 hover:text-red-800" title="Delete">
                                <i class="fa-solid fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Properties of selected element -->
                <section v-if="current" class="space-y-3">
                    <h3 class="font-semibold">Properties</h3>

                    <div class="grid grid-cols-2 gap-2">
                        <label class="text-xs text-gray-500">X
                            <input type="number" class="mt-1 w-full border rounded px-2 py-1"
                                v-model.number="current.x_position" @input="persist(current)" />
                        </label>
                        <label class="text-xs text-gray-500">Y
                            <input type="number" class="mt-1 w-full border rounded px-2 py-1"
                                v-model.number="current.y_position" @input="persist(current)" />
                        </label>
                        <label class="text-xs text-gray-500">Width
                            <input type="number" class="mt-1 w-full border rounded px-2 py-1"
                                v-model.number="current.width" @input="persist(current)" />
                        </label>
                        <label class="text-xs text-gray-500">Height
                            <input type="number" class="mt-1 w-full border rounded px-2 py-1"
                                v-model.number="current.height" @input="persist(current)" />
                        </label>
                    </div>

                    <label class="text-xs text-gray-500 block">Opacity (@{{ Math.round((current.opacity ?? 1) * 100) }}%)
                        <input type="range" min="0" max="1" step="0.1" class="w-full"
                            v-model.number="current.opacity" @input="persist(current)" />
                    </label>

                    <label class="text-xs text-gray-500 block">Rotation (deg)
                        <input type="number" class="mt-1 w-full border rounded px-2 py-1"
                            v-model.number="current.rotation" @input="persist(current)" />
                    </label>
                </section>

                <!-- Fields (placeholders) -->
                <section class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold">Fields (placeholders)</h3>
                    </div>

                    <div v-if="!fields.length" class="text-gray-500 text-sm">No fields yet.</div>

                    <div v-for="(f, i) in fields" :key="'f-' + i" class="p-3 border rounded space-y-2">
                        <div class="flex items-center gap-2">
                            <input type="text" class="flex-1 px-2 py-1 text-sm border rounded"
                                placeholder="Field key" v-model.trim="f.key" @input="onFieldChanged" />
                            <label class="flex items-center gap-1 text-xs">
                                <input type="checkbox" v-model="f.centerX" @change="onCenterXChange(i)" />
                                Center X
                            </label>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <label class="text-xs text-gray-500">X
                                <input type="number" class="mt-1 w-full border rounded px-2 py-1 text-sm"
                                    v-model.number="f.x" :disabled="f.centerX" @input="onFieldChanged" />
                            </label>
                            <label class="text-xs text-gray-500">Y
                                <input type="number" class="mt-1 w-full border rounded px-2 py-1 text-sm"
                                    v-model.number="f.y" @input="onFieldChanged" />
                            </label>
                            <label class="text-xs text-gray-500">Font Size
                                <input type="number" class="mt-1 w-full border rounded px-2 py-1 text-sm"
                                    v-model.number="f.fontSize" @input="onFieldChanged" />
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <label class="text-xs text-gray-500">Color
                                <select class="mt-1 w-full border rounded px-2 py-1 text-sm" v-model="f.color"
                                    @change="onFieldChanged">
                                    <option value="black">Black</option>
                                    <option value="white">White</option>
                                    <option value="gold">Gold</option>
                                    <option value="brown">Brown</option>
                                    <option value="gray">Gray</option>
                                </select>
                            </label>
                            <label class="text-xs text-gray-500">Alignment
                                <select class="mt-1 w-full border rounded px-2 py-1 text-sm" v-model="f.alignment"
                                    @change="onFieldChanged">
                                    <option value="left">Left</option>
                                    <option value="center">Center</option>
                                    <option value="right">Right</option>
                                </select>
                            </label>
                        </div>

                        <div class="flex justify-between pt-1">
                            <label class="text-xs text-gray-500 flex-1">Font
                                <select class="mt-1 w-full border rounded px-2 py-1 text-sm" v-model="f.font"
                                    @change="onFieldChanged">
                                    <option value="default">Default</option>
                                    <option value="monotype">Monotype</option>
                                    <option value="libre">Libre</option>
                                </select>
                            </label>
                        </div>
                    </div>
                </section>

                <!-- Upload -->
                <section>
                    <h3 class="font-semibold mb-2">Add Element</h3>
                    <div class="space-y-2">
                        <input type="text" class="w-full border rounded px-2 py-2"
                            placeholder="Name (e.g. Signature)" v-model.trim="upload.name">
                        <select class="w-full border rounded px-2 py-2" v-model="upload.type">
                            <option value="signature">Signature</option>
                            <option value="seal">Seal</option>
                            <option value="logo">Logo</option>
                            <option value="decoration">Decoration</option>
                            <option value="other">Other</option>
                        </select>
                        <input type="file" accept="image/*" class="w-full border rounded px-2 py-2"
                            @change="onPickFile" />
                        <button
                            @click="doUpload"
                            :disabled="!upload.file || !upload.name || isUploading"
                            class="w-full px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                            <i class="fa-solid fa-upload mr-2"></i> Upload & Add
                        </button>
                        <div v-if="isUploading" class="w-full bg-gray-200 rounded h-2">
                            <div class="bg-blue-600 h-2 rounded transition-all" :style="{ width: uploadProgress + '%' }">
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Or drop an image directly on the canvas.</p>
                </section>
            </aside>
        </div>

        <!-- Toast -->
        <transition name="fade">
            <div v-if="toast"
                class="fixed bottom-4 left-1/2 -translate-x-1/2 bg-black text-white text-sm px-3 py-2 rounded shadow">
                @{{ toast }}
            </div>
        </transition>
    </div>

    <script>
        const {
            createApp,
            nextTick
        } = Vue;

        createApp({
            data() {
                return {
                    template: @json($tplPayload),
                    elements: @json($elsPayload),

                    // Load fields from controller (properly transformed)
                    fields: @json($fieldConfiguration),

                    scale: 0.5,
                    selectedId: null,
                    selectedFieldIndex: null,

                    // click/drag state tracking
                    isDragging: false,
                    clickTimeout: null,

                    // real background pixel size (used for accurate scaling)
                    bgW: null,
                    bgH: null,

                    // upload
                    isUploading: false,
                    uploadProgress: 0,
                    upload: {
                        name: '',
                        type: 'signature',
                        file: null
                    },

                    toast: '',
                    storageBase: "{{ asset('storage') }}",

                    urls: {
                        upload: "{{ route('designer.elements.upload', $template) }}",
                        generate: "{{ route('designer.generate', $template) }}",
                        preview: "{{ route('designer.preview', $template) }}",
                        saveConfig: "{{ route('designer.save-configuration', $template) }}",
                        update: "{{ route('designer.elements.update', ['template' => $template, 'element' => 'ELEMENT_ID']) }}",
                        delete: "{{ route('designer.elements.delete', ['template' => $template, 'element' => 'ELEMENT_ID']) }}",
                    },

                    // live preview state
                    previewUrl: '',
                    previewLoading: false,
                    _previewDebounceTimer: null,
                    _previewMaxWaitTimer: null,
                    _previewPending: false,
                }
            },
            computed: {
                current() {
                    return this.elements.find(e => e.id === this.selectedId) || null;
                },
                canvasStyle() {
                    const W = this.bgW || this.template.width || 1600;
                    const H = this.bgH || this.template.height || 1200;
                    return {
                        width: (W * this.scale) + 'px',
                        height: (H * this.scale) + 'px'
                    };
                }
            },
            methods: {
                /* --------- elements (images) --------- */
                elementStyle(el) {
                    return {
                        left: (el.x_position * this.scale) + 'px',
                        top: (el.y_position * this.scale) + 'px',
                        width: (el.width * this.scale) + 'px',
                        height: (el.height * this.scale) + 'px',
                        zIndex: el.z_index ?? 1,
                        opacity: el.opacity ?? 1,
                        transform: `rotate(${el.rotation ?? 0}deg)`
                    };
                },
                select(id) {
                    this.selectedId = id;
                    this.selectedFieldIndex = null;
                },
                selectField(index) {
                    this.selectedFieldIndex = index;
                    this.selectedId = null;
                },
                clearSelections() {
                    this.selectedId = null;
                    this.selectedFieldIndex = null;
                },
                handleElementMouseDown(id, event) {
                    if (this.clickTimeout) {
                        clearTimeout(this.clickTimeout);
                        this.clickTimeout = null;
                    }
                    if (event.target.classList.contains('resize-handle')) return;
                    this.clickTimeout = setTimeout(() => {
                        if (!this.isDragging) this.select(id);
                        this.clickTimeout = null;
                    }, 150);
                },
                handleFieldMouseDown(index, event) {
                    if (this.clickTimeout) {
                        clearTimeout(this.clickTimeout);
                        this.clickTimeout = null;
                    }
                    this.clickTimeout = setTimeout(() => {
                        if (!this.isDragging) this.selectField(index);
                        this.clickTimeout = null;
                    }, 150);
                },

                /* --------- upload --------- */
                onPickFile(e) {
                    this.upload.file = e.target.files[0] || null;
                },
                openUpload() {
                    this.$el.querySelector('input[type=file]').click();
                },

                /* --------- bg load + scale --------- */
                onBgLoaded(e) {
                    this.bgW = e.target.naturalWidth;
                    this.bgH = e.target.naturalHeight;
                    this.recalcScale();
                },

                /* --------- DnD --------- */
                onDragOver(e) {
                    if (e.dataTransfer) e.dataTransfer.dropEffect = 'copy';
                    e.currentTarget.classList.add('drag-over');
                },
                onDragLeave(e) {
                    e.currentTarget.classList.remove('drag-over');
                },
                onDrop(e) {
                    e.currentTarget.classList.remove('drag-over');
                    const f = e.dataTransfer?.files?.[0];
                    if (!f) return;
                    if (!f.type.startsWith('image/')) return this.toastMsg('Please drop an image.');
                    this.upload = {
                        name: f.name.replace(/\.[^.]+$/, '') || 'Element',
                        type: 'signature',
                        file: f
                    };
                    this.doUpload();
                },

                doUpload() {
                    if (!this.upload.file || !this.upload.name || !this.upload.type) return;
                    const fd = new FormData();
                    fd.append('image', this.upload.file);
                    fd.append('name', this.upload.name);
                    fd.append('type', this.upload.type);

                    this.isUploading = true;
                    this.uploadProgress = 0;

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', this.urls.upload, true);
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
                    xhr.setRequestHeader('Accept', 'application/json');

                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                    };
                    xhr.onreadystatechange = () => {
                        if (xhr.readyState !== XMLHttpRequest.DONE) return;
                        this.isUploading = false;
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (xhr.status === 200 && res.success && res.element) {
                                this.elements.push(res.element);
                                this.toastMsg('Element uploaded successfully.');
                                this.schedulePreview();
                            } else {
                                this.toastMsg(res.error || 'Upload failed');
                            }
                        } catch (err) {
                            this.toastMsg('Upload failed');
                        }
                        this.upload = {
                            name: '',
                            type: 'signature',
                            file: null
                        };
                        this.uploadProgress = 0;
                    };
                    xhr.send(fd);
                },

                async persist(el) {
                    if (!el) return;
                    const url = this.urls.update.replace('ELEMENT_ID', String(el.id));
                    const body = {
                        x_position: Math.round(Math.max(0, el.x_position)),
                        y_position: Math.round(Math.max(0, el.y_position)),
                        width: Math.round(Math.max(10, el.width)),
                        height: Math.round(Math.max(10, el.height)),
                        z_index: el.z_index ?? 1,
                        opacity: el.opacity ?? 1,
                        rotation: el.rotation ?? 0
                    };
                    try {
                        const r = await fetch(url, {
                            method: 'PUT',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(body)
                        });
                        const res = await r.json();
                        if (!r.ok || !res.success) throw new Error(res.error || 'Save failed');
                        this.schedulePreview();
                    } catch (e) {
                        this.toastMsg(e.message || 'Save failed');
                    }
                },

                async remove(el) {
                    if (!el) return;
                    if (!confirm('Delete this element?')) return;
                    const url = this.urls.delete.replace('ELEMENT_ID', String(el.id));
                    try {
                        const r = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        });
                        const res = await r.json();
                        if (!r.ok || !res.success) throw new Error(res.error || 'Delete failed');
                        this.elements = this.elements.filter(x => x.id !== el.id);
                        if (this.selectedId === el.id) this.selectedId = null;
                        this.toastMsg('Deleted.');
                    } catch (e) {
                        this.toastMsg(e.message || 'Delete failed');
                    }
                },

                /* --------- placeholders (fields) --------- */
                onCenterXChange(fieldIndex) {
                    this.$nextTick(() => this.bindFieldInteract(fieldIndex));
                    this.schedulePreview();
                },
                fieldStyle(f) {
                    const W = this.bgW || this.template.width || 1600;
                    const left = f.centerX ? (W * this.scale) / 2 : (f.x * this.scale);
                    const top = (f.y * this.scale);
                    return {
                        left: left + 'px',
                        top: top + 'px',
                        zIndex: 9999
                    };
                },
                buildConfig() {
                    const fields = {};
                    for (const f of this.fields) {
                        if (!f.key) continue;
                        fields[f.key] = {
                            centerX: f.centerX ?? false,
                            x: f.x ?? 0,
                            y: f.y ?? 0,
                            fontSize: f.fontSize ?? 64,
                            color: f.color ?? 'black',
                            alignment: f.alignment ?? 'center',
                            font: f.font ?? 'default',
                        };
                    }
                    return {
                        template: this.template.background_image,
                        fields,
                    };
                },
                onFieldChanged() {
                    this.schedulePreview();
                },
                async saveConfiguration() {
                    const cfg = this.buildConfig();

                    try {
                        const res = await fetch(this.urls.saveConfig, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(cfg)
                        });

                        const data = await res.json();
                        if (data.success) {
                            this.toastMsg(data.message || 'Configuration saved successfully.');
                        } else {
                            this.toastMsg(data.error || 'Failed to save configuration.');
                        }
                    } catch (err) {
                        console.error(err);
                        this.toastMsg('Failed to save configuration.');
                    }
                },
                downloadConfig() {
                    const cfg = this.buildConfig();
                    const blob = new Blob([JSON.stringify(cfg, null, 2)], {
                        type: 'application/json'
                    });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `template-config-${this.template.id}.json`;
                    document.body.appendChild(a);
                    a.click();
                    URL.revokeObjectURL(url);
                    a.remove();
                    this.toastMsg('Config exported.');
                },

                /* --------- generate --------- */
                async generate() {
                    try {
                        await this.saveConfiguration();
                        const r = await fetch(this.urls.generate, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        });
                        const data = await r.json();
                        if (data.success && data.url) {
                            window.open(data.url, '_blank');
                        } else {
                            this.toastMsg(data.error || 'Generation failed');
                        }
                        this.toastMsg('Saved.');
                    } catch (e) {
                        this.toastMsg(e.message || 'Generation failed');
                    }
                },

                /* --------- preview --------- */
                async preview() {
                    try {
                        await this.saveConfiguration();
                    } catch (_) {
                        // continue anyway
                    }

                    window.open(this.urls.preview, '_blank');
                },

                /* --------- auto live preview (debounced with maxWait) --------- */
                schedulePreview() {
                    this._previewPending = true;
                    if (this._previewDebounceTimer) clearTimeout(this._previewDebounceTimer);
                    this._previewDebounceTimer = setTimeout(() => this._firePreview(), 1000);
                    if (!this._previewMaxWaitTimer) {
                        this._previewMaxWaitTimer = setTimeout(() => this._firePreview(), 10000);
                    }
                },
                async _firePreview() {
                    if (!this._previewPending) return;
                    this._previewPending = false;
                    if (this._previewDebounceTimer) {
                        clearTimeout(this._previewDebounceTimer);
                        this._previewDebounceTimer = null;
                    }
                    if (this._previewMaxWaitTimer) {
                        clearTimeout(this._previewMaxWaitTimer);
                        this._previewMaxWaitTimer = null;
                    }

                    this.previewLoading = true;
                    try {
                        await this.saveConfiguration();
                    } catch (_) {
                        // ignore
                    }

                    this.previewUrl = this.urls.preview + '?t=' + Date.now();
                },
                onPreviewLoaded() {
                    this.previewLoading = false;
                },
                onPreviewError() {
                    this.previewLoading = false;
                },

                /* --------- zoom / zIndex --------- */
                zoomIn() {
                    this.scale = Math.min(2, +(this.scale + 0.1).toFixed(2));
                },
                zoomOut() {
                    this.scale = Math.max(0.1, +(this.scale - 0.1).toFixed(2));
                },
                resetZoom() {
                    this.scale = 0.5;
                },
                zUp(el) {
                    el.z_index = Math.min(100, (el.z_index ?? 1) + 1);
                    this.persist(el);
                },
                zDown(el) {
                    el.z_index = Math.max(0, (el.z_index ?? 1) - 1);
                    this.persist(el);
                },

                bindAll() {
                    if (typeof window.interact !== 'function') {
                        console.warn('Interact.js not loaded');
                        return;
                    }
                    this.$nextTick(() => {
                        this.elements.forEach(e => this.bindInteract(e.id));
                        this.fields.forEach((f, i) => this.bindFieldInteract(i));
                    });
                },
                bindInteract(id) {
                    const sel = '#el-' + id;
                    const node = document.querySelector(sel);
                    const self = this;
                    if (!node) return;

                    interact(node)
                        .draggable({
                            inertia: false,
                            modifiers: [],
                            listeners: {
                                start(event) {
                                    self.isDragging = true;
                                    event.target.classList.add('is-dragging');
                                },
                                move(event) {
                                    const el = self.elements.find(x => x.id === id);
                                    if (!el) return;
                                    el.x_position = Math.round(el.x_position + event.dx / self.scale);
                                    el.y_position = Math.round(el.y_position + event.dy / self.scale);
                                },
                                end(event) {
                                    event.target.classList.remove('is-dragging');
                                    const el = self.elements.find(x => x.id === id);
                                    if (el) self.persist(el);
                                    setTimeout(() => {
                                        self.isDragging = false;
                                    }, 200);
                                }
                            }
                        })
                        .resizable({
                            edges: {
                                left: false,
                                right: '.resize-handle',
                                bottom: '.resize-handle',
                                top: false
                            },
                            listeners: {
                                start() {
                                    self.isDragging = true;
                                },
                                move(event) {
                                    const el = self.elements.find(x => x.id === id);
                                    if (!el) return;
                                    el.width = Math.round(el.width + event.deltaRect.width / self.scale);
                                    el.height = Math.round(el.height + event.deltaRect.height / self.scale);
                                    el.x_position = Math.round(el.x_position + event.deltaRect.left / self.scale);
                                    el.y_position = Math.round(el.y_position + event.deltaRect.top / self.scale);
                                },
                                end() {
                                    const el = self.elements.find(x => x.id === id);
                                    if (el) self.persist(el);
                                    setTimeout(() => {
                                        self.isDragging = false;
                                    }, 200);
                                }
                            },
                            modifiers: [
                                interact.modifiers.restrictSize({
                                    min: {
                                        width: 10,
                                        height: 10
                                    }
                                })
                            ]
                        });
                },

                bindFieldInteract(fieldIndex) {
                    const sel = '#field-' + fieldIndex;
                    const node = document.querySelector(sel);
                    const self = this;
                    if (!node) return;

                    interact(node).unset();

                    interact(node)
                        .draggable({
                            inertia: false,
                            modifiers: [],
                            listeners: {
                                start(event) {
                                    self.isDragging = true;
                                    event.target.classList.add('is-dragging');
                                },
                                move(event) {
                                    const f = self.fields[fieldIndex];
                                    if (!f) return;
                                    if (!f.centerX) {
                                        f.x = Math.round(f.x + event.dx / self.scale);
                                    }
                                    f.y = Math.round(f.y + event.dy / self.scale);
                                },
                                end(event) {
                                    event.target.classList.remove('is-dragging');
                                    self.schedulePreview();
                                    setTimeout(() => {
                                        self.isDragging = false;
                                    }, 200);
                                }
                            }
                        });
                },

                onKey(e) {
                    const step = e.shiftKey ? 10 : 1;

                    if (this.current) {
                        if (e.key === 'Delete' || e.key === 'Backspace') {
                            e.preventDefault();
                            this.remove(this.current);
                        } else if (e.key === 'ArrowLeft') {
                            e.preventDefault();
                            this.current.x_position -= step;
                            this.persist(this.current);
                        } else if (e.key === 'ArrowRight') {
                            e.preventDefault();
                            this.current.x_position += step;
                            this.persist(this.current);
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            this.current.y_position -= step;
                            this.persist(this.current);
                        } else if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            this.current.y_position += step;
                            this.persist(this.current);
                        }
                    }

                    else if (this.selectedFieldIndex !== null) {
                        const f = this.fields[this.selectedFieldIndex];
                        if (!f) return;
                        if (e.key === 'ArrowLeft' && !f.centerX) {
                            e.preventDefault();
                            f.x -= step;
                            this.schedulePreview();
                        } else if (e.key === 'ArrowRight' && !f.centerX) {
                            e.preventDefault();
                            f.x += step;
                            this.schedulePreview();
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            f.y -= step;
                            this.schedulePreview();
                        } else if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            f.y += step;
                            this.schedulePreview();
                        }
                    }

                    if ((e.ctrlKey || e.metaKey) && (e.key === '=' || e.key === '+')) {
                        e.preventDefault();
                        this.zoomIn();
                    }
                    if ((e.ctrlKey || e.metaKey) && e.key === '-') {
                        e.preventDefault();
                        this.zoomOut();
                    }
                    if ((e.ctrlKey || e.metaKey) && e.key === '0') {
                        e.preventDefault();
                        this.resetZoom();
                    }
                },

                toastMsg(msg) {
                    this.toast = msg;
                    setTimeout(() => this.toast = '', 2200);
                },
                recalcScale() {
                    nextTick(() => {
                        const canvas = this.$refs.canvas;
                        if (!canvas) return;
                        const W = this.bgW || this.template.width || 1600;
                        const H = this.bgH || this.template.height || 1200;
                        const parent = canvas.parentElement;
                        const sX = (parent.clientWidth - 64) / W;
                        const sY = (parent.clientHeight - 64) / H;
                        this.scale = Math.min(1, Math.max(0.2, Math.floor(Math.min(sX, sY) * 10) / 10));
                    });
                }
            },
            mounted() {
                this.bindAll();
                window.addEventListener('resize', this.recalcScale);
                window.addEventListener('keydown', this.onKey);
                this.recalcScale();
                this.schedulePreview();
            },
            beforeUnmount() {
                window.removeEventListener('resize', this.recalcScale);
                window.removeEventListener('keydown', this.onKey);
                if (this.clickTimeout) {
                    clearTimeout(this.clickTimeout);
                    this.clickTimeout = null;
                }
                if (this._previewDebounceTimer) clearTimeout(this._previewDebounceTimer);
                if (this._previewMaxWaitTimer) clearTimeout(this._previewMaxWaitTimer);
            }
        }).mount('#app');
    </script>
</body>

</html>
