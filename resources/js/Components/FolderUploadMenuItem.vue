<script setup>
import {MenuItem} from "@headlessui/vue";
import {emitter, FILE_UPLOAD_STARTED} from "@/event-bus.js";

// Methods
function onChange(ev){
    ev.preventDefault();
    ev.stopPropagation();
    emitter.emit(FILE_UPLOAD_STARTED, ev.target.files)
}

</script>


<template>
    <MenuItem v-slot="{ active }">
        <a href="#" class="text-gray-700 block px-4 py-2 text-sm relative hover:bg-gray-100">
            Upload Folder
            <input @change="onChange" type="file" class="absolute left-0 top-0 bottom-0 right-0 cursor-pointer opacity-0" multiple directory webkitdirectory>
        </a>
    </MenuItem>
</template>

