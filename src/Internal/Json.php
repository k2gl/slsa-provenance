<?php

declare(strict_types=1);

namespace K2gl\Slsa\Internal;

use K2gl\InToto\ResourceDescriptor;
use K2gl\Slsa\Exception\InvalidProvenanceException;

/**
 * Small, shared JSON-shape helpers for parsing and emitting the provenance
 * value objects with PHPStan-friendly, validated types.
 *
 * @internal
 */
final class Json
{
    /** @param array<mixed> $data */
    public static function requireString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (!is_string($value) || $value === '') {
            throw new InvalidProvenanceException(sprintf('Missing or empty "%s".', $key));
        }
        return $value;
    }

    /** @param array<mixed> $data */
    public static function stringOrNull(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            throw new InvalidProvenanceException(sprintf('"%s" must be a string.', $key));
        }
        return $value;
    }

    /**
     * @param array<mixed> $data
     * @return array<string, mixed>
     */
    public static function object(array $data, string $key): array
    {
        $value = $data[$key] ?? null;
        if ($value === null) {
            return [];
        }
        return self::asObject($value, $key);
    }

    /**
     * @param array<mixed> $data
     * @return array<string, mixed>
     */
    public static function requireObject(array $data, string $key): array
    {
        if (!isset($data[$key])) {
            throw new InvalidProvenanceException(sprintf('Missing "%s".', $key));
        }
        return self::asObject($data[$key], $key);
    }

    /**
     * @param array<mixed> $data
     * @return array<string, mixed>|null
     */
    public static function objectOrNull(array $data, string $key): ?array
    {
        $value = $data[$key] ?? null;
        if ($value === null) {
            return null;
        }
        return self::asObject($value, $key);
    }

    /**
     * @param array<mixed> $data
     * @return array<string, string>|null
     */
    public static function stringMapOrNull(array $data, string $key): ?array
    {
        $value = $data[$key] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_array($value)) {
            throw new InvalidProvenanceException(sprintf('"%s" must be an object of strings.', $key));
        }
        $map = [];
        foreach ($value as $name => $item) {
            if (!is_string($item)) {
                throw new InvalidProvenanceException(sprintf('"%s" values must be strings.', $key));
            }
            $map[(string) $name] = $item;
        }
        return $map;
    }

    /**
     * @param array<mixed> $data
     * @return list<ResourceDescriptor>
     */
    public static function descriptors(array $data, string $key): array
    {
        $value = $data[$key] ?? null;
        if ($value === null) {
            return [];
        }
        if (!is_array($value) || ($value !== [] && !array_is_list($value))) {
            throw new InvalidProvenanceException(sprintf('"%s" must be an array.', $key));
        }
        $descriptors = [];
        foreach ($value as $raw) {
            if (!is_array($raw)) {
                throw new InvalidProvenanceException(sprintf('Each item of "%s" must be an object.', $key));
            }
            $descriptors[] = ResourceDescriptor::fromArray($raw);
        }
        return $descriptors;
    }

    /**
     * Represent an associative array as a value that always serializes to a JSON
     * object — an empty array would otherwise become `[]` instead of `{}`.
     *
     * @param array<string, mixed> $value
     * @return array<string, mixed>|\stdClass
     */
    public static function jsonObject(array $value): array|\stdClass
    {
        return $value === [] ? new \stdClass() : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private static function asObject(mixed $value, string $key): array
    {
        if (!is_array($value)) {
            throw new InvalidProvenanceException(sprintf('"%s" must be an object.', $key));
        }
        if ($value !== [] && array_is_list($value)) {
            throw new InvalidProvenanceException(sprintf('"%s" must be an object, not an array.', $key));
        }
        $object = [];
        foreach ($value as $name => $item) {
            $object[(string) $name] = $item;
        }
        return $object;
    }
}
