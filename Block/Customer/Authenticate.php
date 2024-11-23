<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Block\Customer;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;

class Authenticate extends Template
{
    /**
     * @var FormKey
     */
    private readonly FormKey $formKey;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param FormKey $formKey
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        FormKey $formKey,
        array $data = []
    ) {
        $this->formKey = $formKey;

        parent::__construct($context, $data);
    }

    /**
     * Retrieve Session Form Key
     *
     * @return string
     * @throws LocalizedException
     */
    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }
}
