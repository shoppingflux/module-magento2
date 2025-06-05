<?php

namespace ShoppingFeed\Manager\Api\Sales\Invoice\Pdf;

use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\InvoiceInterface;

/**
 * @api
 */
interface ProcessorInterface
{
    /**
     * @return string
     */
    public function getCode();

    /**
     * @return Phrase|string
     */
    public function getLabel();

    /**
     * @return bool
     */
    public function isAvailable();

    /**
     * @param InvoiceInterface $invoice
     * @return string
     */
    public function getInvoicePdfContent(InvoiceInterface $invoice);
}