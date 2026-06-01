@props([
    'accept' => 'image/*',
    'multiple' => false,
    'maxSize' => 5, // MB
    'maxFiles' => 10,
    'preview' => true,
])

@php
    $id = $attributes->get('id') ?? $attributes->get('name') ?? 'file-upload-' . uniqid();
@endphp

<div 
    x-data="{
        files: [],
        isDragging: false,
        maxSize: {{ $maxSize }} * 1024 * 1024,
        maxFiles: {{ $maxFiles }},
        
        handleDrop(event) {
            this.isDragging = false;
            const droppedFiles = Array.from(event.dataTransfer.files);
            this.addFiles(droppedFiles);
        },
        
        handleSelect(event) {
            const selectedFiles = Array.from(event.target.files);
            this.addFiles(selectedFiles);
            event.target.value = '';
        },
        
        addFiles(newFiles) {
            for (const file of newFiles) {
                if (this.files.length >= this.maxFiles) {
                    alert('Maximum ' + this.maxFiles + ' fichiers autorisés');
                    break;
                }
                
                if (file.size > this.maxSize) {
                    alert(file.name + ' est trop volumineux (max {{ $maxSize }}MB)');
                    continue;
                }
                
                if (!this.isValidType(file)) {
                    alert(file.name + ' n\'est pas un type de fichier accepté');
                    continue;
                }
                
                this.files.push({
                    file: file,
                    name: file.name,
                    size: this.formatSize(file.size),
                    preview: null,
                    uploading: false,
                    progress: 0
                });
                
                // Generate preview for images
                if (file.type.startsWith('image/')) {
                    this.generatePreview(this.files.length - 1);
                }
            }
            
            this.$dispatch('files-selected', { files: this.files });
        },
        
        isValidType(file) {
            const accept = '{{ $accept }}';
            if (accept === '*') return true;
            
            const types = accept.split(',').map(t => t.trim());
            return types.some(type => {
                if (type.endsWith('/*')) {
                    return file.type.startsWith(type.replace('/*', '/'));
                }
                return file.type === type || file.name.endsWith(type);
            });
        },
        
        generatePreview(index) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.files[index].preview = e.target.result;
            };
            reader.readAsDataURL(this.files[index].file);
        },
        
        removeFile(index) {
            this.files.splice(index, 1);
            this.$dispatch('files-selected', { files: this.files });
        },
        
        formatSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }
    }"
    {{ $attributes->merge(['class' => 'w-full']) }}
>
    {{-- Drop zone --}}
    <div
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop($event)"
        :class="isDragging ? 'border-[#F16A00] bg-[#FFF4EB]' : 'border-gray-200 hover:border-gray-300'"
        class="relative border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer"
        @click="$refs.fileInput.click()"
    >
        <input
            type="file"
            x-ref="fileInput"
            @change="handleSelect($event)"
            accept="{{ $accept }}"
            {{ $multiple ? 'multiple' : '' }}
            class="hidden"
            name="{{ $attributes->get('name') }}"
        >
        
        <div class="flex flex-col items-center">
            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-4" :class="isDragging ? 'bg-[#FFE7D1]' : ''">
                <svg class="w-6 h-6" :class="isDragging ? 'text-[#F16A00]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>
            
            <p class="text-sm text-gray-600 mb-1">
                <span class="font-medium text-[#CC5A00]">Cliquez pour télécharger</span>
                ou glissez-déposez
            </p>
            <p class="text-xs text-gray-500">
                {{ Str::upper(str_replace(['image/', '*'], ['', 'Images'], $accept)) }} jusqu'à {{ $maxSize }}MB
                @if($multiple)
                    (max {{ $maxFiles }} fichiers)
                @endif
            </p>
        </div>
    </div>
    
    {{-- Preview grid --}}
    @if($preview)
        <div x-show="files.length > 0" class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            <template x-for="(item, index) in files" :key="index">
                <div class="relative group">
                    {{-- Image preview --}}
                    <div class="aspect-square rounded-xl bg-gray-100 overflow-hidden">
                        <template x-if="item.preview">
                            <img loading="lazy" :src="item.preview" :alt="item.name" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!item.preview">
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </template>
                    </div>
                    
                    {{-- File info --}}
                    <div class="mt-1">
                        <p class="text-xs text-gray-600 truncate" x-text="item.name"></p>
                        <p class="text-xs text-gray-400" x-text="item.size"></p>
                    </div>
                    
                    {{-- Remove button --}}
                    <button
                        type="button"
                        @click.stop="removeFile(index)"
                        class="absolute top-2 right-2 w-6 h-6 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center hover:bg-red-600"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    
                    {{-- Upload progress --}}
                    <template x-if="item.uploading">
                        <div class="absolute inset-0 bg-black/50 rounded-xl flex items-center justify-center">
                            <div class="w-3/4">
                                <div class="h-1 bg-white/30 rounded-full overflow-hidden">
                                    <div class="h-full bg-white rounded-full transition-all" :style="'width: ' + item.progress + '%'"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    @endif
</div>
