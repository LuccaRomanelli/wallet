<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditFailureCode: string
{
    case UserNotFound = 'user_not_found';
    case SelfTransfer = 'self_transfer';
    case MerchantCannotTransfer = 'merchant_cannot_transfer';
    case InsufficientBalance = 'insufficient_balance';
    case AuthorizationDenied = 'authorization_denied';
    case AuthorizationServiceUnavailable = 'authorization_service_unavailable';
}
