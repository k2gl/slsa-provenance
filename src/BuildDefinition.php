<?php

declare(strict_types=1);

namespace K2gl\Slsa;

use K2gl\InToto\ResourceDescriptor;
use K2gl\Slsa\Exception\InvalidProvenanceException;
use K2gl\Slsa\Internal\Json;

/**
 * SLSA Provenance v1 BuildDefinition: the immutable inputs to the build.
 *
 * @see https://slsa.dev/spec/v1.0/provenance#builddefinition
 */
final class BuildDefinition
{
    /**
     * @param array<string, mixed>      $externalParameters
     * @param array<string, mixed>|null $internalParameters
     * @param list<ResourceDescriptor>  $resolvedDependencies
     */
    public function __construct(
        public readonly string $buildType,
        public readonly array $externalParameters = [],
        public readonly ?array $internalParameters = null,
        public readonly array $resolvedDependencies = [],
    ) {
        if ($buildType === '') {
            throw new InvalidProvenanceException('"buildType" must be a non-empty URI.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'buildType' => $this->buildType,
            'externalParameters' => Json::jsonObject($this->externalParameters),
        ];
        if ($this->internalParameters !== null) {
            $out['internalParameters'] = Json::jsonObject($this->internalParameters);
        }
        if ($this->resolvedDependencies !== []) {
            $out['resolvedDependencies'] = array_map(
                static fn (ResourceDescriptor $descriptor): array => $descriptor->toArray(),
                $this->resolvedDependencies,
            );
        }
        return $out;
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Json::requireString($data, 'buildType'),
            Json::object($data, 'externalParameters'),
            Json::objectOrNull($data, 'internalParameters'),
            Json::descriptors($data, 'resolvedDependencies'),
        );
    }
}
