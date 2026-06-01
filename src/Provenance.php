<?php

declare(strict_types=1);

namespace K2gl\Slsa;

use K2gl\InToto\ResourceDescriptor;
use K2gl\InToto\Statement;
use K2gl\Slsa\Exception\InvalidProvenanceException;
use K2gl\Slsa\Internal\Json;

/**
 * A SLSA Provenance v1 predicate: how an artifact was produced. It is carried as
 * the predicate of an in-toto Statement with predicate type
 * "https://slsa.dev/provenance/v1".
 *
 * @see https://slsa.dev/spec/v1.0/provenance
 */
final class Provenance
{
    public const PREDICATE_TYPE = 'https://slsa.dev/provenance/v1';

    public function __construct(
        public readonly BuildDefinition $buildDefinition,
        public readonly RunDetails $runDetails,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'buildDefinition' => $this->buildDefinition->toArray(),
            'runDetails' => $this->runDetails->toArray(),
        ];
    }

    /**
     * Wrap this provenance as the predicate of an in-toto Statement over the
     * given subjects, ready to sign with k2gl/dsse.
     *
     * @param list<ResourceDescriptor> $subject
     */
    public function toStatement(array $subject): Statement
    {
        return new Statement($subject, self::PREDICATE_TYPE, $this->toArray());
    }

    /** Parse the SLSA provenance predicate out of an in-toto Statement. */
    public static function fromStatement(Statement $statement): self
    {
        if ($statement->predicateType !== self::PREDICATE_TYPE) {
            throw new InvalidProvenanceException(sprintf(
                'Statement predicateType is "%s", expected "%s".',
                $statement->predicateType,
                self::PREDICATE_TYPE,
            ));
        }

        return self::fromArray($statement->predicate);
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            BuildDefinition::fromArray(Json::requireObject($data, 'buildDefinition')),
            RunDetails::fromArray(Json::requireObject($data, 'runDetails')),
        );
    }
}
