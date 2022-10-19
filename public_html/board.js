'use strict';

if (document.location.hash) {
    let id = null;
    $('tr[id]').each(
        function () {
            id = this.id;
            if (this.id > document.location.hash.substring(1)) {
                return false;
            }
        }
    );
    document.location.hash = '#' + id;
}