<?php

namespace SwagUserPriceSearchBundle\SearchBundleDBAL\Facet;

use Shopware\Bundle\SearchBundle\FacetInterface;

class UserPriceFacet implements FacetInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'swag_search_bundle_user_price';
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
