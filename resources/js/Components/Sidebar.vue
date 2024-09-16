<script setup>
import CreateNewDropdown from '@/Components/CreateNewDropdown.vue';
import NavLink from '@/Components/NavLink.vue'

</script>
<template>
    <div v-if="$page.props.auth.user" class="pt-8 pl-4 space-y-3 w-full max-w-44" >          
        <CreateNewDropdown/>
        <NavLink :href="route('myfiles')" :active="$page.props.route.name === 'myfiles'"> My Files</NavLink>
        <NavLink :href="route('file.sharedWithMe')" :active="$page.props.route.name === 'file.sharedWithMe'">Shared with me</NavLink>
        <NavLink :href="route('file.sharedByMe')" :active="$page.props.route.name === 'file.sharedByMe'">Shared by me</NavLink>
        <NavLink :href="route('trash')" :active="$page.props.route.name === 'trash'">Trash</NavLink>
    </div>
</template>