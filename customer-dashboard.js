jQuery(document).ready(function($) {
    $('#ledger-history').DataTable({
        paging: true,
        searching: false,
        ordering: true,
        info: true,
        language: {
            paginate: {
                previous: '&laquo;',
                next: '&raquo;'
            },
            search: 'Search:',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'No entries available',
            infoFiltered: '(filtered from _MAX_ total entries)'
        }
    });
});
