<?php

namespace SwagUserPriceSearchBundle\SearchBundleDBAL\Facet;

use Doctrine\DBAL\Connection;
use Enlight_Components_Session_Namespace;
use PDO;
use Shopware\Bundle\SearchBundle\Criteria;
use Shopware\Bundle\SearchBundle\Facet\PriceFacet;
use Shopware\Bundle\SearchBundle\FacetInterface;
use Shopware\Bundle\SearchBundle\FacetResult\RangeFacetResult;
use Shopware\Bundle\SearchBundleDBAL\PartialFacetHandlerInterface;
use Shopware\Bundle\SearchBundleDBAL\QueryBuilderFactory;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use Shopware\Components\QueryAliasMapper;
use Shopware\SwagUserPrice\Bundle\SearchBundleDBAL\PriceHelper;
use Shopware_Components_Config;
use Shopware_Components_Snippet_Manager;
use SwagUserPriceSearchBundle\SearchBundleDBAL\Condition\UserPriceCondition;

class UserPriceFacetHandler implements PartialFacetHandlerInterface
{
    /**
     * @var QueryBuilderFactory
     */
    private $queryBuilderFactory;

    /**
     * @var Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @var Shopware_Components_Config
     */
    private $config;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    private $minFieldName;
    private $maxFieldName;

    /**
     * @param QueryBuilderFactory                 $queryBuilderFactory
     * @param Shopware_Components_Snippet_Manager $snippetManager
     * @param Shopware_Components_Config          $config
     * @param Connection                          $connection
     * @param QueryAliasMapper                    $queryAliasMapper
     */
    public function __construct(
        QueryBuilderFactory $queryBuilderFactory,
        Shopware_Components_Snippet_Manager $snippetManager,
        Shopware_Components_Config $config,
        Connection $connection,
        QueryAliasMapper $queryAliasMapper
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->snippetManager = $snippetManager;
        $this->config = $config;
        $this->connection = $connection;

        if (!$this->minFieldName = $queryAliasMapper->getShortAlias('min')) {
            $this->minFieldName = 'min';
        }

        if (!$this->maxFieldName = $queryAliasMapper->getShortAlias('max')) {
            $this->maxFieldName = 'max';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFacet(FacetInterface $facet)
    {
        if ($facet instanceof UserPriceFacet) {
            return true;
        }

        if ($facet instanceof PriceFacet) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function generatePartialFacet(
        FacetInterface $facet,
        Criteria $reverted,
        Criteria $criteria,
        ShopContextInterface $context
    ) {
        //resets all conditions of the criteria to execute a facet query without user filters.
        $queryCriteria = clone $criteria;
        $queryCriteria->resetConditions();
        $queryCriteria->resetSorting();

        $this->session = Shopware()->Session();
        
        if (!$this->session->offsetGet('sUserId')) {
            $this->session->offsetSet('sUserId', 0);
        }
        
        $query = $this->queryBuilderFactory->createQuery($queryCriteria, $context);
        $pricehelper = new PriceHelper(Shopware()->Container()->get('shopware_searchdbal.search_price_helper_dbal'),
            $this->config, $this->connection, $this->session);
        $pricehelper->buildQuery($query, 'customerPrice1',
            [':currentCustomerGroup', $context->getCurrentCustomerGroup()->getKey()]);

        $ignoredPrice = $this->config->getByNamespace('SwagUserPriceSearchBundle', 'ignoredPrice', 0);
        $basequery = $query->andWhere('price<>' . $ignoredPrice)->select(['MIN(price) as min', 'MAX(price) as max'])->execute()->fetch(PDO::FETCH_ASSOC);

        $min = (float) $basequery['min'];
        $max = (float) $basequery['max'];

        $currencyFactor = $context->getCurrency()->getFactor();
        $min *= $currencyFactor;
        $max *= $currencyFactor;

        $activeMin = 0;
        $activeMax = 0;

        /** @var UserPriceCondition $condition */
        if ($condition = $criteria->getCondition($facet->getName())) {
            $activeMin = $condition->getMinPrice();
            $activeMax = $condition->getMaxPrice();
        }

        if (!$activeMin) {
            $activeMin = $min;
        }
        if (!$activeMax) {
            $activeMax = $max;
        }

        if ($min === $max) {
            return null;
        }

        if ($facet->getName() === 'price') {
            return null;
        }

        return new RangeFacetResult(
            $facet->getName(),
            $criteria->hasCondition($facet->getName()),
            $this->snippetManager->getNamespace('frontend/detail/data')->get('DetailDataColumnPrice'),
            $min,
            $max,
            (float) $activeMin,
            (float) $activeMax,
            $this->minFieldName,
            $this->maxFieldName,
            [],
            null,
            2,
            'frontend/listing/filter/facet-currency-range.tpl'
        );
    }
}
