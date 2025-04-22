document.addEventListener('DOMContentLoaded', function() {
  // Set default date to today
  const dateField = document.querySelector('input[name="departure_date"]');
  if (dateField) {
    const today = new Date();
    const formattedDate = today.toISOString().split('T')[0];
    dateField.value = formattedDate;
  }

  // Function to toggle stops section visibility
  window.toggleStopsSection = function(show) {
    const stopsContainer = document.getElementById('stops-container');
    if (show) {
      stopsContainer.classList.remove('d-none');
      document.querySelectorAll('input[name="stop_city[]"]').forEach(input => {
        input.setAttribute('required', 'required');
      });
      document.querySelectorAll('input[name="stop_duration[]"]').forEach(input => {
        input.setAttribute('required', 'required');
      });
    } else {
      stopsContainer.classList.add('d-none');
      document.querySelectorAll('input[name="stop_city[]"]').forEach(input => {
        input.removeAttribute('required');
      });
      document.querySelectorAll('input[name="stop_duration[]"]').forEach(input => {
        input.removeAttribute('required');
      });
    }
  };

  // Function to toggle return flight section visibility
  window.toggleReturnSection = function(show) {
    const returnContainer = document.getElementById('return-container');
    const returnRequiredFields = document.querySelectorAll('.return-required');

    if (show) {
      returnContainer.classList.remove('d-none');
      returnRequiredFields.forEach(field => {
        field.setAttribute('required', 'required');
      });
    } else {
      returnContainer.classList.add('d-none');
      returnRequiredFields.forEach(field => {
        field.removeAttribute('required');
      });

      // Also uncheck and disable return stops
      document.querySelector('input[name="has_return_stops"][value="0"]').checked = true;
      toggleReturnStopsSection(false);
    }
  };

  // Function to toggle return stops section visibility
  window.toggleReturnStopsSection = function(show) {
    const returnStopsContainer = document.getElementById('return-stops-container');

    if (show) {
      returnStopsContainer.classList.remove('d-none');
      document.querySelectorAll('input[name="return_stop_city[]"]').forEach(input => {
        input.setAttribute('required', 'required');
      });
      document.querySelectorAll('input[name="return_stop_duration[]"]').forEach(input => {
        input.setAttribute('required', 'required');
      });
    } else {
      returnStopsContainer.classList.add('d-none');
      document.querySelectorAll('input[name="return_stop_city[]"]').forEach(input => {
        input.removeAttribute('required');
      });
      document.querySelectorAll('input[name="return_stop_duration[]"]').forEach(input => {
        input.removeAttribute('required');
      });
    }
  };

  // Flight Number formatting
  const flightInput = document.getElementById('flight_number');
  flightInput.addEventListener('input', function() {
    let raw = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
    let formatted = '';

    // Extract letters first
    const letters = raw.match(/^[A-Z]{0,3}/)?.[0] || '';
    const numbers = raw.slice(letters.length).replace(/[^0-9-]/g, ''); // Only digits and dash after letters

    // Auto-insert dash if letters are 2 or 3
    if (letters.length >= 2) {
      const dashIndex = letters.length;
      formatted = letters + '-' + numbers.replace(/-/g, '');
    } else {
      formatted = letters;
    }

    // Apply formatted value
    this.value = formatted;
  });

  // Return flight number formatting
  const returnFlightInput = document.getElementById('return_flight_number');
  if (returnFlightInput) {
    returnFlightInput.addEventListener('input', function() {
      let raw = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
      let formatted = '';

      // Extract letters first
      const letters = raw.match(/^[A-Z]{0,3}/)?.[0] || '';
      const numbers = raw.slice(letters.length).replace(/[^0-9-]/g, ''); // Only digits and dash after letters

      // Auto-insert dash if letters are 2 or 3
      if (letters.length >= 2) {
        formatted = letters + '-' + numbers.replace(/-/g, '');
      } else {
        formatted = letters;
      }

      // Apply formatted value
      this.value = formatted;
    });
  }

  // Stop Duration formatting
  document.addEventListener('input', function(e) {
    if (e.target.classList.contains('stop-duration-input')) {
      let value = e.target.value;
      if (!/^[0-5]$/.test(value)) {
        e.target.value = value.replace(/[^0-5]/g, '');
      }
      if (parseInt(value) > 5) {
        e.target.value = "5";
      }
    }

    // Allow only letters in Stop City (max 12 characters)
    if (e.target.classList.contains('stop-city')) {
      e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, '').slice(0, 12);
    }
  });

  // Dynamically add more stops
  const addStopBtn = document.getElementById('add-stop');
  addStopBtn.addEventListener('click', function() {
    const stopRow = document.querySelector('.stop-row').cloneNode(true);
    stopRow.querySelectorAll('input').forEach(input => {
      input.value = '';
      if (document.querySelector('input[name="has_stops"]:checked').value === "1") {
        input.setAttribute('required', 'required');
      }
    });
    addStopBtn.closest('.text-end').before(stopRow);
  });

  // Add event listener for the "Add Another Return Stop" button
  const addReturnStopBtn = document.getElementById('add-return-stop');
  if (addReturnStopBtn) {
    addReturnStopBtn.addEventListener('click', function() {
      const returnStopRow = document.querySelector('.return-stop-row').cloneNode(true);
      const inputs = returnStopRow.querySelectorAll('input');
      inputs.forEach(input => {
        input.value = '';
        if (document.querySelector('input[name="has_return_stops"]:checked').value === "1" &&
          document.querySelector('input[name="has_return"]:checked').value === "1") {
          input.setAttribute('required', 'required');
        }
      });
      addReturnStopBtn.closest('.text-end').before(returnStopRow);
    });
  }

  // Return stop city and duration validation
  document.addEventListener('input', function(e) {
    // Validate Return Stop City (letters and spaces only, max length 12)
    if (e.target.classList.contains('return-stop-city')) {
      e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, '').slice(0, 12);
    }

    // Validate Return Stop Duration (only numbers 1-5)
    if (e.target.classList.contains('return-stop-duration')) {
      let value = e.target.value;
      if (!/^[0-5]$/.test(value)) {
        e.target.value = value.replace(/[^0-5]/g, '');
      }
      if (parseInt(value) > 5) {
        e.target.value = "5";
      }
    }
  });

  // Time format validation
  const timeInputs = document.querySelectorAll('input[name="departure_time"], input[name="return_time"]');
  timeInputs.forEach(input => {
    input.addEventListener('input', function(e) {
      let value = e.target.value;

      // Only allow digits and colon
      value = value.replace(/[^0-9:]/g, '');

      // Auto-add colon after 2 digits if not already there
      if (value.length === 2 && !value.includes(':')) {
        value += ':';
      }

      // Limit to 5 chars (HH:MM)
      if (value.length > 5) {
        value = value.substring(0, 5);
      }

      // Validate hours (00-23)
      if (value.includes(':') && value.split(':')[0].length === 2) {
        const hours = parseInt(value.split(':')[0]);
        if (hours > 23) {
          value = '23' + value.substring(2);
        }
      }

      // Update the input value
      e.target.value = value;
    });
  });

  // Auto-open date pickers
  const dateInputs = document.querySelectorAll('input[type="date"]');
  dateInputs.forEach(input => {
    input.addEventListener('focus', function() {
      this.showPicker && this.showPicker();
    });

    input.addEventListener('click', function() {
      this.showPicker && this.showPicker();
    });
  });

  // Handle return airline dropdown
  const returnAirlineSelect = document.querySelector('select[name="return_airline"]');
  if (returnAirlineSelect) {
    returnAirlineSelect.addEventListener('change', function() {
      if (this.value === 'same') {
        // Get the outbound airline value
        const outboundAirline = document.querySelector('select[name="airline_name"]').value;
        // Just show a message without actually changing the value
        if (outboundAirline) {
          Swal.fire({
            icon: 'info',
            title: 'Return Airline',
            text: 'Return airline will be set to: ' + outboundAirline,
            confirmButtonText: 'OK'
          });
        } else {
          Swal.fire({
            icon: 'warning',
            title: 'Outbound Airline Not Selected',
            text: 'Please select an outbound airline first',
            confirmButtonText: 'OK'
          });
          // Reset to empty option
          this.value = '';
        }
      }
    });
  }

  // Flight duration validation
  document.getElementById('flight_duration').addEventListener('input', function() {
    let inputValue = parseFloat(this.value);
    if (inputValue > 8) {
      this.value = 8;
      Swal.fire({
        icon: 'info',
        title: 'Flight Duration',
        text: 'Maximum flight duration is 8 hours',
        confirmButtonText: 'OK'
      });
    }
  });

  // Distance validation
  document.getElementById('distance').addEventListener('input', function() {
    let inputValue = parseInt(this.value);
    if (inputValue > 20000) {
      this.value = 20000;
      Swal.fire({
        icon: 'info',
        title: 'Flight Distance',
        text: 'Maximum flight distance is 20,000 km',
        confirmButtonText: 'OK'
      });
    }
  });

  // Form submission
  document.getElementById('flightForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent actual form submission

    // Simple client-side validation
    let valid = true;
    let firstInvalidField = null;

    // Check required fields
    this.querySelectorAll('[required]').forEach(field => {
      if (!field.value) {
        valid = false;
        field.classList.add('is-invalid');
        const errorElement = document.getElementById(`${field.id}-error`);
        if (errorElement) {
          errorElement.style.display = 'block';
          errorElement.textContent = 'This field is required';
        }
        if (!firstInvalidField) firstInvalidField = field;
      } else {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        const errorElement = document.getElementById(`${field.id}-error`);
        if (errorElement) {
          errorElement.style.display = 'none';
        }
      }
    });

    // Check return date is after departure date when round trip is selected
    if (document.querySelector('input[name="has_return"]:checked').value === "1") {
      const departureDate = new Date(document.getElementById('departure_date').value);
      const returnDate = new Date(document.getElementById('return_date').value);

      if (returnDate <= departureDate) {
        valid = false;
        const returnDateField = document.getElementById('return_date');
        const returnDateError = document.getElementById('return_date-error');

        returnDateField.classList.add('is-invalid');
        returnDateError.style.display = 'block';
        returnDateError.textContent = 'Return date must be after departure date';

        if (!firstInvalidField) firstInvalidField = returnDateField;
      }
    }

    if (!valid) {
      // Scroll to first invalid field
      if (firstInvalidField) {
        firstInvalidField.scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        });
      }

      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        text: 'Please fix all errors before submitting',
        confirmButtonText: 'OK'
      });
    } else {
      // Show success message (in a real app, this would submit to server)
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Flight added successfully',
        confirmButtonText: 'OK'
      }).then((result) => {
        if (result.isConfirmed) {
          this.reset();
          // In a real application, you might redirect: window.location.href = 'flights.php';
        }
      });
    }
  });
});