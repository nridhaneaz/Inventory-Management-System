<script setup>
import { ref } from "vue";

const props = defineProps({
    productImage: String,
}); 
const currentImage = props.productImage ? props.productImage : null;
const preview = ref(currentImage);
const emit = defineEmits(["image"]);

const imageSelected = (e) => {
    preview.value = URL.createObjectURL(e.target.files[0]);

    emit("image", e.target.files[0]);
};
</script>
<template>
    <div class="grid gap-3">
        <label
            for="image"
            class="group relative flex min-h-[220px] cursor-pointer items-center justify-center overflow-hidden rounded-3xl border-2 border-dashed border-amber-200 bg-amber-50/60 transition hover:border-amber-400 hover:bg-amber-50"
        >
            <img
                v-if="preview || productImage"
                :src="preview ?? productImage"
                class="absolute inset-0 h-full w-full object-cover object-center transition duration-300 group-hover:scale-[1.02]"
                alt="Product preview"
            />
            <div v-else class="flex flex-col items-center gap-2 p-6 text-center text-slate-500">
                <span class="material-icons text-4xl text-amber-500">add_a_photo</span>
                <p class="text-sm font-semibold text-slate-700">Upload product photo</p>
                <p class="text-xs text-slate-500">Use a clear image for easy identification at the counter.</p>
            </div>
            <div class="absolute bottom-3 left-3 rounded-full bg-slate-900/75 px-3 py-1 text-xs font-semibold text-white">
                Click to change image
            </div>
        </label>
        <input
            @input="imageSelected($event)"
            type="file"
            name="image"
            id="image"
            hidden
        />
    </div>
</template>
