import $ from 'jquery';
import {transform} from 'ol/proj';
import {getDistance} from 'ol/sphere';

function initialise_form() {
    let $current_location = $('#current_location');
    $current_location.on(
        'click'
        , () => {
            const $geolocation_message = $('#geolocation_message');
            $geolocation_message.text('Getting closest station...');
            $('.ol-control.locate button').trigger('click');
            navigator.geolocation.getCurrentPosition(
                position => {
                    const LIMIT = 10000;
                    const result = $('option[data-easting][data-northing]').toArray().reduce(
                        function (carry : [JQuery, number] | null, element) : [JQuery, number] | null {
                            const $this = $(element);
                            const distance = getDistance(
                                [position.coords.longitude, position.coords.latitude]
                                , transform(
                                    [Number($this.attr('data-easting')), Number($this.attr('data-northing'))],
                                    'EPSG:27700',
                                    'EPSG:4326'
                                )
                            );
                            return distance < LIMIT && (
                                carry === null || distance < carry[1]
                            )
                                ? [$this, distance]
                                : carry;
                        }
                        , null
                    );
                    const distance_formatter = new Intl.NumberFormat(
                        'en-GB'
                        , {
                            style : 'unit',
                            unit : 'meter',
                            maximumFractionDigits : 0,
                        }
                    );
                    $geolocation_message.text(
                        result === null
                            ? `No station found within ${distance_formatter.format(LIMIT)}`
                            : `Closest station: ${result[0].text()} (${distance_formatter.format(result[1])})`
                    );
                    if (result !== null) {
                        $('#query_form').find('input[name="station"]').val(result[0].attr('value') ?? '');
                    }
                } // success
                , () => $geolocation_message.text('Error getting location') // error
            );
        }
    );
    $current_location.css('display', 'initial');
}

export {initialise_form};