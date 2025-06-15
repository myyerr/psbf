<?php

namespace App\Events;

use App\Models\Game;
use App\Models\GameMove;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameMoveMade implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $game;
    public $move;
    public $board; // Current state of the board

    /**
     * Create a new event instance.
     */
    public function __construct(Game $game, GameMove $move, array $board)
    {
        $this->game = $game;
        $this->move = $move;
        $this->board = $board;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast ke channel game khusus
        return [
            new PrivateChannel('games.' . $this->game->id),
        ];
    }

    /**
     * The name of the event to broadcast.
     */
    public function broadcastAs(): string
    {
        return 'game.move.made';
    }
}