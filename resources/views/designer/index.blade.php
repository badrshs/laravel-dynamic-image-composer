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
        .certificate-canvas {
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
                    <span class="text-sm text-gray-500">@{{ tpl.width }} × @{{ tpl.height }}px</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="preview" class="px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                    <i class="fa-regular fa-eye mr-1"></i> Preview
                </button>
                <button @click="generate" class="px-3 py-2 rounded bg-green-600 text-white hover:bg-green-700">
                    <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Generate
                </button>
            </div>
        </div>

        <!-- Main -->
        <div class="flex-1 grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-0">
            <!-- Canvas area -->
            <div class="p-4 sm:p-8 overflow-auto scrollbars">
                <div ref="canvas" class="certificate-canvas drop-zone mx-auto" :style="canvasStyle"
                    @dragover="onDragOver" @dragleave="e => e.currentTarget.classList.remove('drag-over')"
                    @drop="onDrop">

                    <!-- Background -->
                    <img v-if="tpl.background_image" :src="backgroundImageUrl" @load="onBgLoaded" alt="Background"
                        class="bg" />

                    <!-- Elements (images) -->
                    <div v-for="el in elements" :key="'el-' + el.id"
                        :class="['template-element', { 'is-selected': el.id === selectedId }]" :style="elementStyle(el)"
                        @mousedown="handleElementMouseDown(el.id, $event)"
                        @touchstart="handleElementMouseDown(el.id, $event)">
                        <img v-if="el.image_path" :src="getImageUrl(el.image_path)" :alt="el.name" />
                        <div v-if="el.id === selectedId" class="resize-handle"></div>
                    </div>

                    <!-- Fields (text placeholders) -->
                    <div v-for="(field, index) in fields" :key="'field-' + index"
                        :class="['field-marker', { 'is-selected': index === selectedFieldIndex }]"
                        :style="fieldMarkerStyle(field)" @mousedown="handleFieldMouseDown(index, $event)"
                        @touchstart="handleFieldMouseDown(index, $event)">
                        <span class="dot"></span>@{{ field.label }}
                    </div>
                </div>

                <p class="mt-4 text-center text-gray-500 text-sm">
                    Drag elements and field markers on the canvas. Use the sidebar to adjust properties. <br />
                    Click <strong>Preview</strong> to see a live preview, or <strong>Generate</strong> to save the final
                    composition.
                </p>
            </div>

            <!-- Sidebar -->
            <aside class="bg-white border-l border-gray-200 p-4 sm:p-6 space-y-6">

                <!-- Live Preview -->
                <section>
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Live Preview</h3>
                    <div class="relative bg-gray-50 border border-gray-200 rounded overflow-hidden"
                        style="aspect-ratio: 3 / 4;">
                        <img v-if="previewImage" :src="previewImage" alt="Preview"
                            class="w-full h-full object-contain" />
                        <div v-else class="absolute inset-0 flex items-center justify-center">
                            <p class="text-gray-400 text-sm">Click Preview to see result</p>
                        </div>
                    </div>
                    <button @click="preview"
                        class="mt-2 w-full px-3 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        <i class="fa-solid fa-arrows-rotate mr-1"></i>
                        Refresh Preview
                    </button>
                </section>

                <!-- Elements list -->
                <section>
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Elements (@{{ elements.length }})</h3>
                    <div class="space-y-2">
                        <div v-for="el in elements" :key="'list-' + el.id" @click="select(el.id)"
                            :class="['flex items-center gap-3 p-2 rounded border-2 cursor-pointer', el.id === selectedId ?
                                'border-blue-500 bg-blue-50' : 'border-gray-200'
                            ]">
                            <img v-if="el.image_path" :src="getImageUrl(el.image_path)" :alt="el.name"
                                class="w-10 h-10 object-contain rounded" />
                            <span class="flex-1 text-sm truncate">@{{ el.name }}</span>
                            <button @click.stop="removeElement(el.id)" class="text-red-600 hover:text-red-800">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Properties of selected element -->
                <section v-if="current" class="space-y-3">
                    <h3 class="text-sm font-semibold text-gray-900">Properties: @{{ current.name }}</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">X</label>
                            <input v-model.number="current.x_position" type="number"
                                class="w-full px-2 py-1 text-sm border rounded" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Y</label>
                            <input v-model.number="current.y_position" type="number"
                                class="w-full px-2 py-1 text-sm border rounded" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Width</label>
                            <input v-model.number="current.width" type="number"
                                class="w-full px-2 py-1 text-sm border rounded" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Height</label>
                            <input v-model.number="current.height" type="number"
                                class="w-full px-2 py-1 text-sm border rounded" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Opacity</label>
                            <input v-model.number="current.opacity" type="number" min="0" max="1"
                                step="0.1" class="w-full px-2 py-1 text-sm border rounded" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Z-index</label>
                            <input v-model.number="current.z_index" type="number"
                                class="w-full px-2 py-1 text-sm border rounded" />
                        </div>
                    </div>
                </section>

                <!-- Fields (placeholders) -->
                <section class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Text Fields</h3>
                        <button @click="addField" class="text-sm text-blue-600 hover:text-blue-800">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </div>
                    <div v-for="(field, index) in fields" :key="'field-editor-' + index" @click="selectField(index)"
                        :class="['p-3 border-2 rounded cursor-pointer', index === selectedFieldIndex ?
                            'border-blue-500 bg-blue-50' : 'border-gray-200'
                        ]">
                        <div class="flex items-center justify-between mb-2">
                            <input v-model="field.label" placeholder="Field label"
                                class="flex-1 text-sm font-medium border-b border-transparent hover:border-gray-300 focus:border-blue-500 outline-none px-1 bg-transparent" />
                            <button @click.stop="removeField(index)" class="text-red-600 hover:text-red-800">
                                <i class="fa-solid fa-trash text-xs"></i>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <label class="block text-gray-600">X</label>
                                <input v-model.number="field.x" type="number"
                                    class="w-full px-2 py-1 border rounded" />
                            </div>
                            <div>
                                <label class="block text-gray-600">Y</label>
                                <input v-model.number="field.y" type="number"
                                    class="w-full px-2 py-1 border rounded" />
                            </div>
                            <div>
                                <label class="block text-gray-600">Font Size</label>
                                <input v-model.number="field.fontSize" type="number"
                                    class="w-full px-2 py-1 border rounded" />
                            </div>
                            <div>
                                <label class="block text-gray-600">Color</label>
                                <select v-model="field.color" class="w-full px-2 py-1 border rounded">
                                    <option value="black">Black</option>
                                    <option value="white">White</option>
                                    <option value="gold">Gold</option>
                                    <option value="brown">Brown</option>
                                    <option value="gray">Gray</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-gray-600">Font</label>
                                <select v-model="field.font" class="w-full px-2 py-1 border rounded">
                                    <option value="default">Default</option>
                                    <option value="monotype">Monotype</option>
                                    <option value="libre">Libre</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-gray-600">Alignment</label>
                                <select v-model="field.alignment" class="w-full px-2 py-1 border rounded">
                                    <option value="left">Left</option>
                                    <option value="center">Center</option>
                                    <option value="right">Right</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Upload -->
                <section>
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Upload Element</h3>
                    <input type="file" accept="image/png,image/jpeg" class="hidden" @change="onPickFile" />
                    <div v-if="upload.file" class="mb-3 text-sm text-gray-700">
                        Selected: <strong>@{{ upload.file.name }}</strong>
                    </div>
                    <div class="flex gap-2">
                        <button @click="openUpload"
                            class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50">
                            <i class="fa-solid fa-image mr-1"></i> Choose Image
                        </button>
                        <button v-if="upload.file" @click="uploadElement" :disabled="upload.loading"
                            class="flex-1 px-3 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                            <i class="fa-solid fa-upload mr-1"></i> Upload
                        </button>
                    </div>
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
                    // Template data
                    tpl: @json($tplPayload),
                    elements: @json($elsPayload),

                    // Selection
                    selectedId: null,
                    selectedFieldIndex: null,

                    // Canvas scale
                    scale: 1,
                    computedWidth: 600,

                    // Fields (text placeholders)
                    fields: @json($template->field_configuration['fields'] ?? []),

                    // Upload
                    upload: {
                        file: null,
                        loading: false
                    },

                    // Preview
                    previewImage: null,
                    previewTimer: null,

                    // Toast
                    toast: null
                };
            },
            computed: {
                current() {
                    return this.elements.find(e => e.id === this.selectedId) || null;
                },
                canvasStyle() {
                    const w = this.computedWidth;
                    const h = (this.tpl.height / this.tpl.width) * w;
                    return {
                        width: w + 'px',
                        height: h + 'px'
                    };
                },
                backgroundImageUrl() {
                    return '/storage/' + this.tpl.background_image;
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
                        opacity: el.opacity || 1,
                        zIndex: el.z_index || 1
                    };
                },
                select(id) {
                    this.selectedId = id;
                    this.selectedFieldIndex = null; // Clear field selection when selecting element
                },
                selectField(index) {
                    this.selectedFieldIndex = index;
                    this.selectedId = null; // Clear element selection when selecting field
                },
                clearSelections() {
                    this.selectedId = null;
                    this.selectedFieldIndex = null;
                },
                handleElementMouseDown(id, event) {
                    if (event.target.classList.contains('resize-handle')) return;
                    this.select(id);
                    event.stopPropagation();
                },
                handleFieldMouseDown(index, event) {
                    this.selectField(index);
                    event.stopPropagation();
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
                    const img = e.target;
                    this.recalcScale();
                },

                /* --------- DnD --------- */
                onDragOver(e) {
                    e.preventDefault();
                    e.currentTarget.classList.add('drag-over');
                },

                onDrop(e) {
                    e.preventDefault();
                    e.currentTarget.classList.remove('drag-over');
                    // Handle file drop if needed
                },

                /* --------- fields --------- */
                addField() {
                    this.fields.push({
                        label: 'Field ' + (this.fields.length + 1),
                        x: 100,
                        y: 100,
                        fontSize: 60,
                        color: 'black',
                        font: 'default',
                        alignment: 'left'
                    });
                },
                removeField(index) {
                    if (confirm('Remove this field?')) {
                        this.fields.splice(index, 1);
                        this.selectedFieldIndex = null;
                    }
                },
                fieldMarkerStyle(field) {
                    return {
                        left: (field.x * this.scale) + 'px',
                        top: (field.y * this.scale) + 'px'
                    };
                },

                /* --------- helpers --------- */
                getImageUrl(path) {
                    return path.startsWith('http') ? path : '/storage/' + path;
                },

                showToast(msg) {
                    this.toast = msg;
                    clearTimeout(this.toastTimer);
                    this.toastTimer = setTimeout(() => this.toast = null, 2500);
                },

                recalcScale() {
                    const canvas = this.$refs.canvas;
                    if (!canvas) return;
                    const rect = canvas.getBoundingClientRect();
                    this.computedWidth = rect.width;
                    this.scale = rect.width / this.tpl.width;
                },

                /* --------- API calls --------- */
                async uploadElement() {
                    if (!this.upload.file) return;
                    const form = new FormData();
                    form.append('name', this.upload.file.name);
                    form.append('image', this.upload.file);
                    form.append('x_position', 100);
                    form.append('y_position', 100);
                    form.append('width', 200);
                    form.append('height', 200);

                    this.upload.loading = true;
                    try {
                        const resp = await fetch(`/image-template/${this.tpl.id}/elements`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            },
                            body: form
                        });
                        const data = await resp.json();
                        if (data.element) {
                            this.elements.push(data.element);
                            this.showToast('Element uploaded!');
                            this.upload.file = null;
                        }
                    } catch (err) {
                        console.error(err);
                        this.showToast('Upload failed');
                    } finally {
                        this.upload.loading = false;
                    }
                },

                async removeElement(id) {
                    if (!confirm('Delete this element?')) return;
                    try {
                        await fetch(`/image-template/${this.tpl.id}/elements/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            }
                        });
                        this.elements = this.elements.filter(e => e.id !== id);
                        if (this.selectedId === id) this.selectedId = null;
                        this.showToast('Element removed');
                    } catch (err) {
                        console.error(err);
                        this.showToast('Delete failed');
                    }
                },

                async saveConfiguration() {
                    try {
                        await fetch(`/image-template/${this.tpl.id}/save-configuration`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                elements: this.elements,
                                fields: this.fields
                            })
                        });
                        this.showToast('Configuration saved');
                    } catch (err) {
                        console.error(err);
                        this.showToast('Save failed');
                    }
                },

                schedulePreview() {
                    clearTimeout(this.previewTimer);
                    this.previewTimer = setTimeout(() => this.preview(), 1000);
                },

                async preview() {
                    await this.saveConfiguration();
                    try {
                        const url = `/image-template/${this.tpl.id}/preview?t=${Date.now()}`;
                        this.previewImage = url;
                    } catch (err) {
                        console.error(err);
                        this.showToast('Preview failed');
                    }
                },

                async generate() {
                    await this.saveConfiguration();
                    try {
                        const resp = await fetch(`/image-template/${this.tpl.id}/generate`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            }
                        });
                        const data = await resp.json();
                        if (data.success) {
                            this.showToast('Generated! Saved to: ' + data.filename);
                            window.open(data.url, '_blank');
                        }
                    } catch (err) {
                        console.error(err);
                        this.showToast('Generation failed');
                    }
                },

                /* --------- interact.js --------- */
                initInteract() {
                    const vm = this;

                    // Elements dragging and resizing
                    interact('.template-element').draggable({
                        onstart(event) {
                            event.target.classList.add('is-dragging');
                        },
                        onmove(event) {
                            const el = vm.elements.find(e => e.id === vm.selectedId);
                            if (!el) return;
                            el.x_position += event.dx / vm.scale;
                            el.y_position += event.dy / vm.scale;
                        },
                        onend(event) {
                            event.target.classList.remove('is-dragging');
                            vm.schedulePreview();
                        }
                    }).resizable({
                        edges: {
                            bottom: '.resize-handle',
                            right: '.resize-handle'
                        },
                        onmove(event) {
                            const el = vm.elements.find(e => e.id === vm.selectedId);
                            if (!el) return;
                            el.width += event.deltaRect.width / vm.scale;
                            el.height += event.deltaRect.height / vm.scale;
                        },
                        onend() {
                            vm.schedulePreview();
                        }
                    });

                    // Field markers dragging
                    interact('.field-marker').draggable({
                        onstart(event) {
                            event.target.classList.add('is-dragging');
                        },
                        onmove(event) {
                            const field = vm.fields[vm.selectedFieldIndex];
                            if (!field) return;
                            field.x += event.dx / vm.scale;
                            field.y += event.dy / vm.scale;
                        },
                        onend(event) {
                            event.target.classList.remove('is-dragging');
                            vm.schedulePreview();
                        }
                    });
                }
            },
            mounted() {
                window.addEventListener('resize', () => this.recalcScale());
                this.recalcScale();
                this.initInteract();
                this.schedulePreview();
            },
            beforeUnmount() {
                clearTimeout(this.previewTimer);
                clearTimeout(this.toastTimer);
            }
        }).mount('#app');
    </script>
</body>

</html>
