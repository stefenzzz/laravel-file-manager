

<script setup>
// Imports
import TextInput from "@/Components/TextInput.vue";
import {router, useForm} from "@inertiajs/vue3";
import {onMounted, ref, reactive} from "vue";
import {emitter, ON_SEARCH} from "@/event-bus.js";

// Uses
let params = ''

// Refs
const search = ref('');


// Methods
function onSearch() {

    if(search.value){
        params.set('search', search.value);
    }else{
        params.delete('search');
    }
    router.get(route('myfiles') + '?' + params.toString());
    emitter.emit(ON_SEARCH, search.value);

}

// Hooks
onMounted(() => {
    params = new URLSearchParams(window.location.search)
    search.value = params.get('search') ?? '';
})

</script>

<template>
    <div class="w-[350px] flex items-center">
        <TextInput type="text"
                   class="block w-full mr-2"
                   v-model="search"
                   autocomplete
                   @keyup.enter.prevent="onSearch"
                   placeholder="Search for files and folders"/>
    </div>
</template>
