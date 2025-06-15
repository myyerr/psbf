<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_x_id',
        'player_o_id',
        'status',
        'current_turn_user_id',
        'winner_id',
    ];

    public function playerX()
    {
        return $this->belongsTo(User::class, 'player_x_id');
    }

    public function playerO()
    {
        return $this->belongsTo(User::class, 'player_o_id');
    }

    public function currentTurnUser()
    {
        return $this->belongsTo(User::class, 'current_turn_user_id');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function moves()
    {
        return $this->hasMany(GameMove::class);
    }
}