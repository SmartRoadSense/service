<?php
/**
 * Created by PhpStorm.
 * User: gioele
 * Date: 15/04/15
 * Time: 09:22
 */

namespace Srs;


use DateTime;
use GeoJson\GeoJson;
use GeoJson\Geometry\Point;
use SingleData;
use Srs\WebService\AuthorizationException;
use Srs\WebService\MalformedRequestException;
use Srs\WebService\MissingPositionException;
use Srs\WebService\NotFoundException;
use Srs\WebService\SingularityRequestException;
use Track;
use TrackQuery;

/**
 * Class DataBurstV2
 *
 * How to test with custom data (to encrypt):
 * - Sign step (with private key)
 * openssl rsautl -sign -inkey private.pem -in <hash.txt> -out <hash.txt.ssl>
 *
 * - Verify step (with public key)
 * openssl rsautl -verify -inkey public.pem -in <hash.txt.ssl> -pubin -out <hash.txt.decrypted>
 *
 * @package Srs
 */
class DataBurstV2 {
    const VEHICLE_TYPE_MOTORCYCLE = 1;
    const VEHICLE_TYPE_CAR = 2;
    const VEHICLE_TYPE_TRUCK = 3;
    const ANCHORAGE_TYPE_MOBILE_MAT = 1;
    const ANCHORAGE_TYPE_MOBILE_BRACKET = 2;
    const ANCHORAGE_TYPE_POCKET = 3;
    /**
     * @var int
     */
    public $deviceId;
    /**
     * @var DateTime
     */
    public $time;
    /**
     * @var String
     */
    public $trackId;
    /**
     * @var String
     */
    public $secret;
    /**
     * @var  String
     */
    public $metadata;
    /**
     * @var int
     */
    public $vehicleType;
    /**
     * @var int
     */
    public $anchorageType;
    /**
     * @var SingleData[]
     */
    public $data;
    /** @var  Track */
    public $track;

    public function isAnonymous() {
        return $this->deviceId == null;
    }

    /**
     * @param array $jsonArray
     *
     * @return DataBurstV2
     * @throws AuthorizationException
     * @throws HashMismatchException
     * @throws MalformedRequestException
     * @throws MissingPositionException
     * @throws NotFoundException
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public static function fromJSONArray(array $jsonArray)
    {

        $obj = new DataBurstV2();
        if (isset($jsonArray["device-id"])){
            $obj->deviceId = $jsonArray["device-id"];
        }

        $obj->metadata = $jsonArray["metadata"];
        // check that AnchorageType is an allowed value
        if ($jsonArray["anchorage-type"] == self::ANCHORAGE_TYPE_MOBILE_BRACKET ||
            $jsonArray["anchorage-type"] == self::ANCHORAGE_TYPE_MOBILE_MAT ||
            $jsonArray["anchorage-type"] == self::ANCHORAGE_TYPE_POCKET
        )
            $obj->anchorageType = $jsonArray["anchorage-type"];
        // check that VehicleType is an allowed value
        if ($jsonArray["vehicle-type"] == self::VEHICLE_TYPE_CAR ||
            $jsonArray["vehicle-type"] == self::VEHICLE_TYPE_MOTORCYCLE ||
            $jsonArray["vehicle-type"] == self::VEHICLE_TYPE_TRUCK
        )
            $obj->vehicleType = $jsonArray["vehicle-type"];

        $obj->time = new DateTime($jsonArray["time"]);
        $obj->secret = $jsonArray["secret"]; // <-- hash of the real track-uuid generated on the device
        $obj->data = array();

        $payload = $jsonArray["payload"];
        $payloadHash = $jsonArray["payload-hash"];

        if (!$obj->isAnonymous()) {
            // the insert is not anonymous, so we must find the Device that required the insertion,
            // get its public key to decrypt the hash of the payload.

            // in this case payload-hash is encoded in base64, so we need to decode it:
            $payloadHash = base64_decode($payloadHash);

            // find matching device on DB
            $device = (new \DeviceQuery())->findPk($obj->deviceId);
            if ($device == null)
                throw new NotFoundException;
            // and its public key
            $devicePublicKey = $device->getPublicKey();

            // decrypt payload hash, according to the protocol
            if (!openssl_public_decrypt($payloadHash, $decryptedPayloadHash, $devicePublicKey))
                throw new AuthorizationException;
            $payloadHash = $decryptedPayloadHash;
        }

        // check that the payload and the hash of the payload match!
        HashUtil::verifyHash($payload, $payloadHash);

        $obj->track = new \Track();

        // update text metadata
        $obj->track->setMetadata($obj->metadata);
        $obj->track->setVehicleType($obj->vehicleType);
        $obj->track->setAnchorageType($obj->anchorageType);
        if (!$obj->isAnonymous()) {
            // if this call is not anonymous, we must set the device that created this track
            $obj->track->setDeviceId($obj->deviceId);
        }

        $obj->track->setSecret($obj->secret);
        $obj->track->setTrackId(null);

        // ok, the payload is verified, so parse it again from JSON, then analyze its points.
        $points = json_decode($payload, true);

        foreach ($points as $sd) {
            $singleData = new \SingleData();

            if (is_nan($sd["bearing"]))
                throw new MalformedRequestException;
            $singleData->setBearing($sd["bearing"]);

            $singleData->setDate($sd["time"]);

            if (is_nan($sd["duration"]))
                throw new MalformedRequestException;
            $singleData->setDuration($sd["duration"]);

            if (is_nan($sd["position-resolution"]))
                throw new MalformedRequestException;
            $singleData->setPositionResolution($sd["position-resolution"]);

            // check that position is a GeoJSON Point
            $position = GeoJson::jsonUnserialize($sd["position"]);
            if (!($position instanceof Point))
                throw new MissingPositionException;
            // Temporary put Position in a JSON string (used to insert with PostGIS)
            $singleData->setPosition(json_encode($sd["position"]));

            if (count($sd["values"]) < 5)
                throw new MalformedRequestException;

            // unpack values array (x,y,z,PPE,speed,<empty>) and set each property

            foreach ($sd["values"] as $d) {
                // check that each value is not nan
                if (is_nan($d))
                    throw new MalformedRequestException;
            }
            $meta = [floatval($sd["values"][0]),
                floatval($sd["values"][1]),
                floatval($sd["values"][2])];
            $singleData->setMeta($meta);
            $singleData->setPpe(floatval($sd["values"][3]));
            $singleData->setSpeed(floatval($sd["values"][4]));

            // add the SingleData object to array
            $obj->data[] = $singleData;
        }

        return $obj;
    }
}