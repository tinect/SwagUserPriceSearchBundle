<?php

namespace SwagUserPriceSearchBundle\SearchBundleDBAL\Condition;

use Assert\Assertion;
use Shopware\Bundle\SearchBundle\ConditionInterface;

class UserPriceCondition implements ConditionInterface
{
    /**
     * @var float
     */
    protected $minPrice;

    /**
     * @var float
     */
    protected $maxPrice;

    /**
     * @param float $minPrice
     * @param float $maxPrice
     *
     * @throws \Assert\AssertionFailedException
     */
    public function __construct($minPrice = 0.00, $maxPrice = 0.00)
    {
        Assertion::numeric($minPrice);
        Assertion::numeric($maxPrice);
        $this->minPrice = (float) $minPrice;
        $this->maxPrice = (float) $maxPrice;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'swag_search_bundle_user_price';
    }

    /**
     * @return float
     */
    public function getMinPrice()
    {
        return $this->minPrice;
    }

    /**
     * @return float
     */
    public function getMaxPrice()
    {
        return $this->maxPrice;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
