<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Parser
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: Pdf.php 1773 2022-09-07 08:23:27Z jan.slabon $
 */

/**
 * A PDF parser
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Parser
 * @license    https://www.setasign.com/ Commercial
 */
class SetaPDF_Core_Parser_Pdf
{
    /**
     * The reader class
     *
     * @var SetaPDF_Core_Reader_ReaderInterface
     */
    protected $_reader;

    /**
     * The tokenizer
     *
     * @var SetaPDF_Core_Tokenizer
     */
    protected $_tokenizer;

    /**
     * The owner document
     *
     * @var SetaPDF_Core_Document
     */
    protected $_owner;

    /**
     * The current object which is parsed
     *
     * @var SetaPDF_Core_Type_IndirectObject
     */
    protected $_currentObject;

    /**
     * If set to true the owning object is passed to parsed child elements
     *
     * This is needed to create a relation between a parsed object and its owning element.
     * The complete chain will be able to get a relation to the owning document.
     * Needed for example for handling en- and decryption of strings or streams.
     *
     * @var boolean
     */
    protected $_passOwningObjectToChilds = false;

    /**
     * The constructor.
     *
     * @param SetaPDF_Core_Reader_ReaderInterface $reader
     */
    public function __construct(SetaPDF_Core_Reader_ReaderInterface $reader)
    {
        $this->_reader = $reader;
        $this->_tokenizer = new SetaPDF_Core_Tokenizer($this->_reader);
    }

    /**
     * Define if the owning object should be passed to it's childs.
     *
     * @param boolean $passOwningObjectToChilds
     * @see $_passOwningObjectToChilds
     */
    public function setPassOwningObjectToChilds($passOwningObjectToChilds = true)
    {
        $this->_passOwningObjectToChilds = (boolean)$passOwningObjectToChilds;
    }

    /**
     * Released memory and resources.
     */
    public function cleanUp()
    {
        $this->_owner = null;
        $this->_tokenizer->cleanUp();
        $this->_tokenizer = null;
        $this->_reader = null;
    }

    /**
     * Set the reader object.
     *
     * @param SetaPDF_Core_Reader_ReaderInterface $reader
     */
    public function setReader(SetaPDF_Core_Reader_ReaderInterface $reader)
    {
        $this->_reader = $reader;

        /* Because reader is passed by reference we have to forward this
         * set-call to the tokenizer as well.
         * This issue is only reproducible by serializing and
         * deserializing a document object (Mantis #585).
         */
        $this->_tokenizer->setReader($reader);
    }

    /**
     * Get the reader object.
     *
     * @return SetaPDF_Core_Reader_ReaderInterface
     */
    public function getReader()
    {
        return $this->_reader;
    }

    /**
     * Get the tokenizer object.
     *
     * @return SetaPDF_Core_Tokenizer
     */
    public function getTokenizer()
    {
        return $this->_tokenizer;
    }

    /**
     * Set the owner pdf document.
     *
     * @param SetaPDF_Core_Type_Owner $owner
     */
    public function setOwner(SetaPDF_Core_Type_Owner $owner)
    {
        $this->_owner = $owner;
    }

    /**
     * Get the owner pdf document.
     *
     * @return null|SetaPDF_Core_Document
     */
    public function getOwner()
    {
        return $this->_owner;
    }

    /**
     * Get the PDF version.
     *
     * @TODO Should not be located in this class
     * @return string
     * @throws SetaPDF_Core_Parser_Exception
     */
    public function getPdfVersion()
    {
        $reader = $this->_reader;
        $reader->reset(0);
        while (true) {
            $buffer = $reader->getBuffer(false);
            $offset = strpos($buffer, '%PDF-');
            if ($offset === false) {
                if (!$reader->increaseLength(1000)) {
                    throw new SetaPDF_Core_Parser_CrossReferenceTable_Exception('Unable to find PDF file header.');
                }
                continue;
            }
            break;
        }

        if ($offset + 8 > strlen($buffer)) {
            $reader->increaseLength(1000);
            $buffer = $reader->getBuffer(false);
        }

        $results = [];

        if (preg_match('/%PDF-(\d\.\d)/', $buffer, $results) === false) {
            throw new SetaPDF_Core_Parser_Exception('Cannot extract PDF version.');
        }

        return $results[1];
    }

    /**
     * Get the next token.
     *
     * @return string|false
     */
    protected function _getNextToken()
    {
        $token = $this->_tokenizer->readToken();

        /**
         * We jump over a comment.
         * That type is not a real PDF object and will simple ignored.
         */
        if ($token === '%') {
            $this->_reader->readLine();
            return $this->_getNextToken();
        }

        return $token;
    }

    /**
     * Reset the reader to a specific position.
     *
     * @param integer $pos
     */
    public function reset($pos = 0)
    {
        $this->_reader->reset($pos);
        $this->_tokenizer->clearStack();
    }

    /**
     * Skips tokens until a special token is found.
     *
     * This method can be used to e.g. jump over binary inline image data.
     *
     * @param string $token
     * @param bool $inToken Defines if the token should match exactly or if a strpos should be used to find the token.
     * @return bool
     */
    public function skipUntilToken($token, $inToken = false)
    {
        $nextToken = $this->_getNextToken();
        while (
            $nextToken !== false &&
            (
                ($inToken === false && $nextToken !== $token)
                ||
                ($inToken === true && strpos($nextToken, $token) === false)
            )
        ) {
            $nextToken = $this->_getNextToken();
        }

        if ($nextToken) {
            return true;
        }

        return false;
    }

    /**
     * Ensures that the token will evaluate to an expected object type (or not).
     *
     * @param string $token
     * @param string|null $expectedType
     * @return bool
     * @throws SetaPDF_Core_Parser_Pdf_InvalidTokenException
     */
    private function _ensureExpectedValue($token, $expectedType)
    {
        static $mapping = [
            '(' => SetaPDF_Core_Type_String::class,
            '<' => SetaPDF_Core_Type_HexString::class,
            '<<' => SetaPDF_Core_Type_Dictionary::class,
            '/' => SetaPDF_Core_Type_Name::class,
            '[' => SetaPDF_Core_Type_Array::class,
            'true' => SetaPDF_Core_Type_Boolean::class,
            'false' => SetaPDF_Core_Type_Boolean::class,
            'null' => SetaPDF_Core_Type_Null::class
        ];

        if ($expectedType === null || (isset($mapping[$token]) && $mapping[$token] === $expectedType)) {
            return true;
        }

        throw new SetaPDF_Core_Parser_Pdf_InvalidTokenException('Got unexpected token type.');
    }

    /**
     * Read a value.
     *
     * @param string|null $expectedType
     * @return SetaPDF_Core_Type_AbstractType|false
     * @throws SetaPDF_Core_Exception
     * @throws SetaPDF_Core_Parser_Pdf_InvalidTokenException
     */
    public function readValue($expectedType = null)
    {
        if (($token = $this->_getNextToken()) === false) {
            $this->_ensureExpectedValue($token, $expectedType);
            return false;
        }

        return $this->_readValue($token, $expectedType);
    }

    /**
     * Read a value based on a token.
     *
     * @param string|null $token
     * @param string|null $expectedType
     * @return SetaPDF_Core_Type_AbstractType|false
     * @throws SetaPDF_Core_Parser_Pdf_InvalidTokenException
     * @throws SetaPDF_Core_Exception
     * @throws UnexpectedValueException
     */
    private function _readValue($token, $expectedType = null)
    {
        if (!is_numeric($token)) {
            switch ($token) {
                case '(':
                    $this->_ensureExpectedValue($token, $expectedType);
                    $pos = $startPos = $this->_reader->getOffset();

                    $openBrackets = 1;
                    do {
                        $buffer = $this->_reader->getBuffer(false);
                        for ($length = strlen($buffer); $openBrackets > 0 && $pos < $length; $pos++) {
                            switch ($buffer[$pos]) {
                                case '(':
                                    $openBrackets++;
                                    break;
                                case ')':
                                    $openBrackets--;
                                    break;
                                case '\\':
                                    $pos++;
                            }
                        }
                    } while ($openBrackets > 0 && $this->_reader->increaseLength());

                    $result = substr($buffer, $startPos, $openBrackets + $pos - $startPos - 1);
                    $this->_reader->setOffset($pos);

                    return new SetaPDF_Core_Type_String(
                        $result,
                        true,
                        $this->_passOwningObjectToChilds ? $this->_currentObject : null
                    );

                case '<';
                    $this->_ensureExpectedValue($token, $expectedType);
                    $bufferOffset = $this->_reader->getOffset();

                    while (true) {
                        $buffer = $this->_reader->getBuffer(false);
                        $pos = strpos($buffer, '>', $bufferOffset);
                        if ($pos === false) {
                            if (!$this->_reader->increaseLength()) {
                                return false;
                            }
                            continue;
                        }

                        $result = substr($buffer, $bufferOffset, $pos - $bufferOffset);
                        $this->_reader->setOffset($pos + 1);

                        return new SetaPDF_Core_Type_HexString(
                            $result,
                            false,
                            $this->_passOwningObjectToChilds ? $this->_currentObject : null
                        );
                    }
                    break;

                case '<<';
                    $this->_ensureExpectedValue($token, $expectedType);
                    $entries = [];

                    while (($token = $this->_getNextToken()) !== '>>') {
                        if (($key = $this->_readValue($token)) === false) {
                            return false;
                        }

                        // Ensure the first value to be a Name object
                        if (!($key instanceof SetaPDF_Core_Type_Name)) {
                            $this->skipUntilToken('>>');
                            break;
                        }

                        try {
                            if (($value = $this->readValue()) === false) {
                                return false;
                            }
                        } catch (SetaPDF_Core_Parser_Pdf_InvalidTokenException $e) {
                            $value = SetaPDF_Core_Type_Null::getInstance();
                        }

                        // Catch missing value
                        if ($value instanceof SetaPDF_Core_Type_Token && $value->getValue() === '>>') {
                            $entries[] = new SetaPDF_Core_Type_Dictionary_Entry($key, SetaPDF_Core_Type_Null::getInstance());
                            break;
                        }

                        $entries[] = new SetaPDF_Core_Type_Dictionary_Entry($key, $value);
                    }

                    return new SetaPDF_Core_Type_Dictionary($entries);

                case '[';
                    $this->_ensureExpectedValue($token, $expectedType);
                    $result = [];

                    // Recurse into this function until we reach the end of the array.
                    while (($token = $this->_getNextToken()) !== ']') {
                        try {
                            if ($token === false || ($value = $this->_readValue($token)) === false) {
                                return false;
                            }
                        } catch (SetaPDF_Core_Parser_Pdf_InvalidTokenException $e) {
                            continue;
                        }

                        $result[] = $value;
                    }

                    return new SetaPDF_Core_Type_Array($result);

                case '/':
                    $this->_ensureExpectedValue($token, $expectedType);
                    /* It is possible to contact the tokenizer directly, because
                     * the stack will only hold integers until the last element
                     */
                    if ($this->_tokenizer->isCurrentByteRegularCharacter()) {
                        return new SetaPDF_Core_Type_Name($this->_getNextToken(), true);
                    }

                    return new SetaPDF_Core_Type_Name('', true);

                case 'true':
                case 'false':
                    $this->_ensureExpectedValue($token, $expectedType);
                    return new SetaPDF_Core_Type_Boolean($token === 'true');

                case 'null':
                    $this->_ensureExpectedValue($token, $expectedType);
                    return SetaPDF_Core_Type_Null::getInstance();
            }

            if ($expectedType !== null && $expectedType !== SetaPDF_Core_Type_Token::class) {
                throw new SetaPDF_Core_Parser_Pdf_InvalidTokenException('Got unexpected token type.');
            }

            // if there's a minus sign in the token, remove all but the one in the first byte and
            // check if we end at a numeric value
            $tmpToken = $token;
            if (strpos($tmpToken, '-') !== false) {
                $tmpToken = $tmpToken[0] . str_replace('-', '', substr($tmpToken, 1));
            }
            if (is_numeric($tmpToken)) {
                return new SetaPDF_Core_Type_Numeric($tmpToken);
            }

            return new SetaPDF_Core_Type_Token($token);
        }

        if (($token2 = $this->_tokenizer->readToken()) !== false) {
            if (is_numeric($token2)) {
                if (($token3 = $this->_tokenizer->readToken()) !== false) {
                    switch ($token3) {
                        case 'R':
                            if (
                                $expectedType !== null &&
                                $expectedType !== SetaPDF_Core_Type_IndirectReference::class
                            ) {
                                throw new SetaPDF_Core_Parser_Pdf_InvalidTokenException(
                                    'Got unexpected token type.'
                                );
                            }

                            try {
                                return new SetaPDF_Core_Type_IndirectReference($token, $token2, $this->getOwner());
                            } catch (InvalidArgumentException $e) {
                                throw new SetaPDF_Core_Parser_Pdf_InvalidTokenException(
                                    sprintf('Invalid indirect reference (%s, %s).', $token, $token2)
                                );
                            }

                        case 'obj':
                            if (
                                $expectedType !== null &&
                                $expectedType !== SetaPDF_Core_Type_IndirectObject::class
                            ) {
                                throw new SetaPDF_Core_Parser_Pdf_InvalidTokenException(
                                    'Got unexpected token type.'
                                );
                            }

                            try {
                                $obj = new SetaPDF_Core_Type_IndirectObject(null, $this->getOwner(), $token, $token2);
                            } catch (InvalidArgumentException $e) {
                                throw new SetaPDF_Core_Parser_Pdf_InvalidTokenException(
                                    sprintf('Invalid indirect object (%s, %s).', $token, $token2)
                                );
                            }

                            $this->_currentObject = $obj;
                            /**
                             * @var SetaPDF_Core_Type_Dictionary $value
                             */
                            $value = $this->readValue();
                            if ($value === false) {
                                throw new SetaPDF_Core_Parser_Pdf_InvalidTokenException(
                                    sprintf('No value found for object %s, %s.', $token, $token2)
                                );
                            }

                            $this->_tokenizer->leapWhiteSpaces();
                            // Reset the buffer to offset = 0 and automatically
                            // increase the buffer length.
                            $this->_reader->reset(
                                $this->_reader->getPos() + $this->_reader->getOffset()
                            );

                            if (strpos($this->_reader->getBuffer(), 'stream') === 0) {
                                $this->_currentObject = null;
                                $offset = 6; // stream

                                // Find the first "newline"
                                while (($firstByte = $this->_reader->getByte($offset)) !== false) {
                                    if ($firstByte !== "\n" && $firstByte !== "\r") {
                                        $offset++;
                                    } else {
                                        break;
                                    }
                                }

                                if ($firstByte === false) {
                                    throw new SetaPDF_Core_Exception(
                                        'Unable to parse stream data. No newline after the stream keyword found.'
                                    );
                                }

                                $sndByte = $this->_reader->getByte($offset + 1);
                                if ($firstByte === "\n" || $firstByte === "\r") {
                                    $offset++;
                                }

                                if ($sndByte === "\n" && $firstByte !== "\n") {
                                    $offset++;
                                }

                                $this->_reader->setOffset($offset);
                                $pos = $this->_reader->getPos();
                                $pos += $offset;

                                try {
                                    $length = $value->offsetGet('Length');
                                    if ($length === null) {
                                        throw new UnexpectedValueException();
                                    }

                                    try {
                                        $length = $length->ensure()->getValue();
                                    } catch (SetaPDF_Core_Type_IndirectReference_Exception $e) {
                                        throw new UnexpectedValueException();
                                    }

                                    /* 0 bytes lengths is very uncommon, so ensure that the stream has
                                     * really a length of zero bytes by searching for the endstream key-
                                     * word.
                                     */
                                    if ($length == 0) {
                                        throw new UnexpectedValueException();
                                    }

                                    $this->_reader->reset($pos, $length);
                                    $buffer = $this->_reader->getBuffer();

                                    $this->_reader->reset($pos + $length);

                                    // ensure, that the next token is the "endstream" keyword
                                    $nextToken = $this->_getNextToken();
                                    if ($nextToken !== 'endstream') {
                                        throw new UnexpectedValueException();
                                    }

                                } catch (UnexpectedValueException $e) {
                                    $this->_reader->reset($pos);
                                    // TODO: Change to read line by line and match
                                    //       only the first 8 characters of that line
                                    //       The current version will also stop if the
                                    //       endstream token will be found within the stream
                                    while (true) {
                                        $buffer = $this->_reader->getBuffer(false);
                                        $length = strpos($buffer, 'endstream');
                                        if ($length === false) {
                                            if (!$this->_reader->increaseLength(100000)) {
                                                if ($expectedType !== null) {
                                                    throw new SetaPDF_Core_Parser_Pdf_InvalidTokenException(
                                                        'Got unexpected token type.'
                                                    );
                                                }

                                                return false;
                                            }
                                            continue;
                                        }
                                        break;
                                    }

                                    $buffer = substr($buffer, 0, $length);
                                    $lastByte = substr($buffer, -1);

                                    /* Check for EOL marker =
                                     *   CARRIAGE RETURN (\r) and a LINE FEED (\n) or just a LINE FEED (\n},
                                     *   and not by a CARRIAGE RETURN (\r) alone
                                     */
                                    if ($lastByte === "\n") {
                                        $buffer = substr($buffer, 0, -1);

                                        $lastByte = substr($buffer, -1);
                                        if ($lastByte === "\r") {
                                            $buffer = substr($buffer, 0, -1);
                                        }
                                    }

                                    // There are streams in the wild, which have only white signs in them but need to be
                                    // parsed manually due to a problem encountered before. We should set them to empty
                                    // streams to avoid problems in further processing (e.g. applying of filters).
                                    if (trim($buffer) === '') {
                                        $buffer = '';
                                    }

                                    $length = strlen($buffer);
                                }

                                $streamType = null;
                                if ($_streamType = $value->getValue('Type')) {
                                    $streamType = $_streamType->getValue();
                                }

                                switch ($streamType) {
                                    case 'ObjStm':
                                        $streamClass = SetaPDF_Core_Type_ObjectStream::class;
                                        break;
                                    default:
                                        $streamClass = SetaPDF_Core_Type_Stream::class;
                                }

                                $stream = new $streamClass(
                                    $value,
                                    $buffer,
                                    $this->_passOwningObjectToChilds ? $obj : null
                                );

                                if ($streamType === 'ObjStm') {
                                    $stream->setOwner($obj);
                                }

                                $obj->setValue($stream);
                                $buffer = null;

                                $this->_reader->reset($pos + $length);

                                // jump over the last "endstream" token
                                $nextToken = $this->_getNextToken();
                                if ($nextToken !== 'endstream') {
                                    $this->_tokenizer->pushStack($nextToken);
                                }

                                // jump over the last "endobj" token
                                $nextToken = $this->_getNextToken();
                                if ($nextToken !== 'endobj') {
                                    $this->_tokenizer->pushStack($nextToken);
                                }

                                return $obj;

                            }

                            $this->_currentObject = null;
                            $nextToken = $this->_getNextToken();
                            if ($nextToken !== 'endobj') {
                                $this->_tokenizer->pushStack($nextToken);
                            }

                            $obj->setValue($value);

                            return $obj;
                    }
                    $this->_tokenizer->pushStack($token3);
                }
            }
            $this->_tokenizer->pushStack($token2);
        }

        if ($expectedType !== null && $expectedType !== SetaPDF_Core_Type_Numeric::class) {
            throw new SetaPDF_Core_Parser_Pdf_InvalidTokenException(
                'Got unexpected token type.'
            );
        }

        return new SetaPDF_Core_Type_Numeric($token);
    }
}