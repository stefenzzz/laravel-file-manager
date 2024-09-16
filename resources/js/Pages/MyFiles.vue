<script setup>
import { router } from '@inertiajs/vue3';
import FileIcon from '@/Components/FileIcon.vue';
import { watch, onUpdated, onMounted, ref, computed, reactive } from 'vue';
import { httpGet } from '../Helpers/http-helper.js';
import Checkbox from '@/Components/Checkbox.vue'
import { allFiles } from '@/Stores/store';  
import Folder from '@/Components/Folder.vue';
import TrashFilesButton from '@/Components/TrashFilesButton.vue';
import DownloadFilesButton from '@/Components/DownloadFilesButton.vue';
import ShareFileButton from '../Components/ShareFileButton.vue';
import { StarIcon as StarSolidIcon } from '@heroicons/vue/24/solid';
import { StarIcon  } from '@heroicons/vue/24/outline';
import {showErrorDialog} from '@/event-bus.js'
import { httpPost } from '@/Helpers/http-helper.js';
import { emitter, ON_SEARCH } from '@/event-bus.js';

const props = defineProps({
    files:{
        type: [Array, Object],
        required: true
    },
    folder:  Object,
    ancestors: [Array, Object],
})

// global state
allFiles.data = props.files.data,
allFiles.next = props.files.links.next


const allSelected = ref(false);
const selected = ref({});
const loadMoreIntersect = ref(null);
const onlyFavourites = ref(false);
const search = ref('');

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
        for(const file of allFiles.data){
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
    
    if (allFiles.next === null) return ;

    if(onlyFavourites.value){
        const url = new URL(allFiles.next, window.location.origin);
        const p = new URLSearchParams(url.search);
        p.set('favourites',1);
        url.search = p.toString();
        allFiles.next = url.toString();
    }
    
     await httpGet(allFiles.next)
        .then(res => {

            // if allSelected is true the loaded new files will be selected as well
            if(allSelected.value){
                onToggleSelectAll(res.data);
            }

            allFiles.data = [...allFiles.data, ...res.data]
            allFiles.next = res.links.next
            
        })
}

const openFolder = (file)=>{

    if(!file.is_folder) return;
    router.visit(route('myfiles', {folder: file.path} ));
}

// reset selected after delete
const resetSelect = ()=>{
    allSelected.value = false;
    selected.value = {};
}

const addRemoveFavourite = (file)=>{
    file.is_favourite = !file.is_favourite 
    
    httpPost(route('file.addToFavourites'), {id: file.id, is_favourite: file.is_favourite})
        .then( (e) =>{
            if(e.message){
                showErrorDialog(e.message);
            }
        })
        .catch( (er) =>{
            showErrorDialog(er.error.message);
        });
    
}

let params;

const showOnlyFavourites = ()=>{

    if(onlyFavourites.value){
        params.set('favourites',1);
    }else{
        params.delete('favourites');
    }

    router.get( route('myfiles') +'?'+ params.toString() );
}


onMounted( ()=>{

    params = new URLSearchParams(window.location.search);
    onlyFavourites.value = params.get('favourites') === '1';

    search.value = params.get('search');
    emitter.on(ON_SEARCH, (value)=>{
        search.value = value;
    });

    
    const observer = new IntersectionObserver((entries) => entries.forEach(entry => entry.isIntersecting && loadMore()), {
        rootMargin: '0px 0px -200px 0px'
    })

    observer.observe(loadMoreIntersect.value)

})



</script>

<template>
    <section class="pt-20 px-12 max-w-[1520px] w-full mx-auto">

        <div class="flex justify-between items-center my-2">      
            <Folder :ancestors="ancestors"/>
            <div class="flex items-center gap-x-2">
                <label class="flex items-center gap-x-1 cursor-pointer">
                    Only Favourites
                    <Checkbox @change="showOnlyFavourites()"  class="w-4 h-4" v-model="onlyFavourites" :checked="onlyFavourites"/>
                </label>
                <ShareFileButton :allSelected="allSelected" :selectedIds="selectedIds" />
                <DownloadFilesButton :all="allSelected" :ids="selectedIds"/>
                <TrashFilesButton :deleteAll="allSelected" :deleteIds="selectedIds" @delete="resetSelect" />  
            </div>
        </div>

        <!-- table container -->
        <div class="overflow-y-auto max-h-[550px] pb-20 table-container rounded-md">    
            <table class="min-w-full">
                <thead class="bg-slate-200 border-b sticky top-0 z-10">
                    <tr>                  
                        <th v-if="allFiles.data.length" class="text-sm  font-bold text-gray-700 px-6 py-4 text-left w-[30px] max-w-[30px] pr-0"> 
                            <Checkbox @change="onToggleSelectAll(allFiles.data)" v-model="allSelected" :checked="allSelected"/> </th>
                        <th class="text-sm  font-bold text-gray-700 px-6 py-4 text-left max-w-[30px] w-[30px] pr-0"></th>                  
                        <th class="text-sm font-bold text-gray-700 px-6 py-4 text-left">Name</th>
                        <th v-if="search" class="text-sm font-bold text-gray-700 px-6 py-4 text-left">Path</th>
                        <th class="text-sm font-bold text-gray-700 px-6 py-4 text-left">Owner</th>
                        <th class="text-sm font-bold text-gray-700 px-6 py-4 text-left">Last Modified</th>
                        <th class="text-sm font-bold text-gray-700 px-6 py-4 text-left">Size</th>                    
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="file of allFiles.data" :key="file.id" @click="toggleSelect(file)"
                    class=" border-b transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                    :class="{ 'bg-white': !selected[file.id], 'bg-blue-100' : selected[file.id] === true }"
                    >
                        <td class="px-6 pr-0 py-4 pb-[14px] whitespace-nowrap text-sm font-medium text-gray-900 align-bottom">
                            <Checkbox v-model=" selected[file.id] " :checked="selected[file.id] || allSelected"/>
                        </td>
                        <td @click.stop.prevent="addRemoveFavourite(file)" class="pl-4 pr-0 py-4 text-sm font-medium text-yellow-500">
                            <StarSolidIcon v-if="file.is_favourite" class="w-6 h-6"/>
                            <StarIcon v-else class="w-6 h-6"/>                      
                        </td>
                        <td class="px-6 py-4 text-ellipsis overflow-hidden whitespace-nowrap text-sm font-medium text-gray-900 flex items-end">
                            <FileIcon :file="file"/>
                            <span @click.stop="openFolder(file)" :class="{ 'hover:underline': file.is_folder }">{{ file.name }}</span>
                        </td>
                        <td v-if="search" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ file.path }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ file.owner }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ file.updated_at }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ file.is_folder ? '': file.size }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="!allFiles.data.length" class="py-8 text-center text-lg text-gray-400">
                No data
            </div>   
            
            <div ref="loadMoreIntersect"></div>

        </div>

    </section>
</template>