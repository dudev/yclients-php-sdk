<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Exception;

/**
 * A DTO's `fromArray()` (REST response or webhook `data`, same code either way) got a payload
 * missing a field the DTO treats as always-present — thrown instead of silently defaulting to a
 * placeholder (`0`, epoch, a specific enum case) that would otherwise look like real data. Only
 * covers fields where that placeholder would be indistinguishable from a genuine value; fields
 * typed nullable in the DTO stay permissive on purpose (their absence *is* meaningful).
 */
class YclientsMalformedPayloadException extends YclientsException
{
    public function __construct(
        public readonly string $dtoClass,
        public readonly string $field,
    ) {
        parent::__construct(sprintf(
            'Cannot build %s: required field "%s" is missing or has an unexpected type',
            $this->dtoClass,
            $this->field,
        ));
    }
}
