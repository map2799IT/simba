<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserErrorLog extends Model
{
    protected $fillable = [
        'user_id',
        'exception_class',
        'message',
        'url',
        'method',
        'route_name',
        'stack_trace',
        'request_data',
        'ip_address',
        'user_agent',
        'http_status',
        'is_resolved',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'request_data' => 'array',
            'is_resolved' => 'boolean',
            'http_status' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(\Throwable $e, ?\Illuminate\Http\Request $request = null, int $status = 500): void
    {
        try {
            $userId = $request?->user()?->id;
            $requestData = null;

            if ($request !== null) {
                $safe = $request->except(['password', 'password_confirmation', 'token', '_token']);
                $requestData = array_slice($safe, 0, 50);
            }

            static::create([
                'user_id' => $userId,
                'exception_class' => get_class($e),
                'message' => mb_substr($e->getMessage(), 0, 1000),
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
                'route_name' => $request?->route()?->getName(),
                'stack_trace' => mb_substr($e->getTraceAsString(), 0, 5000),
                'request_data' => $requestData,
                'ip_address' => $request?->ip(),
                'user_agent' => mb_substr((string) ($request?->userAgent() ?? ''), 0, 500),
                'http_status' => $status,
                'is_resolved' => false,
            ]);
        } catch (\Throwable) {
            // Jangan throw dari dalam error logger
        }
    }
}
