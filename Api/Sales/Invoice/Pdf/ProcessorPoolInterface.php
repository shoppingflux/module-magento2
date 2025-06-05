<?php

namespace ShoppingFeed\Manager\Api\Sales\Invoice\Pdf;

interface ProcessorPoolInterface
{
    /**
     * @return ProcessorInterface[]
     */
    public function getProcessors();

    /**
     * @param string $code
     * @return ProcessorInterface|null
     */
    public function getProcessorByCode($code);
}
