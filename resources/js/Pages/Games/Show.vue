<template>
    <AuthenticatedLayout>

        <menu class="grid grid-cols-3 gap-1.5 w-0 min-w-fit mx-auto mt-12">
            <li v-for="(square, index) in boardState" :key="index"
                :class="[
            'bg-gray-300',
            'rounded-md',
            'size-32',
            'grid',
            'place-items-center',
            square === -1 ? 'bg-green-500' : square === 1 ? 'bg-red-500' : ''
        ]">

                <button
                    @click="filterSquare(index)"
                    v-if="square === 0"
                    :class="['place-self-stretch', 'bg-gray-200', 'rounded-md', 'transition-colors', yourTurn ? 'hover:bg-gray-300' : '']">
                </button>

                <span v-else v-text="square === -1 ? 'X' : 'O'" class="text-4xl font-bold text-white"></span>

            </li>
        </menu>

        <Modal :show="gameFinished" @close="() => {}">
            <div class="p-6">
                <div class="text-6xl font-bold text-center my-12 font-mono uppercase">
                    <span v-if="winner === 'X'" class="text-green-600">
                        <span>{{ game.player_one.name }} WON!</span>
                    </span>
                    <span v-else-if="winner === 'O'" class="text-green-600">
                        <span>{{ game.player_two.name }} WON!</span>
                    </span>
                    <span v-else class="text-orange-600">
                        Stalemate!
                    </span>
                </div>
                <div class="text-center mt-8">
                    <button @click="resetGame()"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Play Again
                    </button>
                    <button @click="$inertia.visit(route('dashboard'))"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded ml-4">
                        Back to Dashboard
                    </button>
                </div>
            </div>
        </Modal>


        <ul class="max-w-sm mx-auto mt-6 space-y-2">
            <li class="flex items-center gap-2">
                <span class="p-1.5 font-bold rounded bg-gray-200" :class="{ 'bg-green-200': xTurn }">X</span>

                <span>{{ game.player_one.name }}</span>
                <span v-if="game.player_one.wins !== undefined && game.player_one.losses !== undefined" class="text-sm text-gray-600">
                    (W: {{ game.player_one.wins }} L: {{ game.player_one.losses }})
                </span>

                <span :class="{ '!bg-green-500': players.find( ({id}) => id === game.player_one_id ) }"
                      class="bg-red-500 size-2 rounded-full"></span>

            </li>

            <li v-if="game.player_two" class="flex items-center gap-2">

                <span class="p-1.5 font-bold rounded bg-gray-200" :class="{ 'bg-green-200': !xTurn }">O</span>

                <span>{{ game.player_two.name }}</span>
                <span v-if="game.player_two.wins !== undefined && game.player_two.losses !== undefined" class="text-sm text-gray-600">
                    (W: {{ game.player_two.wins }} L: {{ game.player_two.losses }})
                </span>

                <span :class="{ '!bg-green-500': players.find( ({id}) => id === game.player_two_id ) }"
                      class="bg-red-500 size-2 rounded-full"></span>
            </li>

            <li v-else class="flex items-center gap-2">

                <span class="p-1.5 font-bold rounded bg-gray-200">O</span>
                <span>Waiting Player...</span>
                <span class="bg-yellow-500 size-2 rounded-full animate-ping"></span>
            </li>
        </ul>

    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {router, usePage} from '@inertiajs/vue3';
import {computed, onMounted, onUnmounted, ref} from "vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps(['game']);

// -1 = X, 1 = O
const boardState = ref(props.game.state ?? [
    0, 0, 0,
    0, 0, 0,
    0, 0, 0
]);

const players = ref([]);
const gameFinished = ref(false); // game is ended
const winner = ref(''); // ('X', 'O' or 'Stalemate')

const page = usePage();
const xTurn = computed(() => boardState.value.reduce((carry, value) => carry + value, 0) === 0);
const yourTurn = computed(() => {
    if (props.game.player_one_id === page.props.auth.user.id) {
        return xTurn.value;
    }
    return !xTurn.value;
});

// Winning lines
const lines = [
    // rows
    [0, 1, 2],
    [3, 4, 5],
    [6, 7, 8],
    // columns
    [0, 3, 6],
    [1, 4, 7],
    [2, 5, 8],
    // diagonals
    [0, 4, 8],
    [2, 4, 6]
];

const channel = Echo.join(`games.${props.game.id}`)
    .here((users) => players.value = users)
    .joining((user) => router.reload({
        onSuccess: () => players.value.push(user)
    }))
    .leaving((user) => players.value = players.value.filter(({id}) => id !== user.id))
    .listenForWhisper('PlayerMadeMove', ({state}) => {
        boardState.value = state;
        checkForVictory();
    });

const updateOpponent = () => {
    router.put(route('games.update', props.game.id), {
        state: boardState.value
    });

    channel.whisper('PlayerMadeMove', {
        state: boardState.value,
    });
};

const filterSquare = (index) => {
    // if the game is finished or it's not your turn
    if (gameFinished.value || !yourTurn.value) {
        return;
    }

    // if the square is already filled
    if (boardState.value[index] !== 0) {
        return;
    }

    boardState.value[index] = xTurn.value ? -1 : 1;

    updateOpponent();
    checkForVictory();
};

const checkForVictory = () => {

    // Check if O is a winning line
    const winningLine = lines.map((line) => line.reduce((carry, index) => carry + boardState.value[index], 0))
        .find((sum) => Math.abs(sum) === 3);

    if (winningLine === -3) {
        winner.value = 'X';
        gameFinished.value = true;
    } else if (winningLine === 3) {
        winner.value = 'O';
        gameFinished.value = true;
    } else if (!boardState.value.includes(0)) {
        winner.value = 'Stalemate';
        gameFinished.value = true;
    }

    // Send a POST request if the game is finished
    if (gameFinished.value) {
        router.post(route('games.updateStatus', props.game.id), {
            gameId: props.game.id,
            playerWonId: winner.value === 'X' ? props.game.player_one_id : winner.value === 'O' ? props.game.player_two_id : null,
            winningLine: winningLine
        });
    }
};

const resetGame = () => {
    boardState.value = [0, 0, 0, 0, 0, 0, 0, 0, 0];
    gameFinished.value = false; // Reset the game state to allow replays
    winner.value = ''; // Reset the winner
    updateOpponent();
};

onMounted(checkForVictory);

onUnmounted(() => {
    Echo.leave(`games.${props.game.id}`);
});

</script>

