<?php
namespace Svaapta\RestrictCart\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Message\ManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Svaapta\RestrictCart\Logger\Logger;

class RestrictAddToCartObserver implements ObserverInterface
{
    protected $logger;
    protected $messageManager;
    protected $cart;
    protected $customerSession;

    public function __construct(
        Logger $logger,
        ManagerInterface $messageManager,
        Cart $cart,
        CustomerSession $customerSession
    ) {
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->cart = $cart;
        $this->customerSession = $customerSession;
    }

    /**
     * Execute the observer action
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->logger->info('Add to Cart Observer Triggered.');

        $product = $observer->getEvent()->getProduct();
        $quote = $this->cart->getQuote();
        $allowedQtyLimit = 5;
        $this->logger->info('Product ID: ' . $product->getId());

        // Check if the current user is in the restricted group (e.g., Guest or General)
        $customerGroupId = $this->customerSession->getCustomerGroupId();
        $restrictedGroups = [0, 1]; // Example: 0 - Guest, 1 - General Customer Group
        $this->logger->info('Customer Group ID: ' . $customerGroupId);
        
        if (in_array($customerGroupId, $restrictedGroups)) {
            // Calculate the total quantity in the cart
            $currentQty = 0;
            foreach ($quote->getAllItems() as $item) {
                $currentQty += $item->getQty();
            }

            // Check if the new addition will exceed the allowed limit
            if ($currentQty + 1 > $allowedQtyLimit) {
                // Prevent adding the item to the cart
                $this->messageManager->addWarningMessage(
                    __('You cannot add more than 5 items to your cart.')
                );

                // Optionally, throw an exception or revert changes
                throw new LocalizedException(__('You cannot add Product.'));
            }

            $this->logger->info('Current Cart Quantity: ' . $currentQty);
        }
    }
}
