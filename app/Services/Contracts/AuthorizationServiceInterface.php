<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Exceptions\Transfer\TransferAuthorizationException;

interface AuthorizationServiceInterface
{
    /**
     * Authorize a transfer with the external service.
     *
     * @return array<string, mixed> The authorization response data
     *
     * @throws TransferAuthorizationException
     */
    public function authorize(): array;
}
