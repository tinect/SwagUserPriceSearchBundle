<?php

namespace SwagUserPriceSearchBundle\SearchBundleDBAL\Condition;

use Doctrine\DBAL\Connection;
use Shopware\Bundle\SearchBundle\ConditionInterface;
use Shopware\Bundle\SearchBundle\Criteria;
use Shopware\Bundle\SearchBundleDBAL\ConditionHandlerInterface;
use Shopware\Bundle\SearchBundleDBAL\QueryBuilder;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;

class UserPriceConditionHandler implements ConditionHandlerInterface
{
    const STATE_USERPRICE_INCLUDED = 'userprice_include';
    /**
     * @var \Shopware_Components_Config
     */
    private $config;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var \Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * @var Criteria
     */
    private $criteria;

    /**
     * UserPriceConditionHandler constructor.
     *
     * @param \Shopware_Components_Config           $config
     * @param Connection                            $connection
     * @param \Enlight_Components_Session_Namespace $session
     */
    public function __construct(
        \Shopware_Components_Config $config,
        Connection $connection
    ) {
        $this->config = $config;
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsCondition(ConditionInterface $condition)
    {
        if (($condition instanceof UserPriceCondition)) {
            return true;
        } elseif (($condition instanceof \Shopware\Bundle\SearchBundle\Condition\PriceCondition)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function generateCondition(
        ConditionInterface $condition,
        QueryBuilder $query,
        ShopContextInterface $context
    ) {
        if (!$query->hasState(self::STATE_USERPRICE_INCLUDED)) {
            $this->session = Shopware()->Session();
            $query->addState(self::STATE_USERPRICE_INCLUDED);

            $pricehelper = new \Shopware\SwagUserPrice\Bundle\SearchBundleDBAL\PriceHelper(Shopware()->Container()->get('shopware_searchdbal.search_price_helper_dbal'),
                $this->config, $this->connection, $this->session);
            $pricehelper->joinAvailableVariant($query);
            $pricehelper->buildQuery($query, 'customerPrice1',
                [':currentCustomerGroup', $context->getCurrentCustomerGroup()->getKey()]);

            $suffix = md5(json_encode($condition));

            $minKey = ':priceMin' . $suffix;
            $maxKey = ':priceMax' . $suffix;

            $currencyFactor = $context->getCurrency()->getFactor();

            /** @var UserPriceCondition $condition */
            if ($condition->getMaxPrice() > 0 && $condition->getMinPrice() > 0) {
                $query->andWhere('customerPrice1.price BETWEEN ' . $minKey . ' AND ' . $maxKey);
                $query->setParameter($minKey, $condition->getMinPrice() / $currencyFactor);
                $query->setParameter($maxKey, $condition->getMaxPrice() / $currencyFactor);

                return;
            }
            if ($condition->getMaxPrice() > 0) {
                $query->andWhere('customerPrice1.price <= ' . $maxKey);
                $query->setParameter($maxKey, $condition->getMaxPrice() / $currencyFactor);

                return;
            }

            if ($condition->getMinPrice() > 0) {
                $query->andWhere('customerPrice1.price >= ' . $minKey);
                $query->setParameter($minKey, $condition->getMinPrice() / $currencyFactor);

                return;
            }
        }
    }

    public function setCriteria(Criteria $criteria)
    {
        $this->criteria = $criteria;
    }
}
