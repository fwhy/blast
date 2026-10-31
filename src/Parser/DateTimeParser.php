<?php

namespace Fwhy\Blast\Parser;

class DateTimeParser
{
    /**
     * Parse various date representations (Unix timestamp, numeric string, ISO8601, YYYY-MM-DD, etc.)
     *
     * @param mixed $value
     * @return \DateTime|null
     */
    public function parse(mixed $value): ?\DateTime
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTime) {
            return $value;
        }

        if ($value instanceof \DateTimeImmutable) {
            return \DateTime::createFromImmutable($value);
        }

        // If numeric timestamp (int or numeric string)
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $dt = \DateTime::createFromFormat('U', (string)$value);
            return $dt !== false ? $dt : null;
        }

        if (is_string($value)) {
            $value = trim($value);

            // Try standard string formats
            $formats = [
                \DateTimeInterface::ATOM,
                \DateTimeInterface::ISO8601,
                \DateTimeInterface::RFC3339,
                \DateTimeInterface::RFC3339_EXTENDED,
                'Y-m-d H:i:s',
                'Y-m-d H:i',
                'Y-m-d',
                'Y/m/d H:i:s',
                'Y/m/d H:i',
                'Y/m/d',
            ];

            foreach ($formats as $format) {
                $dt = \DateTime::createFromFormat($format, $value);
                if ($dt !== false) {
                    return $dt;
                }
            }

            // Fallback to strtotime / DateTime constructor
            try {
                return new \DateTime($value);
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }
}
