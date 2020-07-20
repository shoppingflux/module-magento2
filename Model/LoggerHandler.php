<?php

namespace ShoppingFeed\Manager\Model;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base as BaseHandler;

class LoggerHandler extends BaseHandler
{
    /**
     * @param DriverInterface $filesystem
     * @param string $fileName
     */
    public function __construct(DriverInterface $filesystem, $fileName)
    {
        parent::__construct($filesystem, BP . '/var/log/' . $this->sanitizeFileName($fileName));
    }

    /**
     * @param mixed $fileName
     * @return string
     */
    private function sanitizeFileName($fileName)
    {
        if (!is_string($fileName)) {
            throw  new \InvalidArgumentException('File name must be a string.');
        }

        $parts = explode('/', $fileName);

        $parts = array_filter(
            $parts,
            function ($value) {
                return !in_array($value, [ '', '.', '..' ], true);
            }
        );

        return implode('/', $parts);
    }
}
