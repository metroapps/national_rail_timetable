'use strict';

const query = new URLSearchParams(window.location.search.substring(1));
const mode = query.get('mode');
const reference_timestamp = (
    query.get('connecting_time') 
        ? new Date(query.get('connecting_time'))
        : new Date()
).getTime() / 1000;
let element = null;
$('tr[data-timestamp]').each(
    function () {
        element = this;
        const timestamp = Number($(this).attr('data-timestamp'));
        if (mode === 'arrivals' ? timestamp <= reference_timestamp : timestamp >= reference_timestamp) {
            return false;
        }
    }
);
element?.scrollIntoView(true);

$('#go_to_top').click(() => window.scrollTo(0, 0));