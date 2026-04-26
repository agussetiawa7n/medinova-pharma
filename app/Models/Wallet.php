<?php

namespace App\Models;

use App\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = ['user_id', 'balance', 'currency'];

    protected function casts(): array
    {
        return ['balance' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function credit(float $amount, string $description = '', ?string $referenceType = null, ?int $referenceId = null): WalletTransaction
    {
        $this->increment('balance', $amount);
        $this->refresh();

        return $this->transactions()->create([
            'user_id'        => $this->user_id,
            'type'           => WalletTransactionType::Credit->value,
            'amount'         => $amount,
            'balance_after'  => $this->balance,
            'description'    => $description,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
        ]);
    }

    public function debit(float $amount, string $description = '', ?string $referenceType = null, ?int $referenceId = null): WalletTransaction
    {
        $this->decrement('balance', $amount);
        $this->refresh();

        return $this->transactions()->create([
            'user_id'        => $this->user_id,
            'type'           => WalletTransactionType::Debit->value,
            'amount'         => $amount,
            'balance_after'  => $this->balance,
            'description'    => $description,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
        ]);
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
