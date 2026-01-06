<?php

namespace App\Casts;

use App\ValueObjects\Cnpj;
use App\ValueObjects\Cpf;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<Cpf|Cnpj, string>
 */
class DocumentCast implements CastsAttributes
{
    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Cpf|Cnpj|null
    {
        if ($value === null) {
            return null;
        }

        $cleaned = preg_replace('/\D/', '', $value);

        return match (strlen($cleaned)) {
            11 => new Cpf($cleaned),
            14 => new Cnpj($cleaned),
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

        if ($value instanceof Cpf || $value instanceof Cnpj) {
            return $value->value();
        }

        $cleaned = preg_replace('/\D/', '', $value);

        return match (strlen($cleaned)) {
            11 => (new Cpf($cleaned))->value(),
            14 => (new Cnpj($cleaned))->value(),
            default => throw new InvalidArgumentException('Document must be a valid CPF (11 digits) or CNPJ (14 digits)'),
        };
    }
}
