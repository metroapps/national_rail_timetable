import $ from 'jquery';
import {Feature} from 'ol';
import {Point} from 'ol/geom';
import {initialise_map, osgb36_to_web_mercator, source} from './map';
import './schedule.css';

const query = new URLSearchParams(window.location.search.substring(1));
const mode = query.get('mode');
// FIXME: doesn't work properly if the browser is in another time zone
const connecting_time_string = query.get('connecting_time');
const reference_timestamp = (
    connecting_time_string
        ? new Date(connecting_time_string)
        : new Date()
).getTime() / 1000;

// departure board
const $rows = $('tr[data-timestamp]');
const $filtered_rows = $rows.filter(
    function () {
        const timestamp = Number($(this).attr('data-timestamp'));
        return mode === 'arrivals' ? timestamp <= reference_timestamp : timestamp >= reference_timestamp;
    }
);
($filtered_rows.length ? $filtered_rows : $rows.last())[0]?.scrollIntoView(true);

// timetable
$('.container').each(
    function () {
        const $columns = $(this).find('th[data-timestamp]');
        const $filtered_columns = $columns.filter(
            function () {
                const timestamp = Number($(this).attr('data-timestamp'));
                return mode === 'arrivals' ? timestamp > reference_timestamp : timestamp >= reference_timestamp;
            }
        );
        const element = ($filtered_columns.length ? $filtered_columns : $columns.last())[0];
        if (element) {
            this.scrollLeft = element.offsetLeft - (
                mode === 'arrivals' ? this.offsetWidth : $(this).find('th:first-child')[0].offsetWidth
            );
        }
        if (mode === 'arrivals') {
            this.scrollTop = $(this).find('tbody')[0].offsetHeight;
        }
    }
)

$('#go_to_top').click(() => window.scrollTo(0, 0));

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