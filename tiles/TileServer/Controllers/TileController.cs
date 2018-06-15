using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.Linq;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Mvc;

namespace TileServer.Controllers {

    [Route("api/srs-tiles/")]
    public class TileController : Controller {

        [HttpGet("{zoom}/{x}/{y}")]
        public ActionResult Get(int zoom, double x, double y) {
            Debug.WriteLine($"Tile {x},{y} zoom {zoom}");

            return null;
        }

    }

}
