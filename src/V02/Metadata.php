<?php

declare(strict_types=1);

namespace K2gl\Slsa\V02;

use K2gl\Slsa\Internal\Json;

/**
 * SLSA Provenance v0.2 metadata: optional details about this particular build
 * run — its identifier, timestamps, completeness claims and reproducibility.
 *
 * @see https://slsa.dev/provenance/v0.2#metadata
 */
final class Metadata
{
    public function __construct(
        public readonly ?string $buildInvocationId = null,
        public readonly ?string $buildStartedOn = null,
        public readonly ?string $buildFinishedOn = null,
        public readonly ?Completeness $completeness = null,
        public readonly ?bool $reproducible = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [];

        if ($this->buildInvocationId !== null) {
            $out['buildInvocationId'] = $this->buildInvocationId;
        }

        if ($this->buildStartedOn !== null) {
            $out['buildStartedOn'] = $this->buildStartedOn;
        }

        if ($this->buildFinishedOn !== null) {
            $out['buildFinishedOn'] = $this->buildFinishedOn;
        }

        if ($this->completeness !== null) {
            $out['completeness'] = Json::jsonObject($this->completeness->toArray());
        }

        if ($this->reproducible !== null) {
            $out['reproducible'] = $this->reproducible;
        }

        return $out;
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        $completeness = Json::objectOrNull($data, 'completeness');

        return new self(
            buildInvocationId: Json::stringOrNull($data, 'buildInvocationId'),
            buildStartedOn: Json::stringOrNull($data, 'buildStartedOn'),
            buildFinishedOn: Json::stringOrNull($data, 'buildFinishedOn'),
            completeness: $completeness === null ? null : Completeness::fromArray($completeness),
            reproducible: Json::boolOrNull($data, 'reproducible'),
        );
    }
}
