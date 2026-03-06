<?php

namespace ShoppingFeed\Manager\Model\Sales\Invoice\Pdf\Processor;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\InvoiceInterface;
use ShoppingFeed\Manager\Api\Sales\Invoice\Pdf\ProcessorInterface;

class FoomanPdfCustomiser implements ProcessorInterface
{
    /**
     * @var \Fooman\PdfCore\Model\PdfRenderer|null
     */
    private $pdfRenderer;

    /**
     * @var \Fooman\PdfCustomiser\Block\InvoiceFactory|null
     */
    private $invoiceDocumentFactory;

    public function __construct()
    {
        try {
            $objectManager = ObjectManager::getInstance();
            $this->pdfRenderer = $objectManager->get('\Fooman\PdfCore\Model\PdfRenderer');
            $this->invoiceDocumentFactory = $objectManager->get('\Fooman\PdfCustomiser\Block\InvoiceFactory');
        } catch (\Exception $e) {
            $this->pdfRenderer = null;
            $this->invoiceDocumentFactory = null;
        }
    }

    public function getCode()
    {
        return 'fooman_pdf_customiser';
    }

    public function getLabel()
    {
        return __('Fooman PDF Customiser');
    }

    public function isAvailable()
    {
        return (null !== $this->pdfRenderer) && (null !== $this->invoiceDocumentFactory);
    }

    public function getInvoicePdfContent(InvoiceInterface $invoice)
    {
        if ((null === $this->pdfRenderer) || (null === $this->invoiceDocumentFactory)) {
            throw new LocalizedException(__('Fooman PDF Customiser module is not installed.'));
        }
        
        $document = $this->invoiceDocumentFactory->create(
            [
                'data' => [
                    'invoice' => $invoice,
                ],
            ]
        );

        $this->pdfRenderer->addDocument($document);

        try {
            return $this->pdfRenderer->getPdfAsString();
        } catch (\Exception $e) {
            throw new LocalizedException(__('Failed to generate PDF: %1', $e->getMessage()));
        }
    }
}
