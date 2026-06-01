<?php

declare(strict_types=1);

namespace K2gl\Slsa\V02;

use K2gl\Slsa\Internal\Json;

/**
 * SLSA Provenance v0.2 invocation.configSource: the source artifact that
 * triggered and configured the build (its URI, resolved digest and entry point).
 *
 * @see https://slsa.dev/provenance/v0.2#invocation
 */
final class ConfigSource
{
    /** @param array<string, string> $digest */
    public function __construct(
        public readonly ?string $uri = null,
        public readonly array $digest = [],
        public readonly ?string $entryPoint = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [];

        if ($this->uri !== null) {
            $out['uri'] = $this->uri;
        }

        if ($this->digest !== []) {
            $out['digest'] = $this->digest;
        }

        if ($this->entryPoint !== null) {
            $out['entryPoint'] = $this->entryPoint;
        }

        return $out;
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            uri: Json::stringOrNull($data, 'uri'),
            digest: Json::stringMapOrNull($data, 'digest') ?? [],
            entryPoint: Json::stringOrNull($data, 'entryPoint'),
        );
    }
}
