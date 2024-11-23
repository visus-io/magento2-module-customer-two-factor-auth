<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Block\Adminhtml\Customer\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Customer\Block\Adminhtml\Edit\GenericButton;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Visus\CustomerTfa\Api\CustomerTfaRepositoryInterface;

class ResetTfaButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @var CustomerTfaRepositoryInterface
     */
    private readonly CustomerTfaRepositoryInterface $customerTfaRepository;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param CustomerTfaRepositoryInterface $customerTfaRepository
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CustomerTfaRepositoryInterface $customerTfaRepository
    ) {
        $this->customerTfaRepository = $customerTfaRepository;

        parent::__construct($context, $registry);
    }

    /**
     * @inheritdoc
     */
    public function getButtonData(): array
    {
        $customerId = (int)$this->getCustomerId();
        if (empty($customerId) || !$this->customerTfaRepository->isEnrolled($customerId)) {
            return [];
        }

        return [
            'label' => __('Reset 2FA'),
            'class' => 'reset',
            'on_click' => sprintf("location.href = '%s'", $this->getResetTokenUrl()),
            'sort_order' => 60,
            'aclResource' => 'Visus_CustomerTfa::reset'
        ];
    }

    /**
     * Get reset tfa token url
     *
     * @return string
     */
    private function getResetTokenUrl(): string
    {
        return $this->getUrl('visus_tfa/customer/reset', ['id' => $this->getCustomerId()]);
    }
}
