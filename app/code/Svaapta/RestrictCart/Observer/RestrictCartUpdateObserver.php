<?php
namespace Svaapta\RestrictCart\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Message\ManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Svaapta\RestrictCart\Logger\Logger;
use Svaapta\RestrictCart\Exception\CartUpdateRestrictionException;

class RestrictCartUpdateObserver implements ObserverInterface
{
    protected $logger;
    protected $customerSession;
    protected $messageManager;

    public function __construct(
        Logger $logger,
        CustomerSession $customerSession,
        ManagerInterface $messageManager
    ) {
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
    }

    /**
     * Execute the observer action
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->logger->info('Restricted Cart Update Observer Trigger.');
        
        $cart = $observer->getEvent()->getCart()->getQuote();
        $this->logger->info('Cart Data: ' . print_r($cart->getAllItems(), true));
        $info = $observer->getEvent()->getInfo()->getData();
        $itemId = key($info);

        // Extract the new quantity the user is trying to set
        $newQty = $info[$itemId]['qty'];
        $this->logger->info('New Qty: ' . $newQty);

        $this->logger->info('Key: ' . key($observer->getInfo()->getData()));
        $this->logger->info('Observer Cart Data: ' . print_r($observer->getInfo(), true));


        $this->logger->info('Request Data: ' .print_r($observer->getData(), true));


        $allowedCustomerGroups = [0, 1];  // Guest and General Customer Group
        $currentGroupId = $this->customerSession->getCustomerGroupId();

        $this->logger->info('Current Customer Group: ' . $currentGroupId);
        $this->logger->info('Item ID: ' . $itemId);

        if (in_array($currentGroupId, $allowedCustomerGroups)) {

            $this->logger->info('Restriction Logic.');
            
            $currentQty = 0;
            $originalQty = 0;  // Store original quantity

            // Calculate total quantity in cart excluding the updated item
            foreach ($cart->getAllItems() as $item) {
                if ($item->getId() == $itemId) {
                    $originalQty = $item->getQty();  // Save the original quantity
                } else {
                    $currentQty += $item->getQty();
                }
            }

            $this->logger->info('Current Qty: ' . $currentQty);

            // If the update would exceed 5 items
            if ($currentQty + $newQty > 5) {
                $this->messageManager->addWarningMessage(
                    __('You can only have a maximum of 5 items in your cart.')
                );

                $this->logger->warning(sprintf(
                    'Blocked cart update: Attempt to update item ID %d to qty %d when current cart has %d items.',
                    $itemId,
                    $newQty,
                    $currentQty
                ));


                // logic which stop or revert the cart quantity.
                // $observer->getEvent()->getCart()->getQuote()->removeItem($itemId);


                // Add and Remove

                
                // Throw the exception to prevent the update
                throw new CartUpdateRestrictionException(__('Cart quantity limit exceeded.'));
                
                return;
            }
        }
    }
}
