<script setup>
import { router } from '@inertiajs/vue3';
import FileIcon from '@/Components/FileIcon.vue';
import { watch, onUpdated, onMounted, ref, computed, reactive } from 'vue';
import { httpGet } from '../Helpers/http-helper.js';
import Checkbox from '@/Components/Checkbox.vue';
import RestoreFilesButton from '@/Components/RestoreFilesButton.vue';
import DeleteFilesButton from '@/Components/DeleteFilesButton.vue';
import { sharedWithMeFiles } from '@/Stores/store';
import FormProgress from '@/Components/FormProgress.vue';
import DownloadFilesButton from '@/Components/DownloadFilesButton.vue';
import Folder from '@/Components/Folder.vue';



const props = defineProps({
    files:{
        type: [Array, Object],
        required: true
    },
    folder:  Object,
    ancestors: [Array, Object],
})

sharedWithMeFiles.data= props.files.data;
sharedWithMeFiles.next= props.files.next_page_url;

const allSelected = ref(false);
const selected = ref({});
const loadMoreIntersect = ref(null);


// get the selected ids
const selectedIds = computed(() => Object.entries(selected.value).filter(a => a[1] === true)
                                                           .map(a => a[0]))
// individual checkbox to toggle select
const toggleSelect = (file)=>{
    // toggle true/false
    selected.value[file.id] = !selected.value[file.id];

    if(!selected.value[file.id]){
        // if current selected file.id is false
        // then allSelected must be false too to refelct the value of :checked prop in checkbox
        allSelected.value = false;
    }else{
        // loop to check if all is true
        // if all is true then allSelected must be true 
        let checked = true;
        for(const file of sharedWithMeFiles.data){
            if(!selected.value[file.id]){
                checked = false;
                break;
            }
        }
        allSelected.value = checked;
    }
}

const onToggleSelectAll = (data)=>{
    console.log(allSelected);
    data.forEach(e =>{
        selected.value[e.id] = allSelected.value;
    });
}


const loadMore = async()=>{
    if (sharedWithMeFiles.next === null) return ;
        
     await httpGet(sharedWithMeFiles.next)
        .then(res => {
            // if allSelected is true the loaded new files will be selected as well
            if(allSelected.value){
                onToggleSelectAll(res.data);
            }
            sharedWithMeFiles.data = [...sharedWithMeFiles.data, ...res.data]
            sharedWithMeFiles.next = res.next_page_url
            
        })
}

const openFolder = (file)=>{

if(!file.is_folder) return;
router.visit(route('file.sharedWithMe', {folder: file.path} ));
}


// reset selected after delete
const resetSelect = ()=>{
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
            <DownloadFilesButton :all="allSelected" :ids="selectedIds" class="mr-2" :sharedWithMe="true"/>
        </nav>

        <!-- table container -->
        <div class="overflow-y-auto max-h-[550px] pb-20 table-container rounded-md">    
            <table class="min-w-full">
                <thead class="bg-slate-200 border-b sticky top-0 z-10">
                    <tr>                  
                        <th v-if="sharedWithMeFiles.data.length" class="text-sm font-bold text-gray-700 px-6 py-4 text-left w-[30px] max-w-[30px] pr-0"> 
                            <Checkbox @change="onToggleSelectAll(sharedWithMeFiles.data)" v-model="allSelected" :checked="allSelected"/> </th>
                        <th class="text-sm font-bold text-gray-700 px-6 py-4 text-left">Name</th>
                        <th class="text-sm font-bold text-gray-700 px-6 py-4 text-left">Path</th>
                        <th class="text-sm font-bold text-gray-700 px-6 py-4 text-left">Shared By</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="file of sharedWithMeFiles.data" :key="file.id" @click="toggleSelect(file)"
                    class="border-b transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                    :class="{ 'bg-white': !selected[file.id], 'bg-blue-100' : selected[file.id] === true }"
                    >                 
                        <td class="px-6 pr-0 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <Checkbox v-model="selected[file.id]" :checked="selected[file.id] || allSelected"/>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-end">
                            <FileIcon :file="file"/>
                            {{ file.name }}
                        </td>
                        <td class="px-6 py-4 max-w-60 text-ellipsis overflow-hidden whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ file.path }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ file.sharedByEmail }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="!sharedWithMeFiles.data.length" class="py-8 text-center text-lg text-gray-400">
                No data
            </div>   
            
            <div ref="loadMoreIntersect"></div>

        </div>

    </section>
    
</template>