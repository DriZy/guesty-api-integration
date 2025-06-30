document.addEventListener('DOMContentLoaded', () => {
    console.log('Dynamic calendar script loaded.');

    let propertyCalendarContainer = document.querySelector('.property-calendar');
    if (!propertyCalendarContainer) {
        console.warn('Property calendar container not found. Creating one dynamically.');
        propertyCalendarContainer = document.createElement('div');
        propertyCalendarContainer.className = 'property-calendar';
        document.body.appendChild(propertyCalendarContainer); // Append to the body or a specific parent element
    }

    const defaultStartDate = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
    const defaultEndDate = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toISOString().split('T')[0];

    let calendarInstance; // Store the calendar instance

    function fetchCalendarData(startDate = defaultStartDate, endDate = defaultEndDate) {
        const postId = guestyApi.post_id || null; // Get the post ID from localized script data
        if (!postId) {
            console.error('Post ID is not available. Ensure this is a single property page.');
            return;
        }

        jQuery.post(guestyApi.ajax_url, {
            action: 'guesty_fetch_calendar_data',
            start_date: startDate,
            end_date: endDate,
            post_id: postId, // Send the post ID via AJAX
            _ajax_nonce: guestyApi.nonce
        }, function (response) {
                console.log("response aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa", response);
                if (response.success) {
                    renderCalendar(response.data.data);
                } else {
                    console.error('Error fetching calendar data:', response);
                }
            }
        );
    }

    function renderCalendar(calendarData) {
        const events = calendarData.map(entry => {
            return {
                title: entry.status,
                start: entry.date,
                className: entry.status // Add class for styling
            };
        });

        const calendarEl = document.createElement('div');
        calendarEl.id = 'property-calendar';
        propertyCalendarContainer.appendChild(calendarEl);

        calendarInstance = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: events,
            eventClick: function (info) {
                    // Prevent default browser action
                    info.jsEvent.preventDefault();

                    // Log event details
                    console.log('Event clicked:', info.event.title);
                    console.log('Event start:', info.event.start);

                    // Example: Navigate to a URL if the event has one
                    if (info.event.url) {
                        window.open(info.event.url, '_blank');
                    }
                }

        });

        console.log("initialise calendar:", calendarInstance);

        calendarInstance.render();

        // Ensure calendar exists in the DOM before adding event listeners
        const toolbarChunk = document.querySelector('#property-calendar .fc-toolbar'); // Updated selector
        if (toolbarChunk) {
            console.log('Toolbar chunk found. Adding event listeners to navigation buttons.');

            const nextButton = toolbarChunk.querySelector('button.fc-next-button');
            const prevButton = toolbarChunk.querySelector('button.fc-prev-button');
            const todayButton = toolbarChunk.querySelector('button.fc-today-button');

            if (nextButton) {
                nextButton.addEventListener('click', () => {
                    updateCalendarDates('next');
                });
            } else {
                console.warn('Next navigation button not found.');
            }

            if (prevButton) {
                prevButton.addEventListener('click', () => {
                    updateCalendarDates('prev');
                });
            } else {
                console.warn('Previous navigation button not found.');
            }

            if (todayButton) {
                todayButton.addEventListener('click', () => {
                    console.log('Today button clicked. Resetting to current month.');
                    updateCalendarDates('today');
                });
            } else {
                console.warn('Today button not found.');
            }
        } else {
            console.warn('Toolbar chunk not found. Navigation buttons will not be functional.');
        }
    }

    function updateCalendarDates(direction) {
        if (!calendarInstance) {
            console.error('Calendar instance is not initialized.');
            return;
        }

        if (direction === 'next') {
            calendarInstance.next();
        } else if (direction === 'prev') {
            calendarInstance.prev();
        } else if (direction === 'today') {
            calendarInstance.today();
        }

        const currentView = calendarInstance.view;
        const startDate = currentView.activeStart.toISOString().split('T')[0];
        const endDate = currentView.activeEnd.toISOString().split('T')[0];

        console.log(`Navigation clicked: ${direction}, Start Date: ${startDate}, End Date: ${endDate}`);
        fetchCalendarData(startDate, endDate);
    }

    function handleDateSelection(date) {
        console.log(`Date selected: ${date}`);
        // Additional logic for handling date selection can be added here
    }

    // Example: Add event listener for date selection (if applicable)
    const calendarElement = document.querySelector('#property-calendar');
    if (calendarElement) {
        calendarElement.addEventListener('click', (event) => {
            if (event.target.classList.contains('fc-day')) {
                const selectedDate = event.target.getAttribute('data-date');
                handleDateSelection(selectedDate);
            }
        });
    } else {
        console.warn('Calendar element not found.');
    }

    // Fetch and render the calendar data
    fetchCalendarData();
});
