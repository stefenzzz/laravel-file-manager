<script setup>
import { TrashIcon } from '@heroicons/vue/16/solid';
import {ref} from "vue";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import { allFiles } from '@/Stores/store'
import {showErrorDialog} from '@/event-bus.js'
import { MinusCircleIcon } from '@heroicons/vue/24/outline';
import { sharedByMeFiles } from '@/Stores/store';

// Uses
const page = usePage();
const form = useForm({
    all: null,
    ids: [],
    parent_id: null,
    email:null,
})
// refs
const processing = ref(false);


const emits = defineEmits(['onRemove'])
const props = defineProps({
    removeAll: {
        type: Boolean,
        required: false,
        default: false
    },
    removeIds: {
        type: Array,
        required: false
    }
})
const showDeleteDialog = ref(false)

const onRemoveClick = ()=> {

    if(!props.removeAll && !props.removeIds.length){
        showErrorDialog('Please select at least one file to remove');
        return;
    }
    showDeleteDialog.value = true;
}

const onRemoveCancel = ()=> showDeleteDialog.value = false;

const onRemoveConfirm = ()=>{
    
    form.all = props.removeAll;
    form.ids = props.removeIds;

    // turn on processing
    processing.value = true;

    form.delete(route('file.unshare'), {
        onSuccess:(response)=>{
            emits('onRemove');
            showDeleteDialog.value = false;
            sharedByMeFiles.data = response.props.files.data;
            sharedByMeFiles.links.next = response.props.files.links.next;

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
    <button @click="onRemoveClick"
        class="inline-flex items-end gap-x-1 px-4 py-2 text-sm font-semibold text-gray-900 bg-white border border-gray-200 rounded-lg
        hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700
        dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
        <MinusCircleIcon class="w-6 h-6"/>
        Unshare
    </button>

    <ConfirmationDialog :show="showDeleteDialog"
                        message="Are you sure you want to move the selected files to trash?"
                        @cancel="onRemoveCancel"
                        @confirm="!form.processing && onRemoveConfirm()"
                        :processing="processing">
    </ConfirmationDialog>
</template>