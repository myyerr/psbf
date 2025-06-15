<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $game;

    /**
     * Create a new event instance.
     */
    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast ke private channel kedua pemain
        return [
            new PrivateChannel('users.' . $this->game->player_x_id),
            new PrivateChannel('users.' . $this->game->player_o_id),
            new PrivateChannel('games.' . $this->game->id), // Channel khusus untuk game ini
        ];
    }

    /**
     * The name of the event to broadcast.
     */
    public function broadcastAs(): string
    {
        return 'game.started';
    }
}