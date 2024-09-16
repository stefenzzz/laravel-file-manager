<script setup>
// Imports
import Modal from "@/Components/Modal.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {nextTick, ref, watch} from "vue";
import {showSuccessNotification, showErrorNotification} from "@/event-bus.js";

// Uses
const form = useForm({
    email: '',
    all: false,
    ids:[],
    parent_id: null
})
const page = usePage();

// Refs
const emailInput = ref(null)

// Props & Emit
const props = defineProps({
    modelValue: Boolean,
    allSelected: Boolean,
    selectedIds: Array
})
const emit = defineEmits(['update:modelValue'])

// Computed

// Methods
function onShow() {
    nextTick(() => emailInput.value.focus())
}


function share() {
    form.parent_id = page.props.folder.id
    if (props.allSelected) {
        form.all = true;
    }  else {
        form.ids = props.selectedIds
    }
    const email = form.email

    form.post(route('file.share'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal()
            form.clearErrors();

            if(page.props.flash.error) {
                showErrorNotification(page.props.flash.error);
                return;
            }
            // Show success notification
            showSuccessNotification(`Selected file(s) are now shared to ${email}.`);
        },
        onError: () => emailInput.value.focus(),
        onFinish: () => {
            form.reset();
        },
    })
}

function closeModal() {
    emit('update:modelValue')
    form.clearErrors();
    form.reset()
}


</script>



<template>
    <modal :show="modelValue" @show="onShow" max-width="sm">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                Share Files
            </h2>
            <div class="mt-6">
                <InputLabel for="shareEmail" value="Enter Email Address" class="sr-only"/>

                <TextInput type="text"
                           ref="emailInput"
                           id="shareEmail"
                           v-model="form.email"
                           :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500' : form.errors.email }"
                           class="mt-1 block w-full"
                           placeholder="Enter Email Address"
                           @keyup.enter="share"
                />
                <InputError :message="form.errors.email" class="mt-2"/>

            </div>
            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3"
                               :class="{ 'opacity-25': form.processing }"
                               @click="!form.processing && share()" :disabled="form.processing">
                    Submit
                </PrimaryButton>
            </div>
        </div>
    </modal>
</template>

