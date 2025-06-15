<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Game Tic Tac Toe
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-xl font-semibold mb-4 text-center">
                        {{ $game->playerX->name }} (X) vs {{ $game->playerO->name }} (O)
                    </h3>

                    <p id="game-status" class="text-center text-lg mb-4">
                        {{-- Status game akan diupdate di sini --}}
                    </p>

                    <div id="game-board" class="grid grid-cols-3 gap-2 w-72 mx-auto border-4 border-gray-700">
                        @for ($i = 0; $i < 9; $i++)
                            <div class="cell w-24 h-24 bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-5xl font-bold cursor-pointer"
                                 data-position="{{ $i }}">
                                {{ $initialBoard[$i] }}
                            </div>
                        @endfor
                    </div>

                    <div id="game-controls" class="mt-8 text-center hidden">
                        <button id="restart-game" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">Mulai Ulang Game</button>
                    </div>

                    <div id="game-messages" class="mt-4 text-center text-red-500"></div>
                </div>
            </div>
        </div>
    </div>

    <script type="module">
        document.addEventListener('DOMContentLoaded', function () {
            const gameId = {{ $game->id }};
            const userId = {{ Auth::id() }};
            const playerXId = {{ $game->player_x_id }};
            const playerOId = {{ $game->player_o_id }};

            let currentBoard = @json($initialBoard);
            let currentTurnUserId = {{ $game->current_turn_user_id }};
            let gameStatus = '{{ $game->status }}';

            const gameBoardElement = document.getElementById('game-board');
            const cells = gameBoardElement.querySelectorAll('.cell');
            const gameStatusElement = document.getElementById('game-status');
            const gameMessagesElement = document.getElementById('game-messages');
            const restartGameButton = document.getElementById('restart-game');

            function updateBoardUI() {
                cells.forEach((cell, index) => {
                    cell.textContent = currentBoard[index];
                    cell.classList.remove('text-blue-500', 'text-red-500');
                    if (currentBoard[index] === 'X') {
                        cell.classList.add('text-blue-500');
                    } else if (currentBoard[index] === 'O') {
                        cell.classList.add('text-red-500');
                    }
                });
            }

            function updateGameStatusUI() {
                if (gameStatus === 'finished') {
                    gameBoardElement.classList.add('pointer-events-none'); // Disable clicks
                    if (currentTurnUserId === playerXId) { // Winner was X
                        gameStatusElement.textContent = `Game Selesai! {{ $game->playerX->name }} (X) Menang!`;
                    } else if (currentTurnUserId === playerOId) { // Winner was O
                        gameStatusElement.textContent = `Game Selesai! {{ $game->playerO->name }} (O) Menang!`;
                    } else { // Draw
                        gameStatusElement.textContent = `Game Selesai! Seri!`;
                    }
                    document.getElementById('game-controls').classList.remove('hidden');
                } else {
                    const currentPlayerName = (currentTurnUserId === playerXId)
                        ? '{{ $game->playerX->name }}'
                        : '{{ $game->playerO->name }}';
                    const currentPlayerMark = (currentTurnUserId === playerXId) ? 'X' : 'O';
                    gameStatusElement.textContent = `Giliran: ${currentPlayerName} (${currentPlayerMark})`;
                    if (currentTurnUserId === userId) {
                        gameBoardElement.classList.remove('pointer-events-none'); // Enable clicks for current player
                    } else {
                        gameBoardElement.classList.add('pointer-events-none'); // Disable clicks for opponent
                    }
                }
            }

            // Initial UI update
            updateBoardUI();
            updateGameStatusUI();

            // Listen for game events
            window.Echo.private(`games.${gameId}`)
                .listen('.game.move.made', (e) => {
                    console.log('GameMoveMade:', e);
                    currentBoard = e.board;
                    currentTurnUserId = e.game.current_turn_user_id;
                    gameStatus = e.game.status;
                    updateBoardUI();
                    updateGameStatusUI();
                })
                .listen('.game.ended', (e) => {
                    console.log('GameEnded:', e);
                    gameStatus = e.game.status;
                    if (e.isDraw) {
                        currentTurnUserId = null; // No winner
                    } else {
                        currentTurnUserId = e.game.winner_id; // Set winner as current turn for status
                    }
                    updateGameStatusUI();
                })
                .error((error) => {
                    console.error('Channel Error:', error);
                    gameMessagesElement.textContent = 'Terjadi kesalahan pada koneksi game.';
                });

            // Handle cell clicks
            gameBoardElement.addEventListener('click', async function(event) {
                if (event.target.classList.contains('cell')) {
                    const position = parseInt(event.target.dataset.position);

                    if (currentBoard[position] !== null) {
                        gameMessagesElement.textContent = 'Posisi ini sudah terisi.';
                        return;
                    }
                    if (userId !== currentTurnUserId) {
                        gameMessagesElement.textContent = 'Bukan giliran Anda.';
                        return;
                    }
                    if (gameStatus !== 'playing') {
                        gameMessagesElement.textContent = 'Game sudah selesai atau belum dimulai.';
                        return;
                    }

                    gameMessagesElement.textContent = ''; // Clear previous messages

                    try {
                        const response = await fetch(`/api/game/${gameId}/make-move`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: JSON.stringify({ position: position }),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Gagal melakukan langkah.');
                        }

                        // Event listener akan menangani update UI
                        // console.log('Move successful:', data);

                    } catch (error) {
                        console.error('Error making move:', error);
                        gameMessagesElement.textContent = error.message;
                    }
                }
            });

            // Handle restart game (basic implementation, might need more complex logic for new game)
            restartGameButton.addEventListener('click', function() {
                alert('Fungsi restart game akan membuat game baru. Kembali ke dashboard untuk mengajak teman.');
                window.location.href = '/dashboard'; // Redirect to dashboard for new game
            });
        });
    </script>
</x-app-layout>