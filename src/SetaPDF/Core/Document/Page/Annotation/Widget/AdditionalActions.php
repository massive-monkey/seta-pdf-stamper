<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Document
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: AdditionalActions.php 1706 2022-03-28 10:40:28Z jan.slabon $
 */

/**
 * Class representing a widget annotations additional-actions dictionary
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Document
 * @license    https://www.setasign.com/ Commercial
 */
class SetaPDF_Core_Document_Page_Annotation_Widget_AdditionalActions
extends SetaPDF_Core_Document_Page_Annotation_AdditionalActions
{
    /**
     * The constructor.
     *
     * @param SetaPDF_Core_Document_Page_Annotation_Widget $annotation
     */
    public function __construct(SetaPDF_Core_Document_Page_Annotation_Widget $annotation)
    {
        parent::__construct($annotation);
    }

    /**
     * Get the action that shall be performed when the annotation receives the input focus.
     *
     * @return null|SetaPDF_Core_Document_Action
     */
    public function getFocus()
    {
        return $this->_getAction('Fo');
    }

    /**
     * Set the action that shall be performed when the annotation receives the input focus.
     *
     * @param SetaPDF_Core_Document_Action $action
     * @return SetaPDF_Core_Document_Page_Annotation_Widget_AdditionalActions
     * @throws SetaPDF_Core_Type_Exception
     */
    public function setFocus(SetaPDF_Core_Document_Action $action)
    {
        $this->_setAction('Fo', $action);

        return $this;
    }

    /**
     * Get the action that shall be performed when the annotation loses the input focus.
     *
     * @return null|SetaPDF_Core_Document_Action
     */
    public function getBlur()
    {
        return $this->_getAction('Bl');
    }

    /**
     * Set the action that shall be performed when the annotation loses the input focus.
     *
     * @param SetaPDF_Core_Document_Action $action
     * @return SetaPDF_Core_Document_Page_Annotation_Widget_AdditionalActions
     * @throws SetaPDF_Core_Type_Exception
     */
    public function setBlur(SetaPDF_Core_Document_Action $action)
    {
        $this->_setAction('Bl', $action);

        return $this;
    }
}
