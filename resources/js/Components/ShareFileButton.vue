<script setup>
// Imports
import {ref} from "vue";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import {showErrorDialog, showSuccessNotification} from "@/event-bus.js";
import { ShareIcon } from "@heroicons/vue/24/solid";
import ShareFilesModal from "@/Components/ShareFilesModal.vue";

// Uses
const page = usePage();
const form = useForm({
    all: null,
    ids: [],
    parent_id: null
})


// Refs
const showEmailsModal = ref(false)

// Props & Emit

const props = defineProps({
    allSelected: {
        type: Boolean,
        required: false,
        default: false
    },
    selectedIds: {
        type: Array,
        required: false
    }
})
const emit = defineEmits(['restore'])

// Methods

function onClick() {
    if (!props.allSelected && !props.selectedIds.length) {
        showErrorDialog('Please select at least one file to share.');
        return;
    }
    showEmailsModal.value = true;
}

</script>


<template>
    <button @click="onClick"
            class="inline-flex gap-x-1 items-center px-4 py-2 text-sm font-semibold text-gray-900 bg-white border border-gray-200 rounded-lg
             hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700
              dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
        <ShareIcon class="w-4 h-4"/>
        Share
    </button>

    <ShareFilesModal v-model="showEmailsModal" :allSelected="allSelected" :selectedIds="selectedIds" />
</template>

