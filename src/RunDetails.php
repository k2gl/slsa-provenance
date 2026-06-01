<?php

declare(strict_types=1);

namespace K2gl\Slsa;

use K2gl\InToto\ResourceDescriptor;
use K2gl\Slsa\Internal\Json;

/**
 * SLSA Provenance v1 RunDetails: details about this particular execution of the
 * build.
 *
 * @see https://slsa.dev/spec/v1.0/provenance#rundetails
 */
final class RunDetails
{
    /** @param list<ResourceDescriptor> $byproducts */
    public function __construct(
        public readonly Builder $builder,
        public readonly ?BuildMetadata $metadata = null,
        public readonly array $byproducts = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = ['builder' => $this->builder->toArray()];

        if ($this->metadata !== null) {
            $out['metadata'] = Json::jsonObject($this->metadata->toArray());
        }

        if ($this->byproducts !== []) {
            $out['byproducts'] = array_map(
                static fn (ResourceDescriptor $descriptor): array => $descriptor->toArray(),
                $this->byproducts,
            );
        }

        return $out;
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        $metadata = Json::objectOrNull($data, 'metadata');

        return new self(
            Builder::fromArray(Json::requireObject($data, 'builder')),
            $metadata === null ? null : BuildMetadata::fromArray($metadata),
            Json::descriptors($data, 'byproducts'),
        );
    }
}
