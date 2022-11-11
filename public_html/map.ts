import {View} from 'ol';
import {Coordinate} from 'ol/coordinate';
import TileLayer from 'ol/layer/Tile';
import VectorLayer from 'ol/layer/Vector';
import Map from 'ol/Map';
import {transform} from 'ol/proj';
import {register} from 'ol/proj/proj4';
import {OSM} from 'ol/source';
import VectorSource from 'ol/source/Vector';
import {Icon, Stroke, Style} from 'ol/style';
import proj4 from 'proj4';

const source = new VectorSource();
function osgb36_to_web_mercator(coordinate : Coordinate) : Coordinate {
    return transform(coordinate, 'EPSG:27700', 'EPSG:3857');
}

function initialise_map(element : HTMLElement) : Map {
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
            view : new View,
        }
    );

    proj4.defs('EPSG:27700', '+proj=tmerc +lat_0=49 +lon_0=-2 +k=0.9996012717 +x_0=400000 +y_0=-100000 +ellps=airy +datum=OSGB36 +units=m +no_defs');
    register(proj4);

    return map;
}

export {initialise_map, osgb36_to_web_mercator, source};