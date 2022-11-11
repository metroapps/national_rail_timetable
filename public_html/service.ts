import $ from 'jquery';
import {Feature, Overlay} from 'ol';
import {FeatureLike} from 'ol/Feature';
import {LineString, Point} from 'ol/geom';
import {Icon, Style} from 'ol/style';
import {initialise_map, osgb36_to_web_mercator, source} from './map';
import './service.css';

function require_element(id : string) : HTMLElement {
    const element = document.getElementById(id);
    if (element === null) {
        throw new Error(`Element ${id} cannot be found.`);
    }
    return element;
}

const map_element = require_element('map');
const map = initialise_map(map_element);

const rendered = new Set();
const request_stop_style = new Style(
    {
        image : new Icon(
            {
                src : '/images/hand.svg',
                scale : 0.25,
            }
        )
    }
);
$('tr[data-crs]').each(
    function (this : HTMLElement) {
        const $this = $(this);
        const crs = $this.attr('data-crs')
        if (!rendered.has(crs)) {
            const feature = new Feature(
                {
                    geometry : new Point(
                        osgb36_to_web_mercator(
                            [Number($this.attr('data-easting')), Number($this.attr('data-northing'))]
                        )
                    ),
                    name : $this.attr('data-name'),
                }
            );
            if ($this.attr('data-request-stop')) {
                feature.setStyle(request_stop_style);
            }
            source.addFeature(feature);
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

const popup_element = require_element('popup');
const popup = new Overlay(
    {
        element : popup_element,
        positioning : 'center-center',
        stopEvent : false,
    }
);
map.addOverlay(popup);

function show_popup(feature : FeatureLike) {
    const geometry = feature.getGeometry();
    if (!(geometry instanceof Point)) {
        throw new Error('A popup can only be shown on a point.');
    }
    popup.setPosition(geometry.getCoordinates());
    popup_element.style.display = 'block';
    popup_element.innerText = feature.get('name');
}

function dismiss_popup() {
    popup_element.style.display = 'none';
}

map.on(
    'click'
    , function (event) {
        dismiss_popup();
        map.forEachFeatureAtPixel(
            event.pixel
            , function (feature) {
                if (feature.getGeometry() instanceof Point) {
                    show_popup(feature);
                    return true;
                }
                return undefined;
            }
        );
    }
);

map.on(
    'pointermove'
    , function (event) {
        const pixel = map.getEventPixel(event.originalEvent);
        const features = map.getFeaturesAtPixel(pixel);
        map_element.style.cursor = features.find(
            feature => feature.getGeometry() instanceof Point
        ) ? 'pointer' : '';
    }
);

map.on('movestart', dismiss_popup);

map.getView().fit(source.getExtent(), {padding : [15, 15, 15, 15]});