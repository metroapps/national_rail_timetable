import $ from 'jquery';
import {Feature} from 'ol';
import {Point} from 'ol/geom';
import {initialise_map, osgb36_to_web_mercator, source} from './map';
import './schedule.css';

const map_element = document.getElementById('map');
if (map_element) {
    const map = initialise_map(map_element);
    $('option[data-easting][data-northing]').each(
        function () {
            const $this = $(this);
            source.addFeature(
                new Feature(
                    {
                        geometry : new Point(
                            osgb36_to_web_mercator(
                                [Number($this.attr('data-easting')), Number($this.attr('data-northing'))]
                            )
                        ),
                        crs : $this.attr('value'),
                    }
                )
            );

        }
    );
    map.on(
        'click'
        , function (event) {
            map.forEachFeatureAtPixel(
                event.pixel
                , function (feature) {
                    $('form input[name="station"]').val(feature.get('crs'))
                    return true;
                }
            );
        }
    );

    map.on(
        'pointermove'
        , function (event) {
            const pixel = map.getEventPixel(event.originalEvent);
            map_element.style.cursor = map.hasFeatureAtPixel(pixel) ? 'pointer' : '';
        }
    );
    map.getView().fit(
        osgb36_to_web_mercator([-103976.3, -16703.87]).concat(osgb36_to_web_mercator([652897.98, 1199851.44]))
        , {padding : [15, 15, 15, 15]}
    );
}