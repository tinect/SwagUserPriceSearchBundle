<?php

namespace SwagUserPriceSearchBundle\SearchBundleDBAL;

use Enlight_Controller_Request_RequestHttp as Request;
use Shopware\Bundle\SearchBundle\Criteria;
use Shopware\Bundle\SearchBundle\CriteriaRequestHandlerInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use SwagUserPriceSearchBundle\SearchBundleDBAL\Condition\UserPriceCondition;
use SwagUserPriceSearchBundle\SearchBundleDBAL\Facet\UserPriceFacet;

class CriteriaRequestHandler implements CriteriaRequestHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handleRequest(
        Request $request,
        Criteria $criteria,
        ShopContextInterface $context
    ) {
        if ($request->has('userpricemin') || $request->has('userpricemax') || $request->has('min') || $request->has('max')) {
            $min = $request->getParam('userpricemin');
            $max = $request->getParam('userpricemax');

            if (!$min) {
                $min = $request->getParam('min');
            }

            if (!$max) {
                $max = $request->getParam('max');
            }

            if (!$min && !$max) {
                return;
            }

            $criteria->addCondition(
                new UserPriceCondition((float) $min, (float) $max)
            );
        }

        $criteria->addFacet(new UserPriceFacet());
    }
}
