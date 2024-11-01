<?php

namespace Paulo\AjaxCompare\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
class OptionsPromo extends AbstractSource
{
    /**
     * @return array|array[]|null
     */
    public function getAllOptions(): ?array
    {
        if (null === $this->_options) {
            $this->_options = [
                ['label' => __('No'), 'value' => 0],
                ['label' => __('Yes'), 'value' => 1]
            ];
        }
        return $this->_options;
    }
}
