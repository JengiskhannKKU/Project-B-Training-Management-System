<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { X, ZoomIn, ZoomOut, Check, Move } from 'lucide-vue-next';

const props = defineProps({
    show: Boolean,
    imageFile: File,
});

const emit = defineEmits(['close', 'confirm']);

const canvasRef = ref(null);
const scale = ref(1);
const position = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const startPos = ref({ x: 0, y: 0 });
const imageObj = ref(null);

const CROP_SIZE = 300; // Output size
const DISPLAY_SIZE = 300; // Canvas display size (square)

// Load image when file changes
watch(() => props.imageFile, (file) => {
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                imageObj.value = img;
                resetState();
                draw();
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});

const resetState = () => {
    scale.value = 1;
    position.value = { x: 0, y: 0 };
    if (imageObj.value) {
        // Fit image to canvas initially
        const ratio = Math.max(DISPLAY_SIZE / imageObj.value.width, DISPLAY_SIZE / imageObj.value.height);
        scale.value = ratio;
        // Center
        position.value.x = (DISPLAY_SIZE - imageObj.value.width * scale.value) / 2;
        position.value.y = (DISPLAY_SIZE - imageObj.value.height * scale.value) / 2;
    }
};

const draw = () => {
    if (!canvasRef.value || !imageObj.value) return;
    const ctx = canvasRef.value.getContext('2d');
    ctx.clearRect(0, 0, DISPLAY_SIZE, DISPLAY_SIZE);
    
    // Draw image with transform
    ctx.save();
    ctx.translate(position.value.x, position.value.y);
    ctx.scale(scale.value, scale.value);
    ctx.drawImage(imageObj.value, 0, 0);
    ctx.restore();
    
    // Overlay (optional, circular mask visual guide)
    ctx.globalCompositeOperation = 'destination-in';
    ctx.beginPath();
    ctx.arc(DISPLAY_SIZE / 2, DISPLAY_SIZE / 2, DISPLAY_SIZE / 2, 0, Math.PI * 2);
    ctx.fill();
    ctx.globalCompositeOperation = 'source-over';
};

watch([scale, position], () => {
    requestAnimationFrame(draw);
}, { deep: true });

// Mouse Events
const onMouseDown = (e) => {
    isDragging.value = true;
    startPos.value = { x: e.clientX - position.value.x, y: e.clientY - position.value.y };
};

const onMouseMove = (e) => {
    if (!isDragging.value) return;
    position.value.x = e.clientX - startPos.value.x;
    position.value.y = e.clientY - startPos.value.y;
};

const onMouseUp = () => {
    isDragging.value = false;
};

// Zoom
const zoom = (delta) => {
    scale.value = Math.max(0.1, scale.value + delta);
};

const confirmCrop = () => {
    if (!imageObj.value) return;
    
    // Create off-screen canvas for high-res crop
    const offCanvas = document.createElement('canvas');
    offCanvas.width = CROP_SIZE;
    offCanvas.height = CROP_SIZE;
    const ctx = offCanvas.getContext('2d');
    
    // Calculate mapping from display to crop size
    // We drew on DISPLAY_SIZE canvas. We want to capture exactly that view into CROP_SIZE.
    // Since they are same size here (300), it's 1:1 map of current view.
    
    // Draw background (white/transparent)
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, CROP_SIZE, CROP_SIZE);
    
    ctx.save();
    // Apply same transforms
    ctx.translate(position.value.x, position.value.y);
    ctx.scale(scale.value, scale.value);
    ctx.drawImage(imageObj.value, 0, 0);
    ctx.restore();
    
    offCanvas.toBlob((blob) => {
        emit('confirm', blob);
    }, 'image/jpeg', 0.9);
};

</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-900">Adjust Avatar</h3>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                    <X class="w-5 h-5" />
                </button>
            </div>
            
            <div class="p-6 flex flex-col items-center gap-4 bg-gray-50">
                <div 
                    class="relative w-[300px] h-[300px] bg-gray-200 rounded-full overflow-hidden shadow-inner cursor-move border-4 border-white"
                    @mousedown="onMouseDown"
                    @mousemove="onMouseMove"
                    @mouseup="onMouseUp"
                    @mouseleave="onMouseUp"
                >
                    <canvas 
                        ref="canvasRef" 
                        width="300" 
                        height="300" 
                        class="w-full h-full"
                    ></canvas>
                    
                    <!-- Helper Text -->
                    <div v-if="!imageObj" class="absolute inset-0 flex items-center justify-center text-gray-400">
                        Loading...
                    </div>
                </div>
                
                <p class="text-xs text-gray-500 flex items-center">
                    <Move class="w-3 h-3 mr-1" /> Drag to position
                </p>

                <!-- Controls -->
                <div class="flex items-center gap-4 w-full px-8">
                    <button @click="zoom(-0.1)" class="p-2 rounded-full hover:bg-gray-200 text-gray-600"><ZoomOut class="w-5 h-5" /></button>
                    <input 
                        type="range" 
                        min="0.1" 
                        max="3" 
                        step="0.05" 
                        v-model.number="scale" 
                        class="flex-1 h-2 bg-gray-300 rounded-lg appearance-none cursor-pointer accent-teal-600"
                    />
                    <button @click="zoom(0.1)" class="p-2 rounded-full hover:bg-gray-200 text-gray-600"><ZoomIn class="w-5 h-5" /></button>
                </div>
            </div>
            
            <div class="p-4 border-t border-gray-100 flex justify-end gap-3 bg-white">
                <button 
                    @click="$emit('close')" 
                    class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl font-medium text-sm transition-colors"
                >
                    Cancel
                </button>
                <button 
                    @click="confirmCrop" 
                    class="px-6 py-2 bg-teal-600 text-white rounded-xl font-medium text-sm hover:bg-teal-700 shadow-lg shadow-teal-500/30 flex items-center transition-all"
                >
                    <Check class="w-4 h-4 mr-2" />
                    Save Avatar
                </button>
            </div>
        </div>
    </div>
</template>
