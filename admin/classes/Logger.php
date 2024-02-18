<?php
class Logger
{
    public static function log($message)
    {
        $formattedMessage = '';

        if (is_array($message)) {
            $formattedMessage .= "Array:\n";
            foreach ($message as $key => $value) {
                if (is_array($value)) {
                    $formattedMessage .= "Sub-Array for $key:\n";
                    foreach ($value as $k => $v) {
                        $formattedMessage .= "  $k: $v\n";
                    }
                    continue;
                } elseif (is_object($value)) {
                    $formattedMessage .= "  $key: Object\n";
                    continue;
                } elseif (is_null($value)) {
                    $formattedMessage .= "  $key: NULL\n";
                    continue;
                } else {
                    $formattedMessage .= "  $key: $value\n";
                    continue;
                }
            }
        } else {
            $formattedMessage = $message;
        }

        // Append the formatted message to a custom log file
        file_put_contents(__DIR__ . '/../error.log', $formattedMessage . PHP_EOL, FILE_APPEND);
    }
}