<?php
/**
 * Created by PhpStorm.
 * User: ailuoy
 * Date: 2017/11/10
 * Time: 11:58
 */

namespace App\Foundation\Handlers;

/**
 * Class AesEncryptHandler
 * @package App\Foundation\Handlers
 */
class AesEncryptHandler
{
    /**
     * @var string
     */
    private $key = '';
    /**
     * @var string
     */
    private $iv = '';
    /**
     * @var bool|mixed
     */
    protected $isOpensslEncrypt = true;

    /**
     * AesEncrypt constructor.
     */
    public function __construct()
    {
        $this->setKey(config('custom.encrypt.key'));
        $this->setIv(config('custom.encrypt.iv'));
        $this->isOpensslEncrypt = version_compare(phpversion(), '7.1', '>=');
    }

    /**
     * @return string
     */
    private function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    private function getIv()
    {
        return $this->iv;
    }

    /**
     * @param  string  $key
     */
    private function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @param  string  $iv
     */
    private function setIv($iv)
    {
        $this->iv = $iv;
    }

    /**
     * String encryption algorithm
     *
     * @param  array  $str
     *
     * @return string
     */
    public function encrypt(array $str)
    {
        if ($this->isOpensslEncrypt) {
            $encrypt_str = openssl_encrypt(
                json_encode($str),
                'AES-128-CBC',
                $this->getKey(),
                OPENSSL_RAW_DATA,
                $this->getIv()
            );
        } else {
            $str         = $this->addPKCS7Padding($str);
            $encrypt_str = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->getKey(), $str, MCRYPT_MODE_CBC, $this->getIv());
        }

        return base64_encode($encrypt_str);
    }

    /**
     * String decryption algorithm
     *
     * @param  string  $str
     *
     * @return string
     */
    public function decrypt($str)
    {
        $str = base64_decode($str);
        if ($this->isOpensslEncrypt) {
            $encrypt_str = openssl_decrypt($str, 'AES-128-CBC', $this->getKey(), OPENSSL_RAW_DATA, $this->getIv());
        } else {
            $encrypt_str = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->getKey(), $str, MCRYPT_MODE_CBC, $this->getIv());
            $encrypt_str = $this->stripPKSC7Padding($encrypt_str);
        }

        return $encrypt_str;
    }

    /**
     * Encryption fill algorithm
     *
     * @param  string  $source
     *
     * @return string
     */
    private function addPKCS7Padding($source)
    {
        $source = trim($source);
        $block  = mcrypt_get_block_size('rijndael-128', 'cbc');
        $pad    = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char   = chr($pad);
            $source .= str_repeat($char, $pad);
        }

        return $source;
    }

    /**
     * Remove the fill algorithm
     *
     * @param  string  $source
     *
     * @return string
     */
    private function stripPKSC7Padding($source)
    {
        $char   = substr($source, -1);
        $num    = ord($char);
        $source = substr($source, 0, -$num);

        return $source;
    }
}
