<?php

namespace App\Http\Controllers;

use App\Events\GameJoined;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\User; // Ditambahkan: Import model User
use Illuminate\Support\Facades\DB; // Ditambahkan: Import facade DB untuk transaksi
use App\Events\ChatMessageSent;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index (Request $request)
    {
        return inertia('Dashboard', [
            'games' => Game::with('playerOne')
                //->whereNull('player_two_id')
                //->where('player_one_id', '!=', $request->user()->id)
                ->where('status', false)
                ->oldest()
                ->simplePaginate(100)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create ()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store (Request $request)
    {
        $game = Game::create(['player_one_id' => $request->user()->id]);

        return to_route('games.show', $game);
    }

    public function join (Request $request, Game $game)
    {
        Gate::authorize('join', $game);

        if ($game->player_one_id === $request->user()->id || $game->player_two_id === $request->user()->id) {
            GameJoined::dispatch($game);
            return to_route('games.show', $game);
        }

        if ($game->player_one_id === null) {
            $game->update(['player_one_id' => $request->user()->id]);
        } elseif ($game->player_two_id === null) {
            $game->update(['player_two_id' => $request->user()->id]);
        } else {
            return back()->withErrors(['msg' => 'Jogo já possui dois jogadores.']);
        }

        GameJoined::dispatch($game);

        return to_route('games.show', $game);
    }

    public function updateStatus (Request $request, Game $game)
    {
        $validated = $request->validate([
            'gameId' => 'required|exists:games,id',
            'playerWonId' => 'nullable|exists:users,id',
            'winningLine' => 'nullable|int'
        ]);

        // Ditambahkan: Cek apakah game sudah selesai sebelumnya untuk mencegah update berulang
        if ($game->status) {
            return to_route('games.show', $game);
        }

        // Ditambahkan: Gunakan transaksi database untuk memastikan update atomik
        DB::transaction(function () use ($game, $validated) {
            // Update status game di tabel games
            $game->update([
                'status' => true,
                'winner_id' => $validated['playerWonId'],
                'winning_line' => $validated['winningLine']
            ]);

            // Dapatkan ID pemain
            $playerOneId = $game->player_one_id;
            $playerTwoId = $game->player_two_id;

            // Logika untuk mengupdate skor pemain (wins/losses)
            if ($validated['playerWonId']) {
                // Jika ada pemenang
                $winnerId = $validated['playerWonId'];
                $loserId = null;

                if ($winnerId == $playerOneId) {
                    $loserId = $playerTwoId;
                } elseif ($winnerId == $playerTwoId) {
                    $loserId = $playerOneId;
                }

                // Increment wins untuk pemenang
                if ($winnerId) {
                    User::where('id', $winnerId)->increment('wins');
                }

                // Increment losses untuk yang kalah
                if ($loserId) {
                    User::where('id', $loserId)->increment('losses');
                }
            } else {
                // Jika game seri (stalemate) dan tidak ada playerWonId
                // Jika Anda memiliki kolom 'draws' di tabel users, Anda bisa mengupdatenya di sini
                // Contoh:
                // if ($playerOneId) {
                //     User::where('id', $playerOneId)->increment('draws');
                // }
                // if ($playerTwoId) {
                //     User::where('id', $playerTwoId)->increment('draws');
                // }
            }
        }); // Akhir transaksi

        return to_route('games.show', $game);
    }

    /**
     * Display the specified resource.
     */
    public function show (Game $game)
    {
        // Pastikan untuk me-load data playerOne dan playerTwo
        // Karena kolom 'wins' dan 'losses' ada langsung di model User,
        // Cukup load relasinya, Laravel akan menyertakan kolom tersebut secara otomatis.
        $game->load('playerOne', 'playerTwo');

        return inertia('Games/Show', compact('game'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit (Game $game)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update (Request $request, Game $game)
    {
        $data = $request->validate([
            'state' => ['required', 'array', 'size:9'],
            'state.*' => ['integer', 'between:-1,1'],
        ]);

        $game->update($data);

        return to_route('games.show', $game);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy (Game $game)
    {
        //
    }
}
