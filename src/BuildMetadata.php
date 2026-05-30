<?php

declare(strict_types=1);

namespace K2gl\Slsa;

use K2gl\Slsa\Internal\Json;

/**
 * SLSA Provenance v1 BuildMetadata: optional details about a specific build run.
 *
 * @see https://slsa.dev/spec/v1.0/provenance#buildmetadata
 */
final class BuildMetadata
{
    public function __construct(
        public readonly ?string $invocationId = null,
        public readonly ?string $startedOn = null,
        public readonly ?string $finishedOn = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [];
        if ($this->invocationId !== null) {
            $out['invocationId'] = $this->invocationId;
        }
        if ($this->startedOn !== null) {
            $out['startedOn'] = $this->startedOn;
        }
        if ($this->finishedOn !== null) {
            $out['finishedOn'] = $this->finishedOn;
        }
        return $out;
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Json::stringOrNull($data, 'invocationId'),
            Json::stringOrNull($data, 'startedOn'),
            Json::stringOrNull($data, 'finishedOn'),
        );
    }
}
