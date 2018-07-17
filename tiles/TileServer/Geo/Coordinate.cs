using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace TileServer.Geo {

    public readonly struct Coordinate {

        public Coordinate(double lat, double lng) {
            Latitude = lat;
            Longitude = lng;
        }

        public readonly double Latitude;

        public readonly double Longitude;

    }

}
