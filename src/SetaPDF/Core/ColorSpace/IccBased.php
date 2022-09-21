<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: IccBased.php 1706 2022-03-28 10:40:28Z jan.slabon $
 */

/**
 * ICCBased Color Space
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage ColorSpace
 * @license    https://www.setasign.com/ Commercial
 */
class SetaPDF_Core_ColorSpace_IccBased
    extends SetaPDF_Core_ColorSpace
    implements SetaPDF_Core_Resource
{
    /**
     * Creates an instance of this color space.
     *
     * @param SetaPDF_Core_IccProfile_Stream $iccStream
     * @return SetaPDF_Core_ColorSpace_IccBased
     * @throws SetaPDF_Core_Exception
     */
    public static function create(SetaPDF_Core_IccProfile_Stream $iccStream)
    {
        return new self(new SetaPDF_Core_Type_Array([
            new SetaPDF_Core_Type_Name('ICCBased'),
            $iccStream->getIndirectObject()
        ]));
    }

    /**
     * Release profile stream instances by a document instance.
     *
     * @param SetaPDF_Core_Document $document
     */
    public static function freeCache(SetaPDF_Core_Document $document)
    {
        $document->clearCache(SetaPDF_Core_Document::CACHE_ICC_PROFILE);
    }

    /**
     * The constructor.
     *
     * @param SetaPDF_Core_Type_AbstractType $definition
     * @throws InvalidArgumentException|SetaPDF_Core_Exception
     */
    public function __construct(SetaPDF_Core_Type_AbstractType $definition)
    {
        parent::__construct($definition);

        if (!$this->_value instanceof SetaPDF_Core_Type_Array) {
            throw  new InvalidArgumentException('IccBased color space needs to be defined by an array.');
        }

        if ($this->getFamily() !== 'ICCBased') {
            throw new InvalidArgumentException('ICCBased color space has to be named "ICCBased".');
        }

        try {
            SetaPDF_Core_Type_Stream::ensureType($this->_value->offsetGet(1));
        } catch (SetaPDF_Core_Type_Exception $e) {
            throw new InvalidArgumentException(
                "ICCBased color space needs a ICC profile (stream object) in it's definition.",
                0,
                $e
            );
        }
    }

    /**
     * Get an instance of the ICC Profile stream.
     *
     * @return SetaPDF_Core_IccProfile_Stream
     * @throws SetaPDF_Core_Type_Exception
     */
    public function getIccProfileStream()
    {
        $indirectObject = SetaPDF_Core_Type_IndirectReference::ensureType($this->getPdfValue()->offsetGet(1));

        $ident = $indirectObject->getObjectIdent();
        $document = $indirectObject->getOwnerPdfDocument();
        if ($document->hasCache(SetaPDF_Core_Document::CACHE_ICC_PROFILE, $ident)) {
            return $document->getCache(SetaPDF_Core_Document::CACHE_ICC_PROFILE, $ident);
        }

        $stream = new SetaPDF_Core_IccProfile_Stream($indirectObject);
        $document->addCache(SetaPDF_Core_Document::CACHE_ICC_PROFILE, $ident, $stream);

        return $stream;
    }

    /**
     * Gets an indirect object for this color space dictionary.
     *
     * @see SetaPDF_Core_Resource::getIndirectObject()
     * @param SetaPDF_Core_Document $document
     * @return SetaPDF_Core_Type_IndirectObjectInterface
     * @throws InvalidArgumentException
     */
    public function getIndirectObject(SetaPDF_Core_Document $document = null)
    {
        if ($this->_indirectObject === null) {
            if ($document === null) {
                throw new InvalidArgumentException('To initialize a new object $document parameter is not optional!');
            }

            $this->_indirectObject = $document->createNewObject($this->getPdfValue());
        }

        return $this->_indirectObject;
    }

    /**
     * Get the resource type of an implementation.
     *
     * @return string
     */
    public function getResourceType()
    {
        return SetaPDF_Core_Resource::TYPE_COLOR_SPACE;
    }

    /**
     * @throws SetaPDF_Core_Type_Exception
     */
    private function _getStream()
    {
        $stream = $this->getPdfValue()->offsetGet(1);
        if ($stream !== null) {
            $stream = $stream->ensure();
        }

        return SetaPDF_Core_Type_Stream::ensureType($stream);
    }

    /**
     * Get the alternate color space.
     *
     * @return SetaPDF_Core_ColorSpace|SetaPDF_Core_ColorSpace_DeviceCmyk|SetaPDF_Core_ColorSpace_DeviceGray|SetaPDF_Core_ColorSpace_DeviceN|SetaPDF_Core_ColorSpace_DeviceRgb|SetaPDF_Core_ColorSpace_IccBased|SetaPDF_Core_ColorSpace_Indexed|SetaPDF_Core_ColorSpace_Lab|SetaPDF_Core_ColorSpace_Separation|null
     * @throws SetaPDF_Core_Type_Exception
     * @throws SetaPDF_Core_Exception
     */
    public function getAlternateColorSpace()
    {
        $dict = $this->_getStream()->getValue();
        if (!$dict->offsetExists('Alternate')) {
            return null;
        }

        /** @var SetaPDF_Core_Type_Name|SetaPDF_Core_Type_Array $colorSpace */
        $colorSpace =  $dict->getValue('Alternate');

        return SetaPDF_Core_ColorSpace::createByDefinition($colorSpace);
    }

    /**
     * Get the color components of this color space.
     *
     * @return integer
     * @throws SetaPDF_Core_Type_Exception
     */
    public function getColorComponents()
    {
        $dict = $this->_getStream()->getValue();
        $n = SetaPDF_Core_Type_Dictionary_Helper::getValue($dict, 'N');
        return SetaPDF_Core_Type_Numeric::ensureType($n)->getValue();
    }

    /**
     * Get the default decode array of this color space.
     *
     * @return array
     * @throws SetaPDF_Core_Type_Exception
     */
    public function getDefaultDecodeArray()
    {
        $dict = $this->_getStream()->getValue();

        if ($dict->offsetExists('Range')) {
            $range = SetaPDF_Core_Type_Dictionary_Helper::getValue($dict, 'Range');
            if ($range !== null) {
                return SetaPDF_Core_Type_Array::ensureType($range->ensure())->toPhp(true);
            }
        }

        $result = [];
        for ($i = 0; $i < $this->getColorComponents(); $i++) {
            $result[] = 0.;
            $result[] = 1.;
        }

        return $result;
    }
}
