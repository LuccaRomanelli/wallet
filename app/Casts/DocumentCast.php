<?php

namespace App\Casts;

use App\ValueObjects\Identification\CNPJ;
use App\ValueObjects\Identification\CPF;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<CPF|CNPJ, string>
 */
class DocumentCast implements CastsAttributes
{
    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): CPF|CNPJ|null
    {
        if ($value === null) {
            return null;
        }

        $cleaned = preg_replace('/\D/', '', $value);

        return match (strlen($cleaned)) {
            11 => new CPF($cleaned),
            14 => new CNPJ($cleaned),
            default => throw new InvalidArgumentException('Document must be a valid CPF (11 digits) or CNPJ (14 digits)'),
        };
    }

    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CPF || $value instanceof CNPJ) {
            return $value->getValue();
        }

        $cleaned = preg_replace('/\D/', '', $value);

        return match (strlen($cleaned)) {
            11 => (new CPF($cleaned))->getValue(),
            14 => (new CNPJ($cleaned))->getValue(),
            default => throw new InvalidArgumentException('Document must be a valid CPF (11 digits) or CNPJ (14 digits)'),
        };
    }
}
