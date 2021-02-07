<?php
namespace Sga\IpRedirect\Block\Adminhtml\Config;

class Mapping extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    protected $_elementFactory;
    protected $_configYesNo;
    protected $_directories;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Magento\Config\Model\Config\Source\Yesno $configYesNo,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $directories,
        array $data = []
    ) {
        $this->_elementFactory = $elementFactory;
        $this->_configYesNo = $configYesNo;
        $this->_directories = $directories;

        $this->_addAfter = false;

        parent::__construct($context, $data);
    }

    public function _prepareToRender()
    {
        $this->addColumn(
            'country',
            [
                'label' => __('Country'),
                'style' => 'width:200px',
            ]
        );
        $this->addColumn(
            'redirect_once',
            [
                'label' => __('Redirect Once'),
                'style' => 'width:80px',
            ]
        );
        $this->addColumn(
            'url',
            [
                'label' => __('Url'),
                'style' => 'width:300px',
            ]
        );
    }

    public function renderCellTemplate($columnName)
    {
        if ($columnName == 'country' && isset($this->_columns[$columnName])) {
            $element = $this->_elementFactory->create('select');
            $element->setForm($this->getForm())
                ->setName($this->_getCellInputElementName($columnName))
                ->setHtmlId($this->_getCellInputElementId('<%- _id %>', $columnName))
                ->setStyle($this->_columns[$columnName]['style'])
                ->setValues($this->_directories->create()->load()->toOptionArray(false));

            return str_replace("\n", '', $element->getElementHtml());
        }

        if ($columnName == 'redirect_once' && isset($this->_columns[$columnName])) {
            $element = $this->_elementFactory->create('select');
            $element->setForm($this->getForm())
                ->setName($this->_getCellInputElementName($columnName))
                ->setHtmlId($this->_getCellInputElementId('<%- _id %>', $columnName))
                ->setStyle($this->_columns[$columnName]['style'])
                ->setValues($this->_configYesNo->toOptionArray());

            return str_replace("\n", '', $element->getElementHtml());
        }

        return parent::renderCellTemplate($columnName);
    }

    public function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $options = [];

        $options['option_' . $this->_getSelectRenderer()->calcOptionHash($row->getData('country'))] = 'selected="selected"';
        $options['option_' . $this->_getSelectRenderer()->calcOptionHash($row->getData('redirect_once'))] = 'selected="selected"';

        $row->setData('option_extra_attrs', $options);
    }

    public function _getSelectRenderer()
    {
        return $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class,
            '',
            ['data' => ['is_render_to_js_template' => true]]
        );
    }
}