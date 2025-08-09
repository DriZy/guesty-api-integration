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
                className: entry.status,
                extendedProps: {
                    status: entry.status
                }
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
            dateClick: function (info) {
                /*
                * Handles date clicks, displaying a modal with the selected date.
                */
                const eventsOnDate = calendarInstance.getEvents().filter(event => {
                    const eventDate = event.start.toISOString().split('T')[0];
                    return eventDate === info.dateStr;
                });

                if (eventsOnDate.length > 0 && eventsOnDate[0].extendedProps.status === 'available') {
                    const modalContent = handleDateSelection(info.dateStr);
                    jQuery(modalContent).modal();
                }
            },
            dayCellDidMount: function(info) {
                const eventsOnDate = calendarInstance.getEvents().filter(event => {
                    const eventDate = event.start.toISOString().split('T')[0];
                    return eventDate === info.dateStr;
                });

                if (eventsOnDate.length > 0 && eventsOnDate[0].extendedProps.status === 'available') {
                    info.el.classList.add('clickable');
                }else{
                    info.el.classList.add('disabled');
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

        if (calendarInstance) {
            calendarInstance.on('eventClick', function (info) {
                const event = info.event;
                if (event.extendedProps.status === 'available') {
                    const modalContent = handleDateSelection(event.start.toISOString().split('T')[0]);
                    jQuery(modalContent).modal();
                }
            });
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

    let selectedStartDate = null;
    let selectedEndDate = null;

    function handleDateSelection(date) {
        /*
        * Handles date selection events, allowing the user to select a check-in date and pick a check-out date within the modal.
        */
        selectedStartDate = date;
        const postTitle = guestyApi.post_title || 'the property';
        // Return modal content for confirmation
        return `
            <div class="modal-content">
                <h2>Reserve ${postTitle}</h2>
                <p></p>
                <div class="section-input">
                    <p id="check-in-date">Check-in: <b>${selectedStartDate}</b></p>
                    <p id="check-out-date">Check-out: <b></b></p>
                    <label for="checkout-date" class="property-label">Please select your check-out date:</label>
                    <input type="date" placeholder="Select a check-out date" id="checkout-date" min="${selectedStartDate}" />
                    <p id="error-message" style="color: red; display: none;">Please select a valid check-out date.</p>
                    
                </div>
                <div class="section-input">
                    <label for="guest-count" class="property-label">Number of Guests:</label>
                    <input type="number" id="guest-count" min="1" value="1" />
                </div>
                <div class="section-input">
                    <label for="contact-info" class="property-label">Your Email:</label>
                    <input type="email" id="contact-info" placeholder="Enter your email" />
                 </div>
                <div class="section-textarea">
                    <label for="reservation-notes" class="property-label">Reservation Notes:</label>
                    <textarea id="reservation-notes" placeholder="Add any special requests or notes"></textarea>
                </div>
                <div class="section checkbox">
                    <input type="checkbox" id="privacy-policy" />
                    <label for="privacy-policy" class="property-label">I agree to the Privacy Policy</label>
                </div>
                <div class="section checkbox">
                    <input type="checkbox" id="terms-conditions" />
                    <label for="terms-conditions" class="property-label">I agree to the Terms and Conditions</label>
                </div>
                <div class="section checkbox">
                    <input type="checkbox" id="marketing-consent" />
                    <label for="marketing-consent" class="property-label">I agree to receive marketing communications</label>
                </div>
                <p></p>
                <button id="reserve" disabled>Book Property</button>
            </div>
        `;
    }

    document.addEventListener('click', function (event) {
        if (event.target && event.target.id === 'checkout-date') {
            const checkoutInput = event.target;
            checkoutInput.addEventListener('change', function () {
                document.querySelector('#check-out-date b').innerHTML = checkoutInput.value;
                const selectedEndDate = checkoutInput.value;
                if (new Date(selectedEndDate) > new Date(selectedStartDate)) {
                    document.getElementById('reserve').disabled = false;
                    document.getElementById('error-message').style.display = 'none';
                } else {
                    document.getElementById('reserve').disabled = true;
                    document.getElementById('error-message').style.display = 'block';
                }
            });
        }

        if (event.target && event.target.id === 'reserve') {
            const guestCount = document.getElementById('guest-count').value;
            const reservationNotes = document.getElementById('reservation-notes').value;
            const contactInfo = document.getElementById('contact-info').value;
            const privacyPolicy = document.getElementById('privacy-policy').checked;
            const termsConditions = document.getElementById('terms-conditions').checked;
            const marketingConsent = document.getElementById('marketing-consent').checked;

            const reservationData = {
                checkIn: new Date(selectedStartDate).toISOString().split('T')[0],
                checkOut: new Date(document.querySelector('#check-out-date b').innerHTML).toISOString().split('T')[0],
                guests: parseInt(guestCount, 10),
                notes: reservationNotes,
                contactInfo: contactInfo,
                privacyPolicy: privacyPolicy,
                termsConditions: termsConditions,
                marketingConsent: marketingConsent
            };

            // Make AJAX call to create reservation
            jQuery.post(guestyApi.ajax_url, {
                action: 'guesty_create_reservation',
                reservation_data: reservationData,
                post_id: guestyApi.post_id,
                _ajax_nonce: guestyApi.nonce
            }, function (response) {
                if (response.success) {
                    // Send email notification
                    jQuery.post(guestyApi.ajax_url, {
                        action: 'guesty_send_reservation_email',
                        email_data: {
                            postTitle: guestyApi.post_title,
                            ...reservationData,
                            subject: 'Reservation Confirmation',
                        },
                        _ajax_nonce: guestyApi.nonce
                    }, function (emailResponse) {
                        if (emailResponse.success) {

                            alert('Reservation successful and email sent!');
                        } else {
                            alert('Reservation successful but email could not be sent.');
                        }
                        // Remove the modal from the DOM
                        jQuery.modal.close();
                    });
                } else {
                    console.error('Error creating reservation:', response);
                }
            });
        }

        if (event.target && event.target.id === 'book') {
            selectedStartDate = null;
            selectedEndDate = null;
            document.querySelector('.jquery-modal').remove();
        }
    });

    document.addEventListener('input', function (event) {
        const checkoutDate = document.getElementById('checkout-date').value;
        const contactInfo = document.getElementById('contact-info').value;
        const privacyPolicy = document.getElementById('privacy-policy').checked;
        const termsConditions = document.getElementById('terms-conditions').checked;

        const reserveButton = document.getElementById('reserve');

        if (checkoutDate && contactInfo && privacyPolicy && termsConditions) {
            reserveButton.disabled = false;
        } else {
            reserveButton.disabled = true;
        }
    });

    function makeAjaxCall(startDate, endDate) {
        /*
        * Makes an AJAX call to handle the selected date range.
        */
        jQuery.post(guestyApi.ajax_url, {
            action: 'handle_date_range_selection',
            start_date: startDate,
            end_date: endDate,
            _ajax_nonce: guestyApi.nonce
        }, function (response) {
            if (response.success) {
                console.log('Date range selection processed successfully:', response);
            } else {
                console.error('Error processing date range selection:', response);
            }
        });
    }

    const calendarElement = document.querySelector('#property-calendar');
    if (calendarElement) {
        /*
        * Adds an event listener for date selection on the calendar element.
        */
        calendarElement.addEventListener('click', (event) => {
            console.log('oooooooooooooooooooooooooooooooooooooooooooooooo')
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
