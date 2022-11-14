import $ from 'jquery';
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