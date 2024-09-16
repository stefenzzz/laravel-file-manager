<script setup>
import {onMounted, ref, computed, watch} from "vue";
import {emitter, SHOW_NOTIFICATION} from "@/event-bus.js";

//refs
const type = ref('success');
const show = ref(false);
const flashMessage = ref('')
let timeout;

// Methods

const props = defineProps({
    message:String,
})

const closeDelay = ()=>{
    // Clear the previous timeout and
    // set a new one to auto-hide the message
    if (timeout) clearTimeout(timeout);
        timeout = setTimeout(() => {
            show.value = false;
        }, 3000);
}

watch(
    ()=> props.message, 
    (message) => {
        if (message) {
            show.value = true;
            type.value= 'success';
            flashMessage.value = message;     
           closeDelay();
        }
    }
);

onMounted(()=>{

    emitter.on(SHOW_NOTIFICATION, ({type:result,message:msg})=>{
        show.value = true;
        type.value = result;
        flashMessage.value = msg;

        closeDelay();
    })

});



</script>


<template>
    <transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        <div v-if="show" class="fixed top-[10%] left-1/2 -translate-x-1/2 text-white text-center py-4 px-8 rounded-lg shadow-md w-fit font-figtree"
             :class="{
                'bg-emerald-500': type === 'success',
                'bg-red-500': type === 'error'
            }">
            {{ flashMessage }}
        </div>
    </transition>
</template>
