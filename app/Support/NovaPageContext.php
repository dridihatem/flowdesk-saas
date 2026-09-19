<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/**
 * Resolves the current FlowQil page + entity for NovaContext.
 */
final class NovaPageContext
{
    /**
     * Route parameter name => Nova entity type.
     *
     * @var array<string, string>
     */
    private const ENTITY_PARAMS = [
        'client' => 'client',
        'project' => 'project',
        'invoice' => 'invoice',
        'quote' => 'quote',
        'meeting' => 'meeting',
        'task' => 'task',
        'document' => 'document',
        'proposal' => 'quote',
    ];

    /**
     * @return array{page: string|null, entity: array{type: string, id: string}|null}
     */
    public static function fromRequest(?Request $request = null): array
    {
        $request ??= request();
        $route = $request->route();

        return [
            'page' => self::pageFromRoute($route),
            'entity' => self::entityFromRoute($route),
        ];
    }

    /**
     * Payload shape expected by Alpine / Echo clients.
     *
     * @return array{current_page: string|null, current_entity: array{type: string, id: string}|null}
     */
    public static function clientPayload(?Request $request = null): array
    {
        $resolved = self::fromRequest($request);

        return [
            'current_page' => $resolved['page'],
            'current_entity' => $resolved['entity'],
        ];
    }

    private static function pageFromRoute(?Route $route): ?string
    {
        if ($route === null) {
            return null;
        }

        $name = $route->getName();

        return is_string($name) && $name !== '' ? $name : null;
    }

    /**
     * @return array{type: string, id: string}|null
     */
    private static function entityFromRoute(?Route $route): ?array
    {
        if ($route === null) {
            return null;
        }

        foreach (self::ENTITY_PARAMS as $param => $type) {
            if (! $route->hasParameter($param)) {
                continue;
            }

            $value = $route->parameter($param);
            $id = self::normalizeEntityId($value);

            if ($id === null) {
                continue;
            }

            return ['type' => $type, 'id' => $id];
        }

        return null;
    }

    private static function normalizeEntityId(mixed $value): ?string
    {
        if ($value instanceof Model) {
            $key = $value->getKey();

            return $key !== null && $key !== '' ? (string) $key : null;
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_int($value)) {
            return (string) $value;
        }

        return null;
    }
}
