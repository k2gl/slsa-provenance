<?php

declare(strict_types=1);

namespace K2gl\Slsa\V02;

use K2gl\Slsa\Internal\Json;

/**
 * SLSA Provenance v0.2 invocation: identifies the event that kicked off the
 * build, together with the external parameters and environment it ran with.
 *
 * @see https://slsa.dev/provenance/v0.2#invocation
 */
final class Invocation
{
    /**
     * @param array<string, mixed>|null $parameters
     * @param array<string, mixed>|null $environment
     */
    public function __construct(
        public readonly ?ConfigSource $configSource = null,
        public readonly ?array $parameters = null,
        public readonly ?array $environment = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [];

        if ($this->configSource !== null) {
            $out['configSource'] = Json::jsonObject($this->configSource->toArray());
        }

        if ($this->parameters !== null) {
            $out['parameters'] = Json::jsonObject($this->parameters);
        }

        if ($this->environment !== null) {
            $out['environment'] = Json::jsonObject($this->environment);
        }

        return $out;
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        $configSource = Json::objectOrNull($data, 'configSource');

        return new self(
            configSource: $configSource === null ? null : ConfigSource::fromArray($configSource),
            parameters: Json::objectOrNull($data, 'parameters'),
            environment: Json::objectOrNull($data, 'environment'),
        );
    }
}
