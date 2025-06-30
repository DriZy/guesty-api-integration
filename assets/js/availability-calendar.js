jQuery(document).ready(function($) {
    $('.guesty-availability-calendar').each(function() {
        const $calendar = $(this);
        const listingId = $calendar.data('listing-id');

        $calendar.on('click', '.prev-month, .next-month', function() {
            const direction = $(this).hasClass('prev-month') ? -1 : 1;
            // Implement AJAX loading for different months
            // You would need to create an AJAX endpoint to fetch month data
        });

        $calendar.on('click', '.day.available', function() {
            // Handle date selection for booking
            alert('Date selected - implement booking logic');
        });
    });
});
