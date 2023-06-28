<?php

namespace ShoppingFeed\Manager\Model\Config\Value\Handler;

use Magento\Framework\Validator\EmailAddress as EmailAddressValidator;

class Email extends Text
{
    const TYPE_CODE = 'email';

    /**
     * @var EmailAddressValidator
     */
    private $emailAddressValidator;

    /**
     * @param EmailAddressValidator $emailAddressValidator
     */
    public function __construct(EmailAddressValidator $emailAddressValidator)
    {
        $this->emailAddressValidator = $emailAddressValidator;
    }

    public function getFieldValidationClasses()
    {
        return array_merge(parent::getFieldValidationClasses(), [ self::VALIDATION_CLASS_EMAIL ]);
    }

    public function isValidValue($value, $isRequired)
    {
        return parent::isValidValue($value, $isRequired)
            && (($value === '') || $this->emailAddressValidator->isValid($value));
    }
}
