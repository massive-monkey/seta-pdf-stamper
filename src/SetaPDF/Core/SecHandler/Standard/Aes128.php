<?php 
/**
 * This file is part of the SetaPDF-Core Component
 * 
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage SecHandler
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: Aes128.php 1733 2022-06-02 07:39:42Z jan.slabon $
 */

/**
 * Generator class for AES 128 bit security handler
 *  
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage SecHandler
 * @license    https://www.setasign.com/ Commercial
 */
class SetaPDF_Core_SecHandler_Standard_Aes128 extends SetaPDF_Core_SecHandler_Standard
{
    /**
     * Factory method for AES 128 bit security handler.
     *
     * @param SetaPDF_Core_Document $document
     * @param string $ownerPassword The owner password in encoding defined in $passwordsEncoding
     * @param string $userPassword The user password in encoding defined in $passwordsEncoding
     * @param integer $permissions
     * @param boolean $encryptMetadata
     * @param string $passwordsEncoding
     * @return SetaPDF_Core_SecHandler_Standard_Aes128
     * @throws SetaPDF_Core_SecHandler_Exception
     * @throws SetaPDF_Exception_NotImplemented
     */
    public static function factory(
        SetaPDF_Core_Document $document,
        $ownerPassword,
        $userPassword = '',
        $permissions = 0,
        $encryptMetadata = true,
        $passwordsEncoding = SetaPDF_Core_Encoding::PDF_DOC
    )
    {
        $ownerPassword = self::ensurePasswordEncoding(4, $ownerPassword, $passwordsEncoding);
        $userPassword = self::ensurePasswordEncoding(4, $userPassword, $passwordsEncoding);

        $encryptionDict = new SetaPDF_Core_Type_Dictionary();
        $encryptionDict->offsetSet('Filter', new SetaPDF_Core_Type_Name('Standard', true));
        
        $encryptionDict->offsetSet('R', new SetaPDF_Core_Type_Numeric(4));
        $encryptionDict->offsetSet('V', new SetaPDF_Core_Type_Numeric(4));
        $encryptionDict->offsetSet('O', new SetaPDF_Core_Type_String());
        $encryptionDict->offsetSet('U', new SetaPDF_Core_Type_String());
        $encryptionDict->offsetSet('Length', new SetaPDF_Core_Type_Numeric(128));

        $permissions = self::ensurePermissions($permissions, 4);
        $encryptionDict->offsetSet('P', new SetaPDF_Core_Type_Numeric($permissions));
        
        $encryptionDict->offsetSet('EncryptMetadata', new SetaPDF_Core_Type_Boolean($encryptMetadata));
        
        $cf = new SetaPDF_Core_Type_Dictionary();
        $stdCf = new SetaPDF_Core_Type_Dictionary();
        $stdCf->offsetSet('CFM', new SetaPDF_Core_Type_Name('AESV2', true));
        $stdCf->offsetSet('AuthEvent', new SetaPDF_Core_Type_Name('DocOpen', true));
        $stdCf->offsetSet('Length', new SetaPDF_Core_Type_Numeric(16));
        $cf->offsetSet('StdCF', $stdCf);
        $encryptionDict->offsetSet('CF', $cf);
        $encryptionDict->offsetSet('StrF', new SetaPDF_Core_Type_Name('StdCF', true));
        $encryptionDict->offsetSet('StmF', new SetaPDF_Core_Type_Name('StdCF', true));
        
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