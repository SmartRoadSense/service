<?php

class SrsZoomFilter {
    const DEF_MAX_ZOOM = 16;
    const DEF_MIN_ZOOM = 1;

    private $minZoom, $maxZoom;

    function __construct($minZoom, $maxZoom) {
        $this->minZoom = $minZoom;
        $this->maxZoom = $maxZoom;
    }

    public function getModuleFilter($currentZoom){
        // max zoom level is MAX_ZOOM, when we should not filter,
        // so apply a filter of 1.
        if ($currentZoom > $this->maxZoom) {
            $currentZoom = $this->maxZoom;
        }

        // min zoom level is MIN_ZOOM, when we should filter at
        // maximum rate.
        if($currentZoom < $this->minZoom){
            $currentZoom = $this->minZoom;
        }

        return pow(2, $this->maxZoom - $currentZoom);
    }
}