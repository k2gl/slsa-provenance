<?php

declare(strict_types=1);

namespace K2gl\Slsa;

use K2gl\Slsa\Exception\InvalidProvenanceException;
use K2gl\Slsa\Internal\Json;

/**
 * The identity of the verifier that produced a {@see VerificationSummary} — its
 * URI id and, optionally, the versions of the components it ran.
 *
 * @see https://slsa.dev/spec/v1.0/verification_summary#verifier
 */
final class Verifier
{
    /** @param array<string, string>|null $version */
    public function __construct(
        public readonly string $id,
        public readonly ?array $version = null,
    ) {
        if ($id === '') {
            throw new InvalidProvenanceException('"verifier.id" must be a non-empty URI.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = ['id' => $this->id];

        if ($this->version !== null) {
            $out['version'] = Json::jsonObject($this->version);
        }

        return $out;
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Json::requireString($data, 'id'),
            version: Json::stringMapOrNull($data, 'version'),
        );
    }
}
