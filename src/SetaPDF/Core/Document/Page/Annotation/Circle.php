<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Document
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: Circle.php 1733 2022-06-02 07:39:42Z jan.slabon $
 */

/**
 * Class representing a circle annotation
 *
 * See PDF 32000-1:2008 - 12.5.6.8
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Document
 * @license    https://www.setasign.com/ Commercial
 */
class SetaPDF_Core_Document_Page_Annotation_Circle
    extends SetaPDF_Core_Document_Page_Annotation_Square
{
    /**
     * @param SetaPDF_Core_DataStructure_Rectangle|array $rect
     * @return SetaPDF_Core_Type_Dictionary
     * @throws InvalidArgumentException
     */
    public static function createAnnotationDictionary($rect)
    {
        return parent::_createAnnotationDictionary($rect, SetaPDF_Core_Document_Page_Annotation::TYPE_CIRCLE);
    }

    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * The constructor.
     *
     * @param array|SetaPDF_Core_Type_AbstractType|SetaPDF_Core_Type_Dictionary|SetaPDF_Core_Type_IndirectObjectInterface $objectOrDictionary
     * @throws InvalidArgumentException
     * @throws SetaPDF_Core_Type_Exception
     */
    public function __construct($objectOrDictionary)
    {
        $dictionary = $objectOrDictionary instanceof SetaPDF_Core_Type_AbstractType
            ? $objectOrDictionary->ensure(true)
            : $objectOrDictionary;

        if (!($dictionary instanceof SetaPDF_Core_Type_Dictionary)) {
            $args = func_get_args();
            $objectOrDictionary = $dictionary = SetaPDF_Core_Type_Dictionary::ensureType(call_user_func_array(
                ['self', 'createAnnotationDictionary'],
                $args
            ));
            unset($args);
        }

        if (!SetaPDF_Core_Type_Dictionary_Helper::keyHasValue($dictionary, 'Subtype', 'Circle')) {
            throw new InvalidArgumentException('The Subtype entry in a circle annotation shall be "Circle".');
        }

        SetaPDF_Core_Document_Page_Annotation::__construct($objectOrDictionary);
    }
}
