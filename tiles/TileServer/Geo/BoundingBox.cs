using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace TileServer.Geo {

    public readonly struct BoundingBox {

        public BoundingBox(double w, double s, double e, double n) {
            West = w;
            South = s;
            East = e;
            North = n;
        }

        public readonly double West;
        public readonly double South;
        public readonly double East;
        public readonly double North;

        public Coordinate SouthWest {
            get {
                return new Coordinate(South, West);
            }
        }

        public Coordinate NorthEast {
            get {
                return new Coordinate(North, East);
            }
        }

    }

}
