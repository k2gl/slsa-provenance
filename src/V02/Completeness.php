<?php

declare(strict_types=1);

namespace K2gl\Slsa\V02;

use K2gl\Slsa\Internal\Json;

/**
 * SLSA Provenance v0.2 metadata.completeness: which parts of the provenance the
 * builder claims to be complete (i.e. that no relevant information is missing).
 *
 * @see https://slsa.dev/provenance/v0.2#metadata
 */
final class Completeness
{
    public function __construct(
        public readonly ?bool $parameters = null,
        public readonly ?bool $environment = null,
        public readonly ?bool $materials = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [];

        if ($this->parameters !== null) {
            $out['parameters'] = $this->parameters;
        }

        if ($this->environment !== null) {
            $out['environment'] = $this->environment;
        }

        if ($this->materials !== null) {
            $out['materials'] = $this->materials;
        }

        return $out;
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            parameters: Json::boolOrNull($data, 'parameters'),
            environment: Json::boolOrNull($data, 'environment'),
            materials: Json::boolOrNull($data, 'materials'),
        );
    }
}
