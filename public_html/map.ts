import $ from 'jquery';
import {Feature, View} from 'ol';
import {Coordinate} from 'ol/coordinate';
import {LineString, Point} from 'ol/geom';
import TileLayer from 'ol/layer/Tile';
import VectorLayer from 'ol/layer/Vector';
import Map from 'ol/Map';
import 'ol/ol.css';
import {transform} from 'ol/proj';
import {register} from 'ol/proj/proj4';
import {OSM} from 'ol/source';
import VectorSource from 'ol/source/Vector';
import {Icon, Stroke, Style} from 'ol/style';

$('#map').css('display', 'block');
const source = new VectorSource();
const view = new View;
new Map(
    {
        target : 'map',
        layers : [
            new TileLayer(
                {
                    source : new OSM(),
                }
            ),
            new VectorLayer(
                {
                    source,
                    style : new Style(
                        {
                            image : new Icon(
                                {
                                    src : '/images/train.svg',
                                    scale : 0.1,
                                }
                            ),
                            stroke : new Stroke(
                                {
                                    color : 'black',
                                    width : 3,
                                }
                            ),
                        }
                    ),
                }
            )
        ],
        view,
    }
);

const proj4 = (await import('proj4')).default;
proj4.defs('EPSG:27700', '+proj=tmerc +lat_0=49 +lon_0=-2 +k=0.9996012717 +x_0=400000 +y_0=-100000 +ellps=airy +datum=OSGB36 +units=m +no_defs');
register(proj4);

function osgb36_to_web_mercator(coordinate : Coordinate) : Coordinate {
    return transform(coordinate, 'EPSG:27700', 'EPSG:3857');
}

let rendered = new Set();
$('tr[data-crs]').each(
    function (this : HTMLElement) {
        const $this = $(this);
        const crs = $this.attr('data-crs')
        if (!rendered.has(crs)) {
            source.addFeature(
                new Feature(
                    {
                        geometry : new Point(
                            osgb36_to_web_mercator(
                                [Number($this.attr('data-easting')), Number($this.attr('data-northing'))]
                            )
                        ),
                        name : $this.attr('data-name'),
                    }
                )
            );
            rendered.add(crs);
        }
    }
);
$('table[data-line]').each(
    function (this : HTMLElement) {
        let array = JSON.parse($(this).attr('data-line') ?? '');
        if (array instanceof Array) {
            source.addFeature(
                new Feature(new LineString(array.map(osgb36_to_web_mercator)))
            );
        }
    }
);

view.fit(source.getExtent(), {padding : [15, 15, 15, 15]});