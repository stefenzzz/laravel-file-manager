<script setup>
import Notification from '@/Components/Notification.vue';
import {emitter, FILE_UPLOAD_STARTED} from "@/event-bus.js";
import {onMounted, watch, ref} from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import FormProgress from '@/Components/FormProgress.vue';
import {showErrorDialog, showErrorNotification} from '@/event-bus.js'
import ErrorDialog from '@/Components/ErrorDialog.vue';
import { allFiles } from '@/Stores/store';
import FlashMessage from '@/Components/FlashMessage.vue';
import Header from '@/Components/Header.vue';
import Sidebar from '@/Components/Sidebar.vue';

const page = usePage();
const fileUploadForm = useForm({
    files: [],
    relative_paths:[],
    parent_id: null  
})


const uploadFiles = (files)=>{

    if(fileUploadForm.processing){
        showErrorNotification('Files still uploading.');
        return;
    };

    fileUploadForm.parent_id = page.props.folder?.id ?? page.props.default.folder?.id;
    fileUploadForm.files = files;
    
    fileUploadForm.relative_paths = [...files].map((f)=> f.webkitRelativePath);

    if(fileUploadForm.files.length > 100 || fileUploadForm.relative_paths.length > 100){
        
        showErrorDialog('Cant`t upload files more than 100 items');
        return;
    }

    fileUploadForm.post(route('file.store'), {
        preserveScroll: true ,
        onSuccess: (response) => {
            // showSuccessNotification(`${files.length} files have been uploaded`)
            
            allFiles.data = response.props.files.data;
            allFiles.next = response.props.files.links.next;
        },
        onError: errors => {
            let message = '';

            if (Object.keys(errors).length > 0) {
                message = errors[Object.keys(errors)[0]]
            } else {
                message = 'Error during file upload. Please try again later.'
            }
            showErrorDialog(message);

        },
        onFinish: () => {
            fileUploadForm.clearErrors()
            fileUploadForm.reset();
        }
    })
}

onMounted(()=>{
    emitter.on(FILE_UPLOAD_STARTED, uploadFiles);
});

//=========== Drag over files event ================

const dragOver = ref(false)

const handleDrop = (ev)=>{

    dragOver.value = false;

    const files = ev.dataTransfer.files
    if (!files.length) {
        return
    }

    uploadFiles(files)
}

const onDragOver = ()=>{   
    dragOver.value = true
}

const onDragLeave = (ev) => {
    if (ev.currentTarget.contains(ev.relatedTarget)) {
        // Prevent dragOver from resetting if the cursor is still within the container
        return;
    }
    dragOver.value = false;
};

</script>

<template>
<div class="min-h-screen bg-gray-100">
        <Header />
        <main :class="['', { 'flex': $page.props.auth.user} ]"
        @drop.prevent="handleDrop"
        @dragover.prevent="onDragOver"
        @dragleave.prevent="onDragLeave"
        >
            <Sidebar />
            <div class="max-w-full w-full relative">
                <!-- drag upload file indicator-->
                <template v-if="dragOver" >
                    <div class="absolute text-gray-500 text-lg text-center pt-20 bg-gray-100 w-full h-full z-10">
                        <div class="w-[70%] mx-auto p-6 border-dashed border-2 border-gray-400 ">
                            Drop files here to upload
                        </div>
                    </div>
                </template>               

                <!-- content section -->
                 <slot/>
            </div>
        </main>      
        <ErrorDialog />
        <FormProgress :form="fileUploadForm"/>
        <!-- <Notification /> -->
        <FlashMessage :message="page.props.flash.message"/>
</div>
</template>