<script setup>
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {onMounted, ref} from "vue";
import {emitter, SHOW_ERROR_DIALOG} from "@/event-bus.js";


// Refs
const show = ref(false);
const message = ref('')

// Props & Emit
const emit = defineEmits(['close'])

// Methods
function close(){
    show.value = false
    message.value = ''
}

// Hooks
onMounted(() => {
    emitter.on(SHOW_ERROR_DIALOG, ({message: msg}) => {
        show.value = true;
        message.value = msg
    })
})

</script>


<template>
    <Modal :show="show" max-width="md">
        <div class="p-6 font-figtree">
            <h2 class="text-lg mb-2 font-sans text-red-500 font-bold">ERROR</h2>
            <p class="text-sm">{{message}}</p>
            <div class="mt-6 flex justify-end">
                <PrimaryButton @click="close">OK</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

