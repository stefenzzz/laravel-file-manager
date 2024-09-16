<script setup>
import {isAudio, isExcel, isImage, isPDF, isText, isVideo, isWord, isZip} from "@/Helpers/file-helper.js";


// Props & Emit
const {file} = defineProps({
    file: Object
})


</script>


<template>
    <span class="w-6 h-6 inline-flex items-center justify-center mr-2 align-middle">
        <template v-if="file.is_folder">  
            <img class="max-w-full" :src="`/images/icons/folder.png`">
        </template>
        <template v-else>
            <img class="max-w-full" v-if="isImage(file)" :src="`/images/icons/image.png`">
            <img class="max-w-full" v-else-if="isPDF(file)" :src="`/images/icons/pdf.png`">
            <img class="max-w-full" v-else-if="isAudio(file)" :src="`/images/icons/audio.png`">
            <img class="max-w-full" v-else-if="isVideo(file)" :src="`/images/icons/video.png`">
            <img class="max-w-full" v-else-if="isWord(file)" :src="`/images/icons/word.png`">
            <img class="max-w-full" v-else-if="isExcel(file)" :src="`/images/icons/excel.png`">
            <img class="max-w-full" v-else-if="isZip(file)" :src="`/images/icons/zip.png`">
            <img class="max-w-full" v-else-if="isText(file)" :src="`/images/icons/txt-file.png`">
            <img class="max-w-full" v-else :src="`/images/icons/attach-file.png`">
        </template>
    </span>

</template>

