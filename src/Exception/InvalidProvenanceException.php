<?php

declare(strict_types=1);

namespace K2gl\Slsa\Exception;

/**
 * Thrown when a provenance predicate cannot be built or parsed: missing or
 * wrongly typed fields, or a Statement whose predicateType is not SLSA
 * Provenance v1.
 */
final class InvalidProvenanceException extends \RuntimeException implements SlsaException
{
}
