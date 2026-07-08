<?php

namespace CpmsCommon\Log\Formatter;

use CpmsCommon\Log\LogDataAwareInterface;
use CpmsCommon\Log\LogDataAwareTrait;
use DateTime;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

/**
 * DVSA pipe-delimited log formatter for Monolog.
 *
 * Preserves the original ||‑separated field format while accepting
 * a Monolog LogRecord instead of the old Laminas log event array.
 */
class DvsaLogFormatter implements FormatterInterface, LogDataAwareInterface
{
    use LogDataAwareTrait;

    private string $dateTimeFormat;

    /**
     * @param string|null $dateTimeFormat Defaults to \DateTime::ATOM
     */
    public function __construct(?string $dateTimeFormat = null)
    {
        $this->dateTimeFormat = $dateTimeFormat ?? \DateTime::ATOM;
    }

    /**
     * Format a single log record into the pipe-delimited string.
     */
    public function format(LogRecord $record): string
    {
        $replacements = $this->getReplacementValues($record);

        $output = $this->getFormat();

        foreach ($replacements as $name => $value) {
            if ($value !== null) {
                $output = str_replace("%$name%", (string) $value, $output);
            }
        }

        // Clear any remaining un-replaced placeholders
        $output = (string) preg_replace('/%\w+%/', '', $output);

        $this->getLogData()->resetData();

        return $output . PHP_EOL;
    }

    /**
     * Format a batch of records.
     */
    public function formatBatch(array $records): string
    {
        $formatted = '';
        foreach ($records as $record) {
            $formatted .= $this->format($record);
        }
        return $formatted;
    }

    /**
     * Pipe-delimited format template — fields mirror the original Laminas formatter.
     */
    private function getFormat(): string
    {
        return '%timestamp%||%priority%||%priorityName%||%entryType%||%userId%||%openAmToken%||%accessToken%||' .
            '%correlationId%||%classMethod%||%message%||%extra%||%exceptionType%||%exceptionCode%||%exceptionMessage%||' .
            '%stackTrace%';
    }

    /**
     * Build a flat replacement map from the Monolog record and the current LogData.
     *
     * @return array<string, mixed>
     */
    private function getReplacementValues(LogRecord $record): array
    {
        $dateObject = new DateTime();
        $date = $dateObject->format($this->dateTimeFormat);

        // Strip internal Monolog metadata from context before serialising as %extra%
        $context = $record->context;
        unset($context['__dvsa_metadata__']);
        $extraStr = !empty($context) ? json_encode($context) : '';

        $replacements = $this->logData ? $this->logData->toArray() : [];

        // Overlay record-level fields (always present, not overrideable by LogData)
        $replacements['timestamp']    = $date;
        $replacements['priority']     = $record->level->value;
        $replacements['priorityName'] = $record->level->name;
        $replacements['message']      = $record->message;
        $replacements['extra']        = $extraStr;

        return $replacements;
    }
}
