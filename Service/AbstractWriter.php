<?php

namespace Xola\ReportWriterBundle\Service;

use Symfony\Component\DependencyInjection\Container;
use Psr\Log\LoggerInterface;

abstract class AbstractWriter
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Write the formatted order data to disk, so we can fetch it later
     *
     * @param array $data An array of data that will cached online per row
     */
    public function cacheData($filename, $data)
    {
        $line = '';
        foreach ($data as $row) {
            $line .= json_encode($row) . "\n";
        }
        file_put_contents($filename, $line, FILE_APPEND);
    }

    /**
     * Go through the order data and prepare an updated list of headers
     *
     * @param $data
     *
     * @return array
     */
    public function parseHeaders($data, $existingHeaders = [])
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // This is a multi-row header
                $value = array_keys($value);
                $loc = $this->findNestedHeader($key, $existingHeaders);
                if ($loc !== false) {
                    // Merge data headers values with pre-existing data
                    $value = array_unique(array_merge($value, $existingHeaders[$loc][$key]));
                    $existingHeaders[$loc] = [$key => $value];
                } else {
                    $existingHeaders[] = [$key => $value];
                }

            } else {
                // Standard header add it if it does not exist
                if (!in_array($key, $existingHeaders)) {
                    $existingHeaders[] = $key;
                }
            }
        }

        return $existingHeaders;
    }


    /**
     * Find the location of a nested/multi-row header from our list
     *
     * @param string $key
     * @param array $existingHeaders
     *
     * @return bool|int FALSE if it is not found, else location of the header within the array
     */
    private function findNestedHeader($key, $existingHeaders)
    {
        $found = false;
        foreach ($existingHeaders as $idx => $value) {
            if (is_array($value) && isset($value[$key])) {
                return $idx;
            }
        }

        return $found;
    }

    abstract public function prepare($cacheFile, $sortedHeaders);
}