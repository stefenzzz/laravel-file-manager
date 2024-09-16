<script setup>
import {onMounted, ref} from "vue";
import {emitter, SHOW_NOTIFICATION} from "@/event-bus.js";

//refs
const show = ref(false)
const type = ref('success')
const message = ref('')

// Methods

function close() {
    show.value = false;
    type.value = '';
    message.value = ''
}

// Hooks
onMounted(() => {
    let timeout;
    emitter.on(SHOW_NOTIFICATION, ({type: result, message: msg}) => {
        show.value = true;
        type.value = result;
        message.value = msg;

        if (timeout) clearTimeout(timeout)
        timeout = setTimeout(() => {
            close()
        }, 5000)
    })
})

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
        <div v-if="show" class="fixed top-[10%] left-1/2 -translate-x-1/2 text-white text-center py-4 px-4 rounded-lg shadow-md w-full max-w-[300px] h-"
             :class="{
                'bg-emerald-500': type === 'success',
                'bg-red-500': type === 'error'
            }">
            {{ message }}
        </div>
    </transition>
</template>
