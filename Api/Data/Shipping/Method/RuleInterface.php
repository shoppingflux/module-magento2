<?php

namespace ShoppingFeed\Manager\Api\Data\Shipping\Method;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Rule\Model\Condition\Combine as CombinedCondition;
use ShoppingFeed\Manager\Model\Shipping\Method\ApplierInterface;


/**
 * @api
 */
interface RuleInterface
{
    /**#@-*/
    const RULE_ID = 'rule_id';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const IS_ACTIVE = 'is_active';
    const FROM_DATE = 'from_date';
    const TO_DATE = 'to_date';
    const SORT_ORDER = 'sort_order';
    const CONDITIONS_SERIALIZED = 'conditions_serialized';
    const APPLIER_CODE = 'applier_code';
    const APPLIER_CONFIGURATION = 'applier_configuration';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    /**#@-*/

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @return bool
     */
    public function isActive();

    /**
     * @return string|null
     */
    public function getFromDate();

    /**
     * @return string|null
     */
    public function getToDate();

    /**
     * @return int
     */
    public function getSortOrder();

    /**
     * @return CombinedCondition
     */
    public function getConditions();

    /**
     * @return CombinedCondition
     */
    public function getConditionsInstance();

    /**
     * @return string|null
     */
    public function getConditionsSerialized();

    /**
     * @return string
     */
    public function getApplierCode();

    /**
     * @return DataObject
     */
    public function getApplierConfiguration();

    /**
     * @return ApplierInterface
     * @throws LocalizedException
     */
    public function getApplier();

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @param string $name
     * @return RuleInterface
     */
    public function setName($name);

    /**
     * @param string $description
     * @return RuleInterface
     */
    public function setDescription($description);

    /**
     * @param bool $isActive
     * @return RuleInterface
     */
    public function setIsActive($isActive);

    /**
     * @param string|null $fromDate
     * @return RuleInterface
     */
    public function setFromDate($fromDate);

    /**
     * @param string|null $toDate
     * @return RuleInterface
     */
    public function setToDate($toDate);

    /**
     * @param int $sortOrder
     * @return RuleInterface
     */
    public function setSortOrder($sortOrder);

    /**
     * @param array $rawConditions
     * @return RuleInterface
     */
    public function setRawConditions(array $rawConditions);

    /**
     * @param CombinedCondition $conditions
     * @return RuleInterface
     */
    public function setConditions($conditions);

    /**
     * @param string|null $conditions
     * @return RuleInterface
     */
    public function setConditionsSerialized($conditions);

    /**
     * @param string $code
     * @param array $configData
     * @return RuleInterface
     */
    public function setApplier($code, array $configData);

    /**
     * @param string $createdAt
     * @return RuleInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * @param string $updatedAt
     * @return RuleInterface
     */
    public function setUpdatedAt($updatedAt);
}
