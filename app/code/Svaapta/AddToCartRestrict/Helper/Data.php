<?php
namespace Svaapta\AddToCartRestrict\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ENABLED = 'addtocartrestrict/general/enabled';
    const XML_PATH_MAX_QTY = 'addtocartrestrict/general/max_allowed_qty';
    const XML_PATH_CUSTOMER_GROUPS = 'addtocartrestrict/general/customer_groups';

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get maximum allowed quantity
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMaxAllowedQty($storeId = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MAX_QTY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get restricted customer groups
     *
     * @param int|null $storeId
     * @return array
     */
    public function getCustomerGroups($storeId = null)
    {
        $groups = $this->scopeConfig->getValue(
            self::XML_PATH_CUSTOMER_GROUPS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        
        return $groups ? explode(',', $groups) : [];
    }
}