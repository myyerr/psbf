<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{ __("Anda telah login!") }}
                </div>

                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">Pemain Online</h3>
                    <ul id="online-users">
                        </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal untuk Undangan Game --}}
    <div id="invite-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center hidden">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl max-w-sm w-full">
            <h4 class="text-lg font-bold mb-4 text-gray-900 dark:text-gray-100">Undangan Game</h4>
            <p class="mb-4 text-gray-900 dark:text-gray-100">
                <span id="inviter-name"></span> mengajak Anda bermain Tic-Tac-Toe.
            </p>
            <div class="flex justify-end space-x-4">
                <button id="accept-invite" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">Terima</button>
                <button id="reject-invite" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">Tolak</button>
            </div>
        </div>
    </div>

    <script type="module">
        document.addEventListener('DOMContentLoaded', function () {
            const onlineUsersList = document.getElementById('online-users');
            const users = {}; // Untuk menyimpan daftar pengguna yang online
            const inviteModal = document.getElementById('invite-modal');
            const inviterNameElement = document.getElementById('inviter-name');
            const acceptInviteButton = document.getElementById('accept-invite');
            const rejectInviteButton = document.getElementById('reject-invite');

            let currentInviterId = null;

            window.Echo.join('online-users')
                .here((members) => {
                    members.forEach(member => {
                        if (member.id !== {{ Auth::id() }}) { // Jangan tampilkan diri sendiri
                            users[member.id] = member;
                            addOnlineUser(member);
                        }
                    });
                })
                .joining((member) => {
                    if (member.id !== {{ Auth::id() }}) {
                        users[member.id] = member;
                        addOnlineUser(member);
                    }
                })
                .leaving((member) => {
                    delete users[member.id];
                    removeOnlineUser(member);
                })
                .error((error) => {
                    console.error(error);
                });

            function addOnlineUser(member) {
                if (!document.getElementById(`user-${member.id}`)) {
                    const listItem = document.createElement('li');
                    listItem.id = `user-${member.id}`;
                    listItem.classList.add('flex', 'items-center', 'justify-between', 'py-2', 'border-b', 'border-gray-700');
                    listItem.innerHTML = `
                        <span class="text-gray-900 dark:text-gray-100">${member.name}</span>
                        <button class="invite-button px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-md" data-user-id="${member.id}" data-user-name="${member.name}">Ajak Main</button>
                    `;
                    onlineUsersList.appendChild(listItem);
                }
            }

            function removeOnlineUser(member) {
                const listItem = document.getElementById(`user-${member.id}`);
                if (listItem) {
                    listItem.remove();
                }
            }

            // Menangani klik tombol "Ajak Main"
            onlineUsersList.addEventListener('click', async function(event) {
                if (event.target.classList.contains('invite-button')) {
                    const userId = event.target.dataset.userId;
                    const userName = event.target.dataset.userName;

                    try {
                        const response = await fetch('/api/game/invite', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: JSON.stringify({ user_id: userId }),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            alert(data.message || 'Gagal mengirim undangan.');
                            return;
                        }
                        alert(`Undangan game terkirim ke ${userName}!`);
                    } catch (error) {
                        console.error('Error sending invite:', error);
                        alert('Terjadi kesalahan saat mengirim undangan.');
                    }
                }
            });

            // Listen for private channel events for the authenticated user
            window.Echo.private(`users.{{ Auth::id() }}`)
                .listen('.game.invite', (e) => {
                    console.log('Game Invite received:', e);
                    currentInviterId = e.fromUser.id;
                    inviterNameElement.textContent = e.fromUser.name;
                    inviteModal.classList.remove('hidden');
                })
                .listen('.game.started', (e) => {
                    console.log('Game Started received:', e);
                    alert(`Game baru dimulai dengan ${e.game.player_x_id === {{ Auth::id() }} ? e.game.player_o.name : e.game.player_x.name}! Mengarahkan ke game...`);
                    inviteModal.classList.add('hidden'); // Sembunyikan modal jika terbuka
                    window.location.href = `/game/${e.game.id}`; // Redirect ke halaman game
                })
                .error((error) => {
                    console.error('Private Channel Error:', error);
                });

            // Handle Accept Invite
            acceptInviteButton.addEventListener('click', async function() {
                try {
                    const response = await fetch('/api/game/accept-invite', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify({ from_user_id: currentInviterId }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        alert(data.message || 'Gagal menerima undangan.');
                        return;
                    }
                    // Redirection ke game akan ditangani oleh event `game.started`
                } catch (error) {
                    console.error('Error accepting invite:', error);
                    alert('Terjadi kesalahan saat menerima undangan.');
                }
            });

            // Handle Reject Invite
            rejectInviteButton.addEventListener('click', function() {
                alert('Undangan ditolak.');
                inviteModal.classList.add('hidden');
                currentInviterId = null;
                // TODO: Kirim event ke pengundang bahwa undangan ditolak
            });
        });
    </script>
</x-app-layout>