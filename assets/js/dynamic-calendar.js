// Documenting the code with block comments

document.addEventListener('DOMContentLoaded', () => {
    /*
    * Event listener for DOMContentLoaded to ensure the script runs only after the DOM is fully loaded.
    */
    console.log('Dynamic calendar script loaded.');

    let propertyCalendarContainer = document.querySelector('.property-calendar');
    if (!propertyCalendarContainer) {
        /*
        * If the property calendar container is not found, create one dynamically and append it to the body.
        */
        console.warn('Property calendar container not found. Creating one dynamically.');
        propertyCalendarContainer = document.createElement('div');
        propertyCalendarContainer.className = 'property-calendar';
        document.body.appendChild(propertyCalendarContainer);
    }

    const defaultStartDate = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
    const defaultEndDate = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toISOString().split('T')[0];

    const currentDate = new Date();
    const firstDayOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1).toISOString().split('T')[0];
    const lastDayOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).toISOString().split('T')[0];

    console.log(`First day of the month: ${firstDayOfMonth}, Last day of the month: ${lastDayOfMonth}`);

    let calendarInstance; // Variable to store the calendar instance

    function fetchCalendarData(startDate = defaultStartDate, endDate = defaultEndDate) {
        /*
        * Fetches calendar data via AJAX using the provided start and end dates.
        */
        const postId = guestyApi.post_id || null;
        if (!postId) {
            console.error('Post ID is not available. Ensure this is a single property page.');
            return;
        }

        jQuery.post(guestyApi.ajax_url, {
            action: 'guesty_fetch_calendar_data',
            start_date: startDate,
            end_date: endDate,
            post_id: postId,
            _ajax_nonce: guestyApi.nonce
        }, function (response) {
            /*
            * Handles the AJAX response. If successful, renders the calendar with the fetched data.
            */
            console.log("response aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa", response);
            if (response.success) {
                renderCalendar(response.data.data);
            } else {
                console.error('Error fetching calendar data:', response);
            }
        });
    }

    function renderCalendar(calendarData) {
        /*
        * Renders the calendar using the FullCalendar library and the provided calendar data.
        */
        const events = calendarData.map(entry => {
            return {
                title: entry.status,
                start: entry.date,
                className: entry.status
            };
        });

        if (document.querySelector('#property-calendar')) {
            console.warn('Calendar already exists. Skipping reinitialization.');
            return;
        }

        const calendarEl = document.createElement('div');
        calendarEl.id = 'property-calendar';
        propertyCalendarContainer.appendChild(calendarEl);

        calendarInstance = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: events,
            eventClick: function (info) {
                /*
                * Handles event clicks, logging details and optionally navigating to a URL.
                */
                info.jsEvent.preventDefault();
                console.log('Event clicked:', info.event.title);
                console.log('Event start:', info.event.start);

                if (info.event.url) {
                    window.open(info.event.url, '_blank');
                }
            }
        });

        console.log("initialise calendar:", calendarInstance);

        calendarInstance.render();

        const toolbarChunk = document.querySelector('#property-calendar .fc-toolbar');
        if (toolbarChunk) {
            /*
            * Adds event listeners to navigation buttons in the calendar toolbar.
            */
            console.log('Toolbar chunk found. Adding event listeners to navigation buttons.');

            const nextButton = toolbarChunk.querySelector('button.fc-next-button');
            const prevButton = toolbarChunk.querySelector('button.fc-prev-button');
            const todayButton = toolbarChunk.querySelector('button.fc-today-button');

            if (nextButton) {
                nextButton.addEventListener('click', () => {
                    const currentView = calendarInstance.view;
                    const startDate = currentView.activeStart.toISOString().split('T')[0];
                    const endDate = currentView.activeEnd.toISOString().split('T')[0];
                    console.log(`Next button clicked. Fetching data for Start Date: ${startDate}, End Date: ${endDate}`);
                    updateCalendarDates('next');
                    fetchCalendarData(startDate, endDate);
                });
            } else {
                console.warn('Next navigation button not found.');
            }

            if (prevButton) {
                prevButton.addEventListener('click', () => {
                    const currentView = calendarInstance.view;
                    const startDate = currentView.activeStart.toISOString().split('T')[0];
                    const endDate = currentView.activeEnd.toISOString().split('T')[0];
                    console.log(`Previous button clicked. Fetching data for Start Date: ${startDate}, End Date: ${endDate}`);
                    updateCalendarDates('prev');
                    fetchCalendarData(startDate, endDate);
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
        /*
        * Updates the calendar view based on the navigation direction (next, prev, or today).
        */
        if (!calendarInstance) {
            console.error('Calendar instance is not initialized.');
            return;
        }

        if (direction === 'next') {
        } else if (direction === 'prev') {
        } else if (direction === 'today') {
            calendarInstance.today();
        }

        setTimeout(() => {
            const currentView = calendarInstance.view;
            const startDate = currentView.activeStart.toISOString().split('T')[0];
            const endDate = currentView.activeEnd.toISOString().split('T')[0];

            console.log(`Navigation clicked: ${direction}, Start Date: ${startDate}, End Date: ${endDate}`);
            fetchCalendarData(startDate, endDate);
        }, 0);
    }

    function handleDateSelection(date) {
        /*
        * Handles date selection events, logging the selected date.
        */
        console.log(`Date selected: ${date}`);
    }

    const calendarElement = document.querySelector('#property-calendar');
    if (calendarElement) {
        /*
        * Adds an event listener for date selection on the calendar element.
        */
        calendarElement.addEventListener('click', (event) => {
            if (event.target.classList.contains('fc-day')) {
                const selectedDate = event.target.getAttribute('data-date');
                handleDateSelection(selectedDate);
            }
        });
    } else {
        console.warn('Calendar element not found.');
    }

    fetchCalendarData(); // Initial fetch and render of the calendar data
});
