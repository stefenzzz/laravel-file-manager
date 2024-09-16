<script setup>
import { router } from '@inertiajs/vue3';
import FileIcon from '@/Components/FileIcon.vue';
import { watch, onUpdated, onMounted, ref, computed, reactive } from 'vue';
import { httpGet } from '../Helpers/http-helper.js';
import Checkbox from '@/Components/Checkbox.vue';
import RestoreFilesButton from '@/Components/RestoreFilesButton.vue';
import DeleteFilesButton from '@/Components/DeleteFilesButton.vue';
import { trashFiles } from '@/Stores/store';
import FormProgress from '@/Components/FormProgress.vue';



const props = defineProps({
    files:{
        type: [Array, Object],
        required: true
    },
    folder:  Object,
    ancestors: [Array, Object],
})

trashFiles.data= props.files.data;
trashFiles.next= props.files.links.next;

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
        for(const file of trashFiles.data){
            if(!selected.value[file.id]){
                checked = false;
                break;
            }
        }
        allSelected.value = checked;
    }
}

const onToggleSelectAll = (data)=>{
    data.forEach(e =>{
        selected.value[e.id] = allSelected.value;
    });
}

const loadMore = async()=>{
    if (trashFiles.next === null) return ;
    
     await httpGet(trashFiles.next)
        .then(res => {

            // if allSelected is true the loaded new files will be selected as well
            if(allSelected.value){
                onToggleSelectAll(res.data);
            }
            trashFiles.data = [...trashFiles.data, ...res.data]
            trashFiles.next = res.links.next
            
        })
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
                <DeleteFilesButton :allSelected="allSelected" :selectedIds="selectedIds" @delete="resetSelect" />
                <RestoreFilesButton :allSelected="allSelected" :selectedIds="selectedIds" @restore="resetSelect"/>    
        </nav>

        <!-- table container -->
        <div class="overflow-y-auto max-h-[550px] pb-20 table-container rounded-md">    
            <table class="min-w-full">
                <thead class="bg-slate-200 border-b sticky top-0 z-10">
                    <tr>
                        
                        <th v-if="trashFiles.data.length"class="text-sm  font-bold text-gray-700 px-6 py-4 text-left w-[30px] max-w-[30px] pr-0"> 
                            <Checkbox @change="onToggleSelectAll(trashFiles.data)" v-model="allSelected" :checked="allSelected"/> </th>
                        <th class="text-sm  font-bold text-gray-700 px-6 py-4 text-left">Name</th>
                        <th class="text-sm  font-bold text-gray-700 px-6 py-4 text-left">Path</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="file of trashFiles.data" :key="file.id" @click="toggleSelect(file)"
                    class="border-b transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                    :class="{ 'bg-white': !selected[file.id], 'bg-blue-100' : selected[file.id] === true }"
                    >
                    
                        <td class="px-6 pr-0 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <Checkbox  v-model="selected[file.id]" :checked="selected[file.id] || allSelected"/>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-end">
                            <FileIcon :file="file"/>
                            {{ file.name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ file.path }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="!trashFiles.data.length" class="py-8 text-center text-lg text-gray-400">
                No data
            </div>   
            
            <div ref="loadMoreIntersect"></div>

        </div>

    </section>
    
</template>