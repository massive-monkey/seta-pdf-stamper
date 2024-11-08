<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Document
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: PolyLine.php 1733 2022-06-02 07:39:42Z jan.slabon $
 */

/**
 * Class representing a poly line annotation
 *
 * See PDF 32000-1:2008 - 12.5.6.13
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Document
 * @license    https://www.setasign.com/ Commercial
 */
class SetaPDF_Core_Document_Page_Annotation_PolyLine
    extends SetaPDF_Core_Document_Page_Annotation_Polygon
{
    /**
     * Creates a poly line annotation dictionary.
     *
     * @param SetaPDF_Core_DataStructure_Rectangle|array $rect
     * @return SetaPDF_Core_Type_Dictionary
     * @throws InvalidArgumentException
     */
    public static function createAnnotationDictionary($rect)
    {
        $dictionary = parent::_createAnnotationDictionary($rect, SetaPDF_Core_Document_Page_Annotation::TYPE_POLY_LINE);
        $dictionary->offsetSet('Vertices', new SetaPDF_Core_Type_Array());

        return $dictionary;
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

        if (!SetaPDF_Core_Type_Dictionary_Helper::keyHasValue($dictionary, 'Subtype', 'PolyLine')) {
            throw new InvalidArgumentException('The Subtype entry in an poly line annotation shall be "PolyLine".');
        }

        SetaPDF_Core_Document_Page_Annotation_Markup::__construct($objectOrDictionary);
    }

    /**
     * Set the line ending styles.
     *
     * @see SetaPDF_Core_Document_Page_Annotation_LineEndingStyle
     * @param string $first
     * @param string $last
     */
    public function setLineEndingStyles($first, $last)
    {
        $dict = $this->getDictionary();
        $le = SetaPDF_Core_Type_Dictionary_Helper::getValue($dict, 'LE');
        if (!$le instanceof SetaPDF_Core_Type_Array) {
            $le = new SetaPDF_Core_Type_Array();
            $dict->offsetSet('LE', $le);
        }

        $le->clear();
        $le[] = new SetaPDF_Core_Type_Name($first);
        $le[] = new SetaPDF_Core_Type_Name($last);
    }

    /**
     * Get the line ending styles.
     *
     * @return array
     */
    public function getLineEndingStyles()
    {
        $le = SetaPDF_Core_Type_Dictionary_Helper::getValue($this->getDictionary(), 'LE');
        if (!$le instanceof SetaPDF_Core_Type_Array) {
            return [
                SetaPDF_Core_Document_Page_Annotation_LineEndingStyle::NONE,
                SetaPDF_Core_Document_Page_Annotation_LineEndingStyle::NONE
            ];
        }

        return $le->toPhp(true);
    }
}
