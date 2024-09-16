<script setup>
// Props & Emit
const props = defineProps({
    form: Object
})


</script>


<template>
    <div v-if="form.progress"
         class="fixed  right-8 bottom-4 bg-white shadow-lg rounded flex flex-col w-[240px] p-2">
        <div class="text-xs mb-2">Uploading {{ form.files.length }} files</div>
        <div class="h-3 bg-gray-100 rounded-md">
            <div class="h-full bg-blue-500 rounded-md transition-all" :style="{width: form.progress.percentage+'%'}">
            </div>
        </div>
    </div>
</template>

