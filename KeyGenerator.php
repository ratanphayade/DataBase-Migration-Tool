<?php

class KeyGenerator
{

    private $key;
    private $bitCount;
    private $codeLength;

    public function __construct($codeType)
    {
        switch ($codeType) {
            case 'public_id' : $this->bitCount = 50;
                $this->codeLength = 10;
                $this->key = 'syljM20eFHxcnZPOXqrNpkSAvt3IiaJoYumKb5gLREfzhT9VQ1UGW78wB6d4CD-_';
                break;
        }
    }

    /**
     * Get encoded string from id
     * @author Johny
     * @param integer $value id to be encoded
     * @return string, encoded string.s
     */
    public function getCodeFromValue($value)
    {
        $cipherText = $this->encrypt($value);
        return $this->encode($cipherText);
    }

    /**
     * Get id from an encoded string.
     * @author Johny
     * @param string $code encoded string
     * @return integer id
     */
    public function getValueFromCode($code)
    {
        $cipherText = $this->decode($code);
        return $this->encrypt($cipherText);
    }

    /**
     * Round function used in each feistel rounds.
     * @author Johny.
     * @param integer $number
     * @param integer $bit_mask
     * @return integer
     */
    protected function roundFunction($number, $bit_mask)
    {
        return ((($number ^ 638291) + 49) << 1) & $bit_mask;
    }

    /**
     * Encrypt a given number using feistel networks.
     * This specific implementation uses 16 rounds with above round function.
     * @author Johny.
     * @param integer $number number to be encrypted
     * @return integer, encrypted value.
     */
    protected function encrypt($number)
    {
        $bit_mask = ( 1 << $this->bitCount / 2 ) - 1;

        $left = $number >> $this->bitCount / 2;
        $right = $number & $bit_mask;

        for ($i = 0; $i < 16; $i++) {
            $left = $left ^ $this->roundFunction($right, $bit_mask);
            $temp = $left;
            $left = $right;
            $right = $temp;
        }

        return ( $right << $this->bitCount / 2 ) | $left;
    }

    /**
     * Generated an alpha numeric string from integer
     * @author Johny.
     * @param integer $number to be encoded.
     * @return string, encoded string for the number.
     */
    protected function encode($number)
    {
        $output = "";
        for ($i = 0; $i < $this->codeLength; $i++) {
            $index = $number & ( ( 1 << ($this->bitCount / $this->codeLength) ) - 1 );
            $output = $this->key[$index] . $output;
            $number = $number >> ($this->bitCount / $this->codeLength);
        }

        return $output;
    }

    /**
     * Get the number from encoded alphanumeric string.
     * @param string $code alphanumeric string
     * @return integer, number decoded from string.
     */
    protected function decode($code)
    {
        $output = 0;
        for ($i = 0; $i < $this->codeLength; $i++) {
            $index = strpos($this->key, substr($code, $i, 1)) . " ";
            $output = $output << ($this->bitCount / $this->codeLength);
            $output = $output | $index & ( ( 1 << ($this->bitCount / $this->codeLength) ) - 1 );
        }

        return $output;
    }
}
