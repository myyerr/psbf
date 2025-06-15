<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $game;
    public $winnerName;
    public $isDraw;

    /**
     * Create a new event instance.
     */
    public function __construct(Game $game, $winnerName = null, $isDraw = false)
    {
        $this->game = $game;
        $this->winnerName = $winnerName;
        $this->isDraw = $isDraw;
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
        return 'game.ended';
    }
}