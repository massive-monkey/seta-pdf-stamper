<?php 
/**
 * This file is part of the SetaPDF-Core Component
 * 
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage SecHandler
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: Arcfour128.php 1733 2022-06-02 07:39:42Z jan.slabon $
 */

/**
 * Generator class for RC4 128 bit security handler
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage SecHandler
 * @license    https://www.setasign.com/ Commercial
 */
class SetaPDF_Core_SecHandler_Standard_Arcfour128 extends SetaPDF_Core_SecHandler_Standard
{
    /**
     * Factory method for RC4 128 bit security handler.
     *
     * @param SetaPDF_Core_Document $document
     * @param string $ownerPassword The owner password in encoding defined in $passwordsEncoding
     * @param string $userPassword The user password in encoding defined in $passwordsEncoding
     * @param integer $permissions
     * @param string $passwordsEncoding
     * @return SetaPDF_Core_SecHandler_Standard_Arcfour128
     * @throws SetaPDF_Core_SecHandler_Exception
     * @throws SetaPDF_Exception_NotImplemented
     */
    public static function factory(
        SetaPDF_Core_Document $document,
        $ownerPassword,
        $userPassword = '',
        $permissions = 0,
        $passwordsEncoding = SetaPDF_Core_Encoding::PDF_DOC
    )
    {
        $ownerPassword = self::ensurePasswordEncoding(3, $ownerPassword, $passwordsEncoding);
        $userPassword = self::ensurePasswordEncoding(3, $userPassword, $passwordsEncoding);

        $encryptionDict = new SetaPDF_Core_Type_Dictionary();
        $encryptionDict->offsetSet('Filter', new SetaPDF_Core_Type_Name('Standard', true));
        
        $encryptionDict->offsetSet('R', new SetaPDF_Core_Type_Numeric(3));
        $encryptionDict->offsetSet('V', new SetaPDF_Core_Type_Numeric(2));
        $encryptionDict->offsetSet('O', new SetaPDF_Core_Type_String());
        $encryptionDict->offsetSet('U', new SetaPDF_Core_Type_String());
        $encryptionDict->offsetSet('Length', new SetaPDF_Core_Type_Numeric(128));

        $permissions = self::ensurePermissions($permissions, 3);
        $encryptionDict->offsetSet('P', new SetaPDF_Core_Type_Numeric($permissions));
        
        $instance = new self($document, $encryptionDict);
        
        $oValue = $instance->_computeOValue($userPassword, $ownerPassword);
        $encryptionDict->offsetGet('O')->getValue()->setValue($oValue);
        
        $encryptionKey = $instance->_computeEncryptionKey($userPassword);

        $uValue = $instance->_computeUValue($encryptionKey);
        $encryptionDict->offsetGet('U')->getValue()->setValue($uValue);

        $instance->_encryptionKey = $encryptionKey;
        $instance->_auth = true;
        $instance->_authMode = SetaPDF_Core_SecHandler::OWNER;

        return $instance;
    }
}