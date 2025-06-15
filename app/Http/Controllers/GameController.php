<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameMove;
use App\Models\User;
use App\Events\GameInvite;
use App\Events\GameStarted;
use App\Events\GameMoveMade;
use App\Events\GameEnded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class GameController extends Controller
{
    /**
     * Handle game invitation.
     */
    public function invite(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id|different:me',
        ], [
            'user_id.different' => 'Anda tidak bisa mengajak diri sendiri bermain.',
        ]);

        $fromUser = Auth::user();
        $toUser = User::findOrFail($request->user_id);

        // Pastikan tidak ada game aktif antara kedua pemain
        $existingGame = Game::where(function ($query) use ($fromUser, $toUser) {
            $query->where('player_x_id', $fromUser->id)
                  ->where('player_o_id', $toUser->id);
        })->orWhere(function ($query) use ($fromUser, $toUser) {
            $query->where('player_x_id', $toUser->id)
                  ->where('player_o_id', $fromUser->id);
        })->where('status', 'playing')
          ->orWhere('status', 'pending')
          ->first();

        if ($existingGame) {
            return response()->json(['message' => 'Sudah ada game aktif atau undangan tertunda dengan user ini.'], 409);
        }

        GameInvite::dispatch($fromUser, $toUser);

        return response()->json(['message' => 'Undangan game terkirim!']);
    }

    /**
     * Accept a game invitation and start the game.
     */
    public function acceptInvite(Request $request)
    {
        $request->validate([
            'from_user_id' => 'required|exists:users,id',
        ]);

        $inviter = User::findOrFail($request->from_user_id);
        $acceptor = Auth::user();

        // Buat game baru
        $game = Game::create([
            'player_x_id' => $inviter->id, // Inviter jadi X
            'player_o_id' => $acceptor->id, // Acceptor jadi O
            'status' => 'playing',
            'current_turn_user_id' => $inviter->id, // X mulai duluan
        ]);

        GameStarted::dispatch($game);

        return response()->json(['message' => 'Game dimulai!', 'game_id' => $game->id]);
    }

    /**
     * Reject a game invitation (optional, could be implemented if needed).
     */
    public function rejectInvite(Request $request)
    {
        // Implementasi opsional jika Anda ingin fungsionalitas penolakan undangan
        return response()->json(['message' => 'Undangan ditolak. (Fitur ini belum sepenuhnya diimplementasikan)']);
    }

    /**
     * Handle a player making a move.
     */
    public function makeMove(Request $request, Game $game)
    {
        $user = Auth::user();

        // Validasi giliran
        if ($game->current_turn_user_id !== $user->id) {
            throw ValidationException::withMessages(['turn' => 'Bukan giliran Anda.']);
        }

        // Validasi status game
        if ($game->status !== 'playing') {
            throw ValidationException::withMessages(['game' => 'Game tidak dalam status bermain.']);
        }

        $request->validate([
            'position' => 'required|integer|min:0|max:8',
        ]);

        $board = $this->getBoardState($game);

        // Validasi posisi kosong
        if ($board[$request->position] !== null) {
            throw ValidationException::withMessages(['position' => 'Posisi ini sudah terisi.']);
        }

        $playerMark = ($user->id === $game->player_x_id) ? 'X' : 'O';

        // Simpan move
        $move = GameMove::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'position' => $request->position,
            'player_mark' => $playerMark,
        ]);

        // Update board state
        $board[$request->position] = $playerMark;

        // Cek pemenang atau seri
        $winner = $this->checkWinner($board);
        $isDraw = $this->checkDraw($board);

        if ($winner) {
            $game->status = 'finished';
            $game->winner_id = $user->id;
            $game->save();
            GameEnded::dispatch($game, $user->name, false);
        } elseif ($isDraw) {
            $game->status = 'finished';
            $game->save();
            GameEnded::dispatch($game, null, true);
        } else {
            // Ganti giliran
            $game->current_turn_user_id = ($user->id === $game->player_x_id) ? $game->player_o_id : $game->player_x_id;
            $game->save();
        }

        GameMoveMade::dispatch($game, $move, $board);

        return response()->json(['message' => 'Langkah berhasil!', 'game' => $game->load('playerX', 'playerO', 'currentTurnUser')]);
    }

    /**
     * Get the current state of the game board.
     */
    private function getBoardState(Game $game)
    {
        $board = array_fill(0, 9, null); // Inisialisasi papan 3x3 dengan null

        foreach ($game->moves()->orderBy('id')->get() as $move) {
            $board[$move->position] = $move->player_mark;
        }

        return $board;
    }

    /**
     * Check for a winner.
     */
    private function checkWinner(array $board)
    {
        $winningCombinations = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8], // Rows
            [0, 3, 6], [1, 4, 7], [2, 5, 8], // Columns
            [0, 4, 8], [2, 4, 6],             // Diagonals
        ];

        foreach ($winningCombinations as $combination) {
            $a = $board[$combination[0]];
            $b = $board[$combination[1]];
            $c = $board[$combination[2]];

            if ($a !== null && $a === $b && $b === $c) {
                return $a; // 'X' or 'O'
            }
        }
        return null;
    }

    /**
     * Check for a draw.
     */
    private function checkDraw(array $board)
    {
        if (in_array(null, $board)) {
            return false; // Masih ada kotak kosong
        }
        return !$this->checkWinner($board); // Tidak ada pemenang dan semua kotak terisi
    }

    /**
     * Show game board
     */
    public function showGame(Game $game)
    {
        // Pastikan user adalah salah satu pemain di game ini
        if ($game->player_x_id !== Auth::id() && $game->player_o_id !== Auth::id()) {
            abort(403, 'Anda tidak diizinkan mengakses game ini.');
        }

        $initialBoard = $this->getBoardState($game);

        return view('game', compact('game', 'initialBoard'));
    }
}