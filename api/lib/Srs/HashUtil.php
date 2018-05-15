<?php
/**
 * Created by PhpStorm.
 * User: gioele
 * Date: 15/04/15
 * Time: 16:27
 */

namespace Srs;


class HashUtil {
    const HASHING_ALG_SHA3 = "sha512";

    /**
     * @param $original
     * @param $hashed
     *
     * @throws HashMismatchException
     */
    public static function verifyHash($original, $hashed){
        // check that the payload and the hash of the payload match!
        $localHash = openssl_digest($original, self::HASHING_ALG_SHA3, true);
        $localHashEncoded = base64_encode($localHash);

        if ($localHashEncoded != $hashed) {
            throw new HashMismatchException($hashed, $localHashEncoded);
        }
    }

    /**
     * @param $data
     *
     * @return string
     */
    public static function hashToSha3($data){
        return openssl_digest($data, self::HASHING_ALG_SHA3);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function hashToSha1($data){
        return sha1($data);
    }

}