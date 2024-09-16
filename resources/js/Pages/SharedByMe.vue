<script setup>
import { router } from '@inertiajs/vue3';
import FileIcon from '@/Components/FileIcon.vue';
import { watch, onUpdated, onMounted, ref, computed, reactive } from 'vue';
import { httpGet } from '../Helpers/http-helper.js';
import Checkbox from '@/Components/Checkbox.vue';
import RestoreFilesButton from '@/Components/RestoreFilesButton.vue';
import DeleteFilesButton from '@/Components/DeleteFilesButton.vue';
import { sharedByMeFiles } from '@/Stores/store';
import FormProgress from '@/Components/FormProgress.vue';
import DownloadFilesButton from '@/Components/DownloadFilesButton.vue';
import UnshareButton from '@/Components/UnshareButton.vue';



const props = defineProps({
    files:{
        type: [Array, Object],
        required: true
    },
    folder:  Object,
    ancestors: [Array, Object],
})

sharedByMeFiles.data= props.files.data;
sharedByMeFiles.next= props.files.next_page_url;

const allSelected = ref(false);
const selected = ref({});
const loadMoreIntersect = ref(null);


// get the selected ids
const selectedIds = computed(() => Object.entries(selected.value).filter(a => a[1] === true)
                                                           .map(a => a[0]))
// individual checkbox to toggle select
const toggleSelect = (file)=>{
    // toggle true/false
    selected.value[file.fileShared_id] = !selected.value[file.fileShared_id];

    if(!selected.value[file.fileShared_id]){
        // if current selected file.fileShared_id is false
        // then allSelected must be false too to refelct the value of :checked prop in checkbox
        allSelected.value = false;
    }else{
        // loop to check if all is true
        // if all is true then allSelected must be true 
        let checked = true;
        for(const file of sharedByMeFiles.data){
            if(!selected.value[file.fileShared_id]){
                checked = false;
                break;
            }
        }
        allSelected.value = checked;
    }
}

const onToggleSelectAll = (data)=>{
    data.forEach(e =>{
        selected.value[e.fileShared_id] = allSelected.value;
    });
}


const loadMore = async()=>{
    if (sharedByMeFiles.next === null) return ;
        
     await httpGet(sharedByMeFiles.next)
        .then(res => {
            // if allSelected is true the loaded new files will be selected as well
            if(allSelected.value){
                onToggleSelectAll(res.data);
            }
            sharedByMeFiles.data = [...sharedByMeFiles.data, ...res.data]
            sharedByMeFiles.next = res.next_page_url
            
        })
}

// reset selected after delete
const resetSelect = ()=>{
    console.log('remove selected', allSelected.value,selected.value);
    allSelected.value = false;
    selected.value = {};
}



onMounted( ()=>{
    
    const observer = new IntersectionObserver((entries) => entries.forEach(entry => entry.isIntersecting && loadMore()), {
        rootMargin: '0px 0px -200px 0px'
    })

    observer.observe(loadMoreIntersect.value)

})

</script>

<template>
    <section class="pt-20 px-12 max-w-[1520px] w-full mx-auto">

        <nav class="flex items-center justify-end p-1 mb-3">
            <UnshareButton :removeAll="allSelected" :removeIds="selectedIds" @onRemove="resetSelect()"/>            
        </nav>

        <!-- table container -->
        <div class="overflow-y-auto max-h-[550px] pb-20 table-container rounded-md">    
            <table class="min-w-full">
                <thead class="bg-slate-200 border-b sticky top-0 z-10">
                    <tr>
                        
                        <th v-if="sharedByMeFiles.data.length" class="text-sm font-bold text-gray-700 px-6 py-4 text-left w-[30px] max-w-[30px] pr-0"> 
                            <Checkbox @change="onToggleSelectAll(sharedByMeFiles.data)" v-model="allSelected" :checked="allSelected"/> </th>
                        <th class="text-sm font-bold text-gray-700 px-6 py-4 text-left">Name</th>
                        <th class="text-sm font-bold text-gray-700 px-6 py-4 text-left">Path</th>
                        <th class="text-sm font-bold text-gray-700 px-6 py-4 text-left">Shared To</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="file of sharedByMeFiles.data" :key="file.id" @click="toggleSelect(file)"
                    class="border-b transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                    :class="{ 'bg-white': !selected[file.fileShared_id], 'bg-blue-100' : selected[file.fileShared_id] === true }"
                    >
                    
                        <td class="px-6 pr-0 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <Checkbox  v-model="selected[file.fileShared_id]" :checked="selected[file.fileShared_id] || allSelected"/>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-end">
                            <FileIcon :file="file"/>
                            {{ file.name }}
                        </td>
                        <td class="px-6 py-4 max-w-60 text-ellipsis overflow-hidden whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ file.path }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ file.sharedToEmail }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="!sharedByMeFiles.data.length" class="py-8 text-center text-lg text-gray-400">
                No data
            </div>   
            
            <div ref="loadMoreIntersect"></div>

        </div>

    </section>
    
</template>