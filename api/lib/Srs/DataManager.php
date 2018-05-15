<?php
/**
 * Created by PhpStorm.
 * User: gioele
 * Date: 23/03/15
 * Time: 11:57
 */

namespace Srs;

use DateTime;
use PDO;
use Propel\Runtime\Propel;
use Rhumsaa\Uuid\Uuid;
use SingleData;

class DataManager {

    /**
     * @param DataBurstV2 $data
     *
     * @return \Track
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public static function insertV2(DataBurstV2 $data) {

        ini_set('max_execution_time', 300);

        //$star_time = microtime(true);

        // First update (or insert) specified Track
        $data->track->save();
        //echo " track_save: ". (microtime(true) - $star_time);

        //$star_time = microtime(true);
        // then, for each point
        foreach ($data->data as $single) {
            // assign this track
            $single->setTrack($data->track);
            // and try to fix date (if smartphone date is not aligned with server date)
            $single->setDate(DataManager::tryFixTime($single->getDate(null), $data->time));
        }

        //echo " set_track: ". (microtime(true) - $star_time);

        //$star_time = microtime(true);
        // Finally, insert points
        DataManager::multipleInsert($data->data);

        //echo " multiple_insert: ". (microtime(true) - $star_time);

        return $data->track;
    }

    /**
     * @param DataBurst $data
     *
     * @return String
     */
    public static function insert(DataBurst $data) {
        $track = $data->track;
        if ($track == null) {
            // Generate a version 4 (random) UUID object
            $track = Uuid::uuid4()->toString();
        }

        // for each data contained, apply source and try to fix date
        foreach ($data->data as $single) {
            $single->setSourceId($data->source);
            $single->setDate(DataManager::tryFixTime($single->getDate(null), $data->time));
            if ($single->getTrack() == null) {
                $single->setTrack($track);
            }
        }

        DataManager::multipleInsert($data->data);

        return $track;
    }

    /**
     * @param SingleData[] $data
     */
    public static function multipleInsert($data) {

        $profile = array();
        $profile['header'] = 0;
        $profile['sql_prepare'] = 0;
        $profile['prepare'] = 0;
        $profile['execute'] = 0;


        //$start_time = microtime(true);
        // Multiple insert in a unique transaction
        $connection = Propel::getConnection();
        $connection->beginTransaction();

        /*

        // Create the prepared statement with custom SQL (for PGSQL arrays and PostGIS geometry)
        $sql = "INSERT INTO single_data(track_id, duration, position_resolution, bearing, date, meta, ppe, speed,
                                    position)
              VALUES(:track_id, :duration, :position_resolution, :bearing, :date, :meta, :ppe, :speed,
                                    st_setsrid(
                                      st_geomfromgeojson(:position),
                                      4326));";
        */

        //$profile['header'] = microtime(true) - $start_time;
        //$start_time = microtime(true);

        // Create the prepared statement with custom SQL (for PGSQL arrays and PostGIS geometry)
        $sql = "INSERT INTO single_data(track_id, duration, position_resolution, bearing, date, meta, ppe, speed,
                                    position)
              VALUES(:track_id, :duration, :position_resolution, :bearing, :date, :meta, :ppe, :speed,
                                    st_setsrid(
                                      st_geomfromgeojson(:position),
                                      4326));";
        $st = $connection->prepare($sql);

        //$profile['sql_prepare'] = microtime(true) - $start_time;


        // then insert each SingleData point
        foreach ($data as $d) {

            //$start_time = microtime(true);

            // bind parameter for statement for data insertion
            $st->bindValue("track_id", $d->getTrack()->getTrackId(), PDO::PARAM_INT);
            $st->bindValue("duration", $d->getDuration(), PDO::PARAM_STR);
            $st->bindValue("position_resolution", $d->getPositionResolution(), PDO::PARAM_INT);
            $st->bindValue("bearing", $d->getBearing(), PDO::PARAM_STR);
            $st->bindValue("date", $d->getDate() ? $d->getDate("Y-m-d H:i:s") : null, PDO::PARAM_STR);

            $st->bindValue("ppe", $d->getPpe(), PDO::PARAM_STR);
            $st->bindValue("speed", $d->getSpeed(), PDO::PARAM_STR);
            $st->bindValue("position", $d->getPosition(), PDO::PARAM_STR);
            // merge array values (variable required by PDO - reference variable)
            $meta = "{" . implode(",", $d->getMeta()) . "}";
            $st->bindValue("meta", $meta, PDO::PARAM_STR);

            //$profile['prepare'] += (microtime(true) - $start_time);

            //$start_time = microtime(true);
            // execute prepared statement
            $st->execute();
            //$profile['execute'] += (microtime(true) - $start_time);
        }

        //$commit_start_time = microtime(true);

        // Finally commit transaction to effectively save points on DB
        $connection->commit();

        //$profile['total'] = (microtime(true) - $commit_start_time);

        //$avg_prepare = $profile['prepare'] / count($data);
        //$avg_execute = $profile['execute'] / count($data);

        //echo "\n\n\n
         //header_prepare:".$profile['header']."
         //sql_prepare:".$profile['sql_prepare']."
         //tot_prepare:".$profile['prepare']." avg_execute:$avg_execute tot_execute:".$profile['execute']."  commit:".$profile['total']."\n\n\n";
    }

    /**
     * If burst DateTime is very different from local DateTime, try to fix each SingleDate timestamp with the
     * difference (LocalTimestamp - BurstTimestamp)
     *
     * @param DateTime $targetDate
     * @param DateTime $burstTimestamp
     *
     * @return DateTime adjusted $targetDate
     */
    public static function tryFixTime(DateTime $targetDate, DateTime $burstTimestamp) {
        $localTimestamp = new DateTime;

        $interval = $burstTimestamp->diff($localTimestamp);

        LogUtil::get()->info("Timestamps:");
        LogUtil::get()->info("LocalTimestamp: " . print_r($localTimestamp, true));
        LogUtil::get()->info("BurstTimestamp: " . print_r($burstTimestamp, true));
        LogUtil::get()->info("TargetDate: " . print_r($targetDate, true));

        $diffSeconds = $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
        // if diff is greater than 5 minutes, adjust date
        if (abs($diffSeconds) > 5 * 60)
            $targetDate->add($interval);

        LogUtil::get()->info("Diff is $diffSeconds seconds from: " . print_r($interval, true));

        return $targetDate;
    }
}