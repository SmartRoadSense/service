using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace TileServer.DataModel {

    public class BoundingBoxResponse {

        public string Type { get; set; }

        public List<Feature> Features { get; set; }

    }

}
