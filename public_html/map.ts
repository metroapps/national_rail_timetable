import {View} from 'ol';
import {Control, defaults} from 'ol/control';
import {Coordinate} from 'ol/coordinate';
import Feature from 'ol/Feature';
import Point from 'ol/geom/Point';
import {circular} from 'ol/geom/Polygon';
import TileLayer from 'ol/layer/Tile';
import VectorLayer from 'ol/layer/Vector';
import Map from 'ol/Map';
import 'ol/ol.css';
import {fromLonLat, transform} from 'ol/proj';
import {register} from 'ol/proj/proj4';
import {OSM} from 'ol/source';
import VectorSource from 'ol/source/Vector';
import {Icon, Stroke, Style} from 'ol/style';
import proj4 from 'proj4';
import $ from 'jquery';

const source = new VectorSource();
const geolocation_source = new VectorSource();
function osgb36_to_web_mercator(coordinate : Coordinate) : Coordinate {
    return transform(coordinate, 'EPSG:27700', 'EPSG:3857');
}

function initialise_map(element : HTMLElement) : Map {
    element.style.display = 'block';
    const map = new Map(
        {
            target : element,
            layers : [
                new TileLayer(
                    {
                        source : new OSM(),
                    }
                ),
                new VectorLayer(
                    {source : geolocation_source}
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
                ),
            ],
            view : new View,
        }
    );

    const locate = $('<div class="ol-control ol-unselectable locate"><button><img src="/images/current_location.png" alt="Current location"/></button></div>')[0];
    let registered = false;
    let auto_zoom = false;
    let auto_zooming = false;
    const zoom_to_current_location = function () {
        if (auto_zoom && !geolocation_source.isEmpty()) {
            auto_zooming = true;
            map.getView().fit(geolocation_source.getExtent(), {
                maxZoom: 14,
                duration: 500,
            });
        }
    };
    locate.addEventListener('click', function () {
        auto_zoom = true;
        if (!registered) {
            // noinspection ReuseOfLocalVariableJS
            registered = true;
            navigator.geolocation.watchPosition(
                function (pos) {
                    const coords = [pos.coords.longitude, pos.coords.latitude];
                    const accuracy = circular(coords, pos.coords.accuracy);
                    geolocation_source.clear(true);
                    geolocation_source.addFeatures([
                        new Feature(
                            accuracy.transform('EPSG:4326', map.getView().getProjection())
                        ),
                        new Feature(new Point(fromLonLat(coords))),
                    ]);
                    zoom_to_current_location();
                },
                function (error) {
                    console.log(`geolocation failed: ${error.message}`);
                },
                {
                    enableHighAccuracy: true,
                }
            );
        }
        // noinspection ReuseOfLocalVariableJS
        zoom_to_current_location();
    });
    map.addControl(
        new Control({
            element: locate,
        })
    );
    map.on(
        'movestart'
        , () => {
            if (auto_zooming) {
                // noinspection ReuseOfLocalVariableJS
                auto_zooming = false;
            } else {
                // noinspection ReuseOfLocalVariableJS
                auto_zoom = false;
            }
        }
    );

    proj4.defs('EPSG:27700', '+proj=tmerc +lat_0=49 +lon_0=-2 +k=0.9996012717 +x_0=400000 +y_0=-100000 +ellps=airy +datum=OSGB36 +units=m +no_defs');
    register(proj4);

    return map;
}

export {initialise_map, osgb36_to_web_mercator, source};