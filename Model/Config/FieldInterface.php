<?php

namespace ShoppingFeed\Manager\Model\Config;

use ShoppingFeed\Manager\Model\Config\Value\HandlerInterface as ValueHandlerInterface;

interface FieldInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return ValueHandlerInterface
     */
    public function getValueHandler();

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @return bool
     */
    public function isRequired();

    /**
     * @return mixed|null
     */
    public function getDefaultFormValue();

    /**
     * @return mixed|null
     */
    public function getDefaultUseValue();

    /**
     * @return int|null
     */
    public function getSortOrder();

    /**
     * @return array
     */
    public function getUiMetaConfig();

    /**
     * @param mixed $value
     * @return mixed
     */
    public function prepareRawValueForForm($value);

    /**
     * @param mixed $value
     * @return mixed
     */
    public function prepareRawValueForUse($value);

    /**
     * @param mixed $value
     * @return mixed
     */
    public function prepareFormValueForSave($value);
}
