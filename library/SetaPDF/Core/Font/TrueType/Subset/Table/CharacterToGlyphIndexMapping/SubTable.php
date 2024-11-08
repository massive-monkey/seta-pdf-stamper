<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Font
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: SubTable.php 1706 2022-03-28 10:40:28Z jan.slabon $
 */

/**
 * This class is a generic representation of a "cmap" subtable in a sub-setting context.
 *
 * @see SetaPDF_Core_Font_TrueType_Subset_Table_CharacterToGlyphIndexMapping::setSubTable()
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Font
 * @license    https://www.setasign.com/ Commercial
 */
abstract class SetaPDF_Core_Font_TrueType_Subset_Table_CharacterToGlyphIndexMapping_SubTable implements Countable
{
    /**
     * The mapping.
     *
     * @var array
     */
    protected $_mapping = [];

    /**
     * The language of the cmap subtable.
     *
     * @var int
     */
    protected $_language;

    /**
     * The constructor.
     *
     * @param int $language
     */
    public function __construct($language)
    {
        $this->_language = $language;
    }

    /**
     * Release memory and cycled references.
     */
    public function cleanUp()
    {
        $this->_mapping = null;
        $this->_language = null;
    }

    /**
     * Add/Change a mapping in the subtable.
     *
     * @param int $charCode
     * @param int $index
     */
    public function setGlyphIndex($charCode, $index)
    {
        $this->_mapping[$charCode] = $index;
    }

    /**
     * Returns the language.
     *
     * @return int
     */
    public function getLanguage()
    {
        return $this->_language;
    }

    /**
     * Gets the size of the mapping.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->_mapping);
    }

    /**
     * Returns the sub table format
     *
     * @return int
     */
    abstract public function getFormat();

    /**
     * Writes the sub table.
     *
     * @param SetaPDF_Core_Writer_WriterInterface $writer
     */
    abstract public function write(SetaPDF_Core_Writer_WriterInterface $writer);
}
