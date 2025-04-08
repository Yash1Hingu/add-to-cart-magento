<?php
namespace Svaapta\AddToCartRestrict\Model\Config\Source;

use Magento\Customer\Model\Config\Source\Group\Multiselect as OriginalMultiselect;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\Option\ArrayInterface;

class Multiselect implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    protected $groupCollectionFactory;

    /**
     * @param CollectionFactory $groupCollectionFactory
     */
    public function __construct(
        CollectionFactory $groupCollectionFactory
    ) {
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    /**
     * Get option array with 'Not Logged In' added
     * 
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        // Add "Not Logged In" to the list with value 0
        // $options[] = ['value' => 0, 'label' => __('Not Logged In')];

        // Fetch customer groups using the collection
        $customerGroupCollection = $this->groupCollectionFactory->create();

        // Loop through customer groups and add them to the options list
        foreach ($customerGroupCollection as $group) {
            $options[] = [
                'value' => $group->getId(),
                'label' => $group->getCustomerGroupCode()
            ];
        }

        return $options;
    }
}
