<script setup>
import { TrashIcon } from '@heroicons/vue/16/solid';
import {ref} from "vue";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import { allFiles } from '@/Stores/store'
import {showErrorDialog, showSuccessNotification} from '@/event-bus.js';


// Uses
const page = usePage();
const form = useForm({
    all: null,
    ids: [],
    parent_id: null
})
// refs
const processing = ref(false);

const emits = defineEmits(['delete'])
const props = defineProps({
    deleteAll: {
        type: Boolean,
        required: false,
        default: false
    },
    deleteIds: {
        type: Array,
        required: false
    }
})
const showDeleteDialog = ref(false)

const onDeleteClick = ()=> {

    if(!props.deleteAll && !props.deleteIds.length){
        showErrorDialog('Please select at least one file to delete');
        return;
    }
    showDeleteDialog.value = true;
}

const onDeleteCancel = ()=> showDeleteDialog.value = false;

const onDeleteConfirm = ()=>{

    form.all = props.deleteAll;
    form.parent_id = page.props.folder.id;
    form.ids = props.deleteIds;

    // turn on processing
    processing.value = true;

    form.delete(route('file.trash'),{
        preserveScroll: true,
        onSuccess:(response)=>{

            showDeleteDialog.value = false;
            emits('delete');           
            showSuccessNotification('Selected file(s) have been moved to Trash.');
            allFiles.data = response.props.files.data;
            allFiles.next = response.props.files.links.next;
            
        },
        onFinish:()=>{
            form.clearErrors();
            form.reset();
            // turn off processing
            processing.value = false;
        }

    });
}


</script>
<template>
    <button @click="onDeleteClick"
        class="inline-flex gap-x-[1px] px-4 py-2 text-sm font-semibold text-gray-900 bg-white border border-gray-200 rounded-lg
        hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700
        dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
        <TrashIcon class="w-4 h-4" />
        Trash
    </button>

    <ConfirmationDialog :show="showDeleteDialog"
                        message="Are you sure you want to move the selected files to trash?"
                        @cancel="!form.processing && onDeleteCancel()"
                        @confirm="!form.processing && onDeleteConfirm()"
                        :processing="processing">
    </ConfirmationDialog>
</template>