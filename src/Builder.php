<?php

declare(strict_types=1);

namespace K2gl\Slsa;

use K2gl\InToto\ResourceDescriptor;
use K2gl\Slsa\Exception\InvalidProvenanceException;
use K2gl\Slsa\Internal\Json;

/**
 * SLSA Provenance v1 Builder: identifies the platform that ran the build.
 *
 * @see https://slsa.dev/spec/v1.0/provenance#builder
 */
final class Builder
{
    /**
     * @param list<ResourceDescriptor>  $builderDependencies
     * @param array<string, string>|null $version
     */
    public function __construct(
        public readonly string $id,
        public readonly array $builderDependencies = [],
        public readonly ?array $version = null,
    ) {
        if ($id === '') {
            throw new InvalidProvenanceException('Builder "id" must be a non-empty URI.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = ['id' => $this->id];

        if ($this->builderDependencies !== []) {
            $out['builderDependencies'] = array_map(
                static fn (ResourceDescriptor $descriptor): array => $descriptor->toArray(),
                $this->builderDependencies,
            );
        }

        if ($this->version !== null) {
            $out['version'] = Json::jsonObject($this->version);
        }

        return $out;
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Json::requireString($data, 'id'),
            Json::descriptors($data, 'builderDependencies'),
            Json::stringMapOrNull($data, 'version'),
        );
    }
}
