<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_one_id',
        'player_two_id',
        'player_one_credit',
        'player_two_credit',
        'match_reward',
        'match_fee',
        'status',
        'state',
        'winner_id',
        'winning_line',
    ];

    protected $casts = [
        'status' => 'boolean',
        'state' => 'json',
    ];

    public function playerOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_one_id');
    }

    public function playerTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_two_id');
    }
}
