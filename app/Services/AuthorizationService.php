<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Transfer\TransferAuthorizationException;
use App\Services\Contracts\AuthorizationServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthorizationService implements AuthorizationServiceInterface
{
    public function authorize(): array
    {
        try {
            $response = Http::timeout(config('services.external_authorization.timeout'))
                ->get(config('services.external_authorization.url'));

            $data = $response->json();

            if (! $response->successful()) {
                Log::warning('Authorization service returned non-success status', [
                    'status' => $response->status(),
                    'response' => $data,
                ]);
                throw new TransferAuthorizationException('Authorization service unavailable.');
            }

            $authorized = $data['data']['authorization'] ?? false;

            if (! $authorized) {
                Log::info('Transfer authorization denied', ['response' => $data]);
                throw new TransferAuthorizationException('Transfer not authorized.');
            }

            return $data;
        } catch (TransferAuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Authorization service error', [
                'message' => $e->getMessage(),
            ]);
            throw new TransferAuthorizationException('Failed to contact authorization service.');
        }
    }
}
