<script setup>
import {HomeIcon,  ChevronRightIcon} from '@heroicons/vue/20/solid'

defineProps({
    ancestors: [Array, Object]
});

</script>



<template>
    <nav class="flex items-center justify-between p-1">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li v-for="ans of ancestors.data" :key="ans.id" class="inline-flex items-center">
                <Link v-if="!ans.parent_id" :href="route('myfiles')"
                    class="inline-flex gap-x-1 text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                    <HomeIcon class="w-4 h-4"/>
                    My Files
                </Link>
                <div v-else class="flex items-center">
                    <ChevronRightIcon class="w-6 h-6 text-slate-600"/>
                    <Link :href="route('myfiles', {folder: ans.path})"
                            class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                        {{ ans.name }}
                    </Link>
                </div>
            </li>
        </ol>
    </nav>
</template>