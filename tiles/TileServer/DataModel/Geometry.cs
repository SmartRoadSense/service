using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace TileServer.DataModel {

    public class Geometry {

        public string Type { get; set; }

        public double[] Coordinates { get; set; }

        public Geo.Coordinate ToGeoCoordinate() {
            if (Coordinates.Length < 2)
                throw new InvalidOperationException("Coordinates array does not contain at least two values");

            return new Geo.Coordinate(
                Coordinates[1],
                Coordinates[0]
            );
        }

    }

}
