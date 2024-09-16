<script setup>
// Imports
import {useForm, usePage} from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { httpGet } from "@/Helpers/http-helper.js";
import {showErrorDialog} from '@/event-bus.js'
import { ArrowDownTrayIcon } from "@heroicons/vue/24/outline";
import { debounce } from "lodash";
import { ref } from "vue";

// Uses
const page = usePage();
const processing = ref(false);

// Props & Emit
const props = defineProps({
    all: {
        type: Boolean,
        required: false,
        default: false
    },
    ids: {
        type: Array,
        required: false
    },
    sharedWithMe: false,
})


const form = useForm({
        all: props.all ? 1 : 0,
        ids: props.ids,
        parent_id: page.props.folder?.id,
        sharedWithMe: props.sharedWithMe,
    });


function download() { 
    if (!props.all && !props.ids.length) {     
        showErrorDialog('Select a file to download');
        return;
    }  
    
    const params = new URLSearchParams();

    params.append('parent_id', page.props.folder?.id ?? page.props.default.folder.id);  
    
    if(props.all){
        params.append('all', props.all ? 1 : 0);
    }else{
        for (const id of props.ids) { 
            params.append('ids[]', id);
        }
    }
    
    // turn processing on
    processing.value = true;

    let url = route('file.download');

    //overwrite url if sharedWithMe is true
    if(props.sharedWithMe) url = route('file.downloadSharedWithMe');
   
    httpGet( url + '?' + params.toString() ).then( res =>{

        // turn processing off
        processing.value = false;

        if(res.message){
            showErrorDialog(res.message);
            return;
        }
        
        if(!res.url){
            return;
        }
        const a = document.createElement('a');
        a.download = res.filename;
        a.href= res.url;
        a.click();

    });  
}

const debouncedDownload = debounce(download, 1000); 

</script>


<template>
    <PrimaryButton @click="!processing && download()" :class="{ 'opacity-80' : processing }" :disabled="processing">
        <ArrowDownTrayIcon class="w-4 h-4"/>
        Download
    </PrimaryButton>
</template>

