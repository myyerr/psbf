<template>
    <Head title="Dashboard"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg py-4 px-6">

                    <Link :href="route('games.store')" method="post" as="button"
                          class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Create Game
                    </Link>

                    <ul class="divide-y mt-6">
                        <li v-for="game in games" :key="game.id" class="flex justify-between items-center px-2 py-1.5">

                            <span>
                                #{{ game.id }} by {{ game.player_one.name }}
                            </span>

                            <span v-if="game.player_one_id === page.props.auth.user.id"
                                  class="text-amber-600 font-semibold">
                                [My Room]
                            </span>

                            <Link v-if="game.player_one_id === page.props.auth.user.id && game.player_two_id === NULL"
                                  :href="`/games/${game.id}`" method="get" as="button"
                                  class="hover:bg-gray-100 transition-colors p-2 rounded-md">
                                Join Again
                            </Link>

                            <Link v-else :href="route('games.join', game)" method="post" as="button"
                                  class="hover:bg-gray-100 transition-colors p-2 rounded-md">
                                Join Game
                            </Link>

                        </li>
                    </ul>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, Link, router, usePage} from '@inertiajs/vue3';
import {ref} from "vue";

const page = usePage();
const props = defineProps(['games']);
const games = ref(props.games.data);

Echo.private('lobby')
    .listen('GameJoined', (event) => {
        games.value = games.value.filter((game) => game.id !== event.game.id);

        // Reload the page if there are less than 5 games
        if (games.value.length < 5) {
            router.reload({only: ['games'], onSuccess: () => games.value = props.games.data});
        }

    });

</script>
