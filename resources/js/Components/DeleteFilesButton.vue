
<script setup>
// Imports
import {ref} from "vue";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import {showErrorDialog, showSuccessNotification} from "@/event-bus.js";
import { TrashIcon } from '@heroicons/vue/16/solid';
import { trashFiles } from '@/Stores/store'

// Uses
const page = usePage();
const form = useForm({
    all: null,
    ids: [],
    parent_id: null
})


// Refs

const showConfirmationDialog = ref(false)
const processing = ref(false);

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
const emit = defineEmits(['delete'])

// Methods
function onClick() {
    if (!props.allSelected && !props.selectedIds.length) {
        showErrorDialog('Please select at least one file to delete')
        return
    }
    showConfirmationDialog.value = true;
}

function onCancel() {
    showConfirmationDialog.value = false;
}

function onConfirm() {
    if (props.allSelected) {
        form.all = true
        form.ids = [];
    } else {
        form.ids = props.selectedIds
    }
    
    // turn on processing
    processing.value = true;

    form.delete(route('file.delete'), {

        onSuccess: (response) => {
            
            showConfirmationDialog.value = false
            emit('delete');         
            showSuccessNotification('Selected file(s) have been deleted permanently.');
            trashFiles.data= response.props.files.data;
            trashFiles.next= response.props.files.links.next;
        },
        onFinish: () => {
            form.clearErrors()
            form.reset();
            // turn off processing
            processing.value = false;
        }
    })

}

</script>

<template>
    <button @click="onClick"
            class="inline-flex gap-x-1 px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg
            hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600
            dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white mr-3">
        <TrashIcon class="w-4 h-4" />
        Delete Permanently
    </button>

    <ConfirmationDialog :show="showConfirmationDialog"
                        message="Are you sure you want to delete selected files?"
                        @cancel="onCancel"
                        @confirm="!form.processing && onConfirm()"
                        :processing="processing">

    </ConfirmationDialog>
</template>
