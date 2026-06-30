<?php

declare(strict_types=1);

namespace K2gl\Slsa;

use K2gl\InToto\PredicateRegistry;
use K2gl\Slsa\V02\Provenance as ProvenanceV02;

/**
 * Registers the SLSA predicate types with an in-toto {@see PredicateRegistry} so
 * that {@see \K2gl\InToto\Statement::predicate()} resolves a SLSA Provenance
 * statement to a typed {@see Provenance} (v1) or {@see ProvenanceV02} (v0.2).
 */
final class Predicates
{
    /** Register SLSA Provenance v1 and v0.2 in the given registry (or the shared default). */
    public static function register(?PredicateRegistry $registry = null): void
    {
        $registry ??= PredicateRegistry::default();
        $registry->register(Provenance::PREDICATE_TYPE, Provenance::fromArray(...));
        $registry->register(ProvenanceV02::PREDICATE_TYPE, ProvenanceV02::fromArray(...));
    }
}
