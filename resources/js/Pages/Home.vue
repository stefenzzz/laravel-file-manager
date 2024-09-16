<script setup>

</script>

<template>

    <Head title="Home" />

    <section class="mx-auto pt-20 text-center">
            Welcome to my storage drive application.<br>
            Please <Link :href="route('login')" class="text-blue-500">Login</Link> or <Link :href="route('register')" class="text-blue-500">Register</Link> to test.
    </section>
</template>