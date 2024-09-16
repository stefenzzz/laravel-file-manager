
<script setup>
// Imports
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import { watch, onMounted } from 'vue';

// Props & Emit
const props = defineProps({
    show: Boolean,
    message: String,
    processing: Boolean
})
const emit = defineEmits(['cancel', 'confirm']);



</script>

<template>
    <Modal :show="show" max-width="md">
        <div class="p-6">
            <h2 class="text-xl mb-2 font-semibold">Please Confirm</h2>
            <p class="text-sm">{{message}}</p>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="emit('cancel')">Cancel</SecondaryButton>

                <DangerButton :class="['ml-3', {'opacity-50':processing}]" @click="emit('confirm')" :disabled="processing">
                    Confirm
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>

