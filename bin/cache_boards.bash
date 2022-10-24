#!/bin/bash
for station in \
LBG CST BFR CTK WAE CHX VXH WAT VIC PAD MYB EUS STP KGX OLD MOG LST FST \
BDS TCR ZFD \
CLJ SRA BHM MAN LDS GLC
do
    "$(dirname "$0")"/show_departure_board.php "today 12:00" $station >/dev/null &
    "$(dirname "$0")"/show_departure_board.php --arrivals "today 12:00" $station >/dev/null &
    "$(dirname "$0")"/show_departure_board.php "tomorrow 12:00" $station >/dev/null &
    "$(dirname "$0")"/show_departure_board.php --arrivals "tomorrow 12:00" $station >/dev/null &
done