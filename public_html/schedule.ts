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

$('#go_to_top').on('click', () => window.scrollTo(0, 0));

const $query_form = $('#query_form');
const $query_form_toggle = $('#query_form_toggle');

$query_form.css('display', 'none');

function handle_query_form_toggle() {
    let toggle = $query_form_toggle[0];
    if (toggle instanceof HTMLInputElement) {
        $query_form.css('display', toggle.checked ? 'block' : 'none');
    }
}
handle_query_form_toggle();
$query_form_toggle.on(
    'change'
    , handle_query_form_toggle
);
