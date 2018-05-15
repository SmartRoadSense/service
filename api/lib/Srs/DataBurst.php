<?php
/**
 * Created by PhpStorm.
 * User: gioele
 * Date: 23/03/15
 * Time: 11:41
 */

namespace Srs;

use AppToken;
use DateTime;
use GeoJson\GeoJson;
use GeoJson\Geometry\Point;
use SingleData;
use Srs\WebService\MalformedRequestException;
use Srs\WebService\MissingPositionException;

class DataBurst {
    /**
     * @var int
     */
    public $id;
    /**
     * @var int
     */
    public $source;
    /**
     * @var DateTime
     */
    public $time;
    /**
     * @var String
     */
    public $track;
    /**
     * @var SingleData[]
     */
    public $data;

    /**
     * @param array $jsonArray
     *
     * @return DataBurst
     * @throws MissingPositionException if some points don't specify a valid position
     * @throws MalformedRequestException if some fields are missing or in the wrong format.
     */
    public static function fromJSONArray(array $jsonArray) {
        $obj = new DataBurst();
        $obj->source = $jsonArray["source"];
        $obj->time = new DateTime($jsonArray["time"]);
        $obj->track = $jsonArray["track"];
        $obj->data = array();

        foreach ($jsonArray["data"] as $sd) {
            $singleData = new \SingleData();
            $singleData->setBearing($sd["bearing"]);
            $singleData->setDate($sd["time"]);
            $singleData->setDuration($sd["duration"]);

            // check that position is a GeoJSON Point
            $position = GeoJson::jsonUnserialize($sd["position"]);
            if (!($position instanceof Point))
                throw new MissingPositionException;
            // Temporary put Position in a JSON string (used to insert with PostGIS)
            $singleData->setPosition(json_encode($sd["position"]));

            $singleData->setPositionResolution($sd["position-resolution"]);
            if (isset($sd["source"]))
                $singleData->setSource($sd["source"]);
            if (isset($sd["track"]))
                $singleData->setTrack($sd["track"]);

            if (count($sd["values"]) < 5)
                throw new MalformedRequestException;

            // unpack values array (x,y,z,PPE,speed,<empty>) and set each property
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

    public static function checkOwner(DataBurst $data, AppToken $loggedToken) {
        // retrieve the Source from DB
        $source = (new \SourceQuery())->findPk($data->source);

        if ($source == null)
            return false;

        return trim($source->getOwner()) == "" || // if there is no owner ignore security...
        strtolower($source->getOwner()) == strtolower($loggedToken->getDevice()); // case insensitive equality
    }
}