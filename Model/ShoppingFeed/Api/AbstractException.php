<?php

namespace ShoppingFeed\Manager\Model\ShoppingFeed\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;


abstract class AbstractException extends LocalizedException
{
    /**
     * @param string $errorReason
     */
    public function __construct($errorReason)
    {
        $basePhrase = $this->getBasePhrase()->render();

        if (empty($errorReason)) {
            $fullPhrase = __('A request to Shopping Feed failed: %1', $basePhrase);
        } else {
            $fullPhrase = __('A request to Shopping Feed failed: %1 (reason: "%2")', $basePhrase, $errorReason);
        }

        parent::__construct($fullPhrase);
    }

    /**
     * @return Phrase
     */
    abstract protected function getBasePhrase();
}
