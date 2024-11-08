<?php
/**
 * This file is part of the SetaPDF-Core Component
 * 
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Type
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: Numeric.php 1733 2022-06-02 07:39:42Z jan.slabon $
 */

/**
 * Class representing a numeric object
 * 
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Type
 * @license    https://www.setasign.com/ Commercial
 */
class SetaPDF_Core_Type_Numeric extends SetaPDF_Core_Type_AbstractType
    implements SetaPDF_Core_Type_ScalarValue
{
    /**
     * The numeric value
     * 
     * @var integer|float
     */
    protected $_value = 0;
    
    /**
     * This helper method simulates the overflow behavior of a 32bit system on a 64bit system.
     * 
     * @param integer $value
     * @return integer
     */
    public static function ensure32BitInteger($value)
    {
        if (PHP_INT_SIZE === 4 || ($value) < (2147483647)) {
            return $value;
        }
        //x need to be a var otherwise a zend_guard 5.2 encrypted package will make this to -1
        $x = 4294967295;
        return ($value | ($x << 32));
    }
    
    /**
     * Parses a php integer or float value to a pdf numeric string and write it into a writer.
     *
     * @see SetaPDF_Core_Type_AbstractType
     * @param SetaPDF_Core_WriteInterface $writer
     * @param integer|float $value
     * @return void
     */
    public static function writePdfString(SetaPDF_Core_WriteInterface $writer, $value)
    {
        if ($value == 0) {
            $writer->write(' 0');
        } elseif (is_int($value)) {
            $writer->write(' ' . $value);
        } else {
            $writer->write(' ' . rtrim(rtrim(sprintf('%.5F', $value), '0'), '.'));
        }
    }

    /**
     * Ensures that the passed value is a SetaPDF_Core_Type_Numeric instance.
     *
     * @param mixed $value
     * @return self
     * @throws SetaPDF_Core_Type_Exception
     */
    public static function ensureType($value)
    {
        return self::_ensureType(self::class, $value, 'Numeric value expected.');
    }

    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * The constructor.
     * 
     * @param integer|float $value
     */
    public function __construct($value = null)
    {
        unset($this->_observed);
        
        if (null !== $value) {
            $this->_value = $value + 0;
        }
    }
    
    /**
     * Set the numeric value.
     * 
     * @param float|integer $value
     * @see SetaPDF_Core_Type_AbstractType::setValue()
     */
    public function setValue($value)
    {
        $value += 0;
            
        if ($value === $this->_value) {
            return;
        }
        
        $this->_value = $value;
        
        if (isset($this->_observed)) {
            $this->notify();
        }
    }
    
    /**
     * Ger the numeric value.
     *
     * @return float
     * @see SetaPDF_Core_Type_AbstractType::getValue()
     */
    public function getValue()
    {
        return $this->_value;
    }
    
    /**
     * Returns the type as a formatted PDF string.
     *
     * @param SetaPDF_Core_Document|null $pdfDocument
     * @return string
     */
    public function toPdfString(SetaPDF_Core_Document $pdfDocument = null)
    {
        if ($this->_value == 0) {
            return ' 0';
        }

        if (is_int($this->_value)) {
            return ' ' . $this->_value;
        }

        return ' ' . rtrim(rtrim(sprintf('%.5F', $this->_value), '0'), '.');
    }

    /**
     * Writes the type as a formatted PDF string to the document.
     *
     * @param SetaPDF_Core_Document $pdfDocument
     */
    public function writeTo(SetaPDF_Core_Document $pdfDocument)
    {
        $pdfDocument->write($this->toPdfString($pdfDocument));
    }
    
    /**
     * Converts the PDF data type to a PHP data type and returns it.
     *
     * @return float
     */
    public function toPhp()
    {
        return $this->_value;
    }
}