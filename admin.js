jQuery(document).ready(function($) {
    $('.widefat').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        order: [[0, 'desc']],
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
    // Update Ledger 
    $(document).on('click', '.update-ledgers', function() {
        let form = $(this).closest('form');
        
        let orderId = form.find('.order_id').val();
        let customerEmail = form.find('.customer_email').val();
        let receivedAmount = form.find('.received_amount').val();
        let orderTotal = form.find('#order_total_' + orderId).val();
        if (!receivedAmount || isNaN(receivedAmount) || parseFloat(receivedAmount) <= 0) {
            alert('Please enter a valid positive number for the received amount.');
            return false;
        }
        $('#loader').show();
        $.ajax({
            url: ajaxJsData.ajax_url,
            type: 'POST',
            data: {
                action: 'update_ledger',
                order_id: orderId,
                user_email: customerEmail,
                received_amount: receivedAmount,
                order_total: orderTotal
            },
            success: function(response) {
                // $('#loader').hide(); // Hide loader
                if (response.success) {
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                    $('#success-message').text(response.data.message + ' Please wait while grid refreshed').show();
                    setTimeout(function() {
                        location.reload();
                    }, 2500);
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('AJAX Error: ' + error);
            }
        });
    });

    // Display Ledger History in popup modal
        $('.ledger-history').click(function() {
            let orderId = $(this).closest('form').data('order-id');
            let orderCurrencyCode = $(this).closest('form').find('.order_currency_code').val();
            $.ajax({
                url: ajaxJsData.ajax_url,
                type: 'POST',
                data: {
                    action: 'fetch_ledger_history',
                    order_id: orderId,
                    order_currency_code: orderCurrencyCode
                },
                success: function(response) {
                    if (response.success) {
                        $('#ledger-history-content').html(response.data);
                        $('#ledgerHistoryModal').fadeIn();
                    } else {
                        $('#ledger-history-content').html(response.data);
                        $('#ledgerHistoryModal').fadeIn();
                    }
                },
                error: function() {
                    $('#ledger-history-content').html('<p>Error loading ledger history.</p>');
                }
            });
        });
        $('.close-button').click(function() {
            $('#ledgerHistoryModal').fadeOut();
        });
        $(window).click(function(event) {
            if ($(event.target).is('#ledgerHistoryModal')) {
                $('#ledgerHistoryModal').fadeOut();
            }
        });
    
    // Download Ledger History CSV script
        $('#download-csv').click(function() {
            var csvContent = "data:text/csv;charset=utf-8,";
    
            $('#ledger-history-content').find('table').each(function() {
                var table = $(this);
                var rows = table.find('tr').map(function() {
                    return $(this).find('td, th').map(function() {
                        return $(this).text();
                    }).get().join(',');
                }).get().join('\n');
    
                csvContent += rows + '\n\n';
            });
    
            var encodedUri = encodeURI(csvContent);
            var link = document.createElement('a');
            link.setAttribute('href', encodedUri);
            link.setAttribute('download', 'ledger_history.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
});
