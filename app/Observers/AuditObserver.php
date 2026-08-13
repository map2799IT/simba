<?php

namespace App\Observers;

use App\Models\AuditLog;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use JsonSerializable;
use Stringable;
use Throwable;

class AuditObserver
{
    /**
     * Kolom yang tidak perlu dicatat sebagai perubahan.
     */
    private const IGNORED_FIELDS = [
        'created_at',
        'updated_at',
        'remember_token',
        'password',
        'password_confirmation',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Kolom yang tidak boleh muncul pada audit.
     */
    private const HIDDEN_FIELDS = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function created(Model $model): void
    {
        $this->write(
            event: AuditLog::EVENT_CREATED,
            model: $model,
            oldValues: [],
            newValues: $this->sanitize(
                $model->getAttributes()
            )
        );
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except(
            $model->getChanges(),
            self::IGNORED_FIELDS
        );

        if ($changes === []) {
            return;
        }

        $oldValues = [];

        foreach (array_keys($changes) as $field) {
            $oldValues[$field] =
                $model->getRawOriginal($field);
        }

        $this->write(
            event: AuditLog::EVENT_UPDATED,
            model: $model,
            oldValues: $this->sanitize($oldValues),
            newValues: $this->sanitize($changes)
        );
    }

    public function deleted(Model $model): void
    {
        $this->write(
            event: AuditLog::EVENT_DELETED,
            model: $model,
            oldValues: $this->sanitize(
                $model->getAttributes()
            ),
            newValues: []
        );
    }

    public function restored(Model $model): void
    {
        $this->write(
            event: AuditLog::EVENT_RESTORED,
            model: $model,
            oldValues: [],
            newValues: $this->sanitize(
                $model->getAttributes()
            )
        );
    }

    private function write(
        string $event,
        Model $model,
        array $oldValues,
        array $newValues
    ): void {
        /*
         * Audit tidak boleh menggagalkan transaksi utama hanya
         * karena metadata request tidak tersedia.
         */
        try {
            $isConsole = app()->runningInConsole();

            AuditLog::query()->create([
                'user_id' => Auth::id(),

                'event' => $event,

                'auditable_type' =>
                    $model::class,

                'auditable_id' =>
                    (int) $model->getKey(),

                'auditable_label' =>
                    $this->makeLabel($model),

                'route_name' => $isConsole
                    ? 'console'
                    : request()->route()?->getName(),

                'url' => $isConsole
                    ? null
                    : request()->fullUrl(),

                'method' => $isConsole
                    ? 'CLI'
                    : request()->method(),

                'ip_address' => $isConsole
                    ? null
                    : request()->ip(),

                'user_agent' => $isConsole
                    ? null
                    : Str::limit(
                        (string) request()->userAgent(),
                        500,
                        ''
                    ),

                'old_values' => $oldValues !== []
                    ? $oldValues
                    : null,

                'new_values' => $newValues !== []
                    ? $newValues
                    : null,

                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function makeLabel(
        Model $model
    ): string {
        $code = trim(
            (string) $model->getAttribute('code')
        );

        $name = trim(
            (string) $model->getAttribute('name')
        );

        if ($code !== '' && $name !== '') {
            return "{$code} — {$name}";
        }

        if ($code !== '') {
            return $code;
        }

        if ($name !== '') {
            return $name;
        }

        $reference = trim(
            (string) $model->getAttribute(
                'reference_number'
            )
        );

        if ($reference !== '') {
            return $reference;
        }

        return class_basename($model)
            .' #'
            .$model->getKey();
    }

    private function sanitize(
        array $values
    ): array {
        $values = Arr::except(
            $values,
            self::HIDDEN_FIELDS
        );

        $sanitized = [];

        foreach ($values as $key => $value) {
            $sanitized[$key] =
                $this->normalizeValue($value);
        }

        return $sanitized;
    }

    private function normalizeValue(
        mixed $value
    ): mixed {
        if ($value === null) {
            return null;
        }

        if (
            is_string($value)
            || is_int($value)
            || is_float($value)
            || is_bool($value)
        ) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(
                DATE_ATOM
            );
        }

        if ($value instanceof JsonSerializable) {
            return $value->jsonSerialize();
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_array($value)) {
            return array_map(
                fn (mixed $item): mixed =>
                    $this->normalizeValue($item),
                $value
            );
        }

        return json_decode(
            json_encode($value),
            true
        );
    }
}