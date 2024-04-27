#!/bin/bash
for station in \
LBG WAT VIC PAD EUS STP KGX LST ZFD \
CLJ RDG SRA BHM MAN LDS GLC
do
    php "$(dirname "$0")"/show_departure_board.php "today 12:00" $station >/dev/null &
    php "$(dirname "$0")"/show_departure_board.php --arrivals "today 12:00" $station >/dev/null &
    php "$(dirname "$0")"/show_departure_board.php "tomorrow 12:00" $station >/dev/null &
    php "$(dirname "$0")"/show_departure_board.php --arrivals "tomorrow 12:00" $station >/dev/null &
done