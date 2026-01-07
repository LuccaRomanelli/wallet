<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\AuditFailureCode;
use App\Enums\AuditStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionAuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionAuditLogFactory> */
    use HasFactory;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'payer_id',
        'payee_id',
        'amount',
        'status',
        'failure_reason',
        'failure_code',
        'client_ip',
        'user_agent',
        'request_id',
        'authorization_response',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AuditStatus::class,
            'failure_code' => AuditFailureCode::class,
            'authorization_response' => 'array',
            'amount' => MoneyCast::class,
            'created_at' => 'datetime:Y-m-d H:i:s.u',
            'updated_at' => 'datetime:Y-m-d H:i:s.u',
        ];
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function payee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payee_id');
    }
}
