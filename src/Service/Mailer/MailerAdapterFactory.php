<?php

namespace App\Service\Mailer;

class MailerAdapterFactory
{
    /**
     * @param MailerAdapterInterface[] $adapters
     */
    public static function create(iterable $adapters, string $adapterType): MailerAdapterInterface
    {
        $adapterMap = [];
        foreach ($adapters as $adapter) {
            $classParts = explode('\\', get_class($adapter));
            $className = end($classParts);
            $key = str_replace('MailerAdapter', '', $className);
            $adapterMap[strtolower($key)] = $adapter;
        }

        $type = strtolower($adapterType);

        if (isset($adapterMap[$type])) {
            return $adapterMap[$type];
        }

        // Fallback to FileMailerAdapter
        if (isset($adapterMap['file'])) {
            return $adapterMap['file'];
        }

        throw new \RuntimeException(sprintf('No mailer adapter found for type "%s"', $adapterType));
    }
}