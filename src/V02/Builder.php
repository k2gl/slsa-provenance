<?php

declare(strict_types=1);

namespace K2gl\Slsa\V02;

use K2gl\Slsa\Exception\InvalidProvenanceException;
use K2gl\Slsa\Internal\Json;

/**
 * SLSA Provenance v0.2 builder: identifies the entity that ran the build. In
 * v0.2 the builder carries only an id (unlike the richer v1 builder).
 *
 * @see https://slsa.dev/provenance/v0.2#builder
 */
final class Builder
{
    public function __construct(
        public readonly string $id,
    ) {
        if ($id === '') {
            throw new InvalidProvenanceException('Builder "id" must be a non-empty URI.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['id' => $this->id];
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Json::requireString($data, 'id'));
    }
}
