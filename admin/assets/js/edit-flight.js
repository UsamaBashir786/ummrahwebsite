/**
 * Flight form handling script for both Add and Edit Flight pages
 * Provides consistent functionality across both pages
 */
document.addEventListener('DOMContentLoaded', function() {
  // ====== UTILITY FUNCTIONS ======
  
  // Safely get element - avoids errors for missing elements
  function getElement(selector) {
    return document.querySelector(selector);
  }
  
  // Check if element exists
  function elementExists(selector) {
    return document.querySelector(selector) !== null;
  }

  // Set default date to today for new flights
  const dateField = getElement('input[name="departure_date"]');
  if (dateField && !dateField.value) {
    const today = new Date();
    const formattedDate = today.toISOString().split('T')[0];
    dateField.value = formattedDate;
  }

  // ====== SECTION VISIBILITY FUNCTIONS ======
  
  // Function to toggle stops section visibility
  window.toggleStopsSection = function(show) {
    const stopsContainer = getElement('#stops-container');
    if (!stopsContainer) return;
    
    if (show) {
      stopsContainer.classList.remove('hidden');
      document.querySelectorAll('input[name="stop_city[]"]').forEach(input => {
        input.setAttribute('required', 'required');
      });
      document.querySelectorAll('input[name="stop_duration[]"]').forEach(input => {
        input.setAttribute('required', 'required');
      });
    } else {
      stopsContainer.classList.add('hidden');
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
    const returnContainer = getElement('#return-container');
    if (!returnContainer) return;
    
    if (show) {
      returnContainer.classList.remove('hidden');
      document.querySelectorAll('.return-required').forEach(field => {
        field.setAttribute('required', 'required');
      });
    } else {
      returnContainer.classList.add('hidden');
      document.querySelectorAll('.return-required').forEach(field => {
        field.removeAttribute('required');
      });

      // Also uncheck and disable return stops
      const directReturnFlight = getElement('input[name="has_return_stops"][value="0"]');
      if (directReturnFlight) {
        directReturnFlight.checked = true;
        toggleReturnStopsSection(false);
      }
    }
  };

  // Function to toggle return stops section visibility
  window.toggleReturnStopsSection = function(show) {
    const returnStopsContainer = getElement('#return-stops-container');
    if (!returnStopsContainer) return;
    
    if (show) {
      returnStopsContainer.classList.remove('hidden');
      document.querySelectorAll('input[name="return_stop_city[]"]').forEach(input => {
        input.setAttribute('required', 'required');
      });
      document.querySelectorAll('input[name="return_stop_duration[]"]').forEach(input => {
        input.setAttribute('required', 'required');
      });
    } else {
      returnStopsContainer.classList.add('hidden');
      document.querySelectorAll('input[name="return_stop_city[]"]').forEach(input => {
        input.removeAttribute('required');
      });
      document.querySelectorAll('input[name="return_stop_duration[]"]').forEach(input => {
        input.removeAttribute('required');
      });
    }
  };

  // ====== RADIO BUTTON EVENT LISTENERS ======
  
  // Bind event listeners to radio buttons
  const directFlightRadio = getElement('#directFlight');
  const hasStopsRadio = getElement('#hasStops');
  if (directFlightRadio && hasStopsRadio) {
    directFlightRadio.addEventListener('change', () => toggleStopsSection(false));
    hasStopsRadio.addEventListener('change', () => toggleStopsSection(true));
    // Initial state
    toggleStopsSection(hasStopsRadio.checked);
  }
  
  const oneWayFlightRadio = getElement('#oneWayFlight');
  const roundTripRadio = getElement('#roundTrip');
  if (oneWayFlightRadio && roundTripRadio) {
    oneWayFlightRadio.addEventListener('change', () => toggleReturnSection(false));
    roundTripRadio.addEventListener('change', () => toggleReturnSection(true));
    // Initial state
    toggleReturnSection(roundTripRadio.checked);
  }
  
  const directReturnFlightRadio = getElement('#directReturnFlight');
  const hasReturnStopsRadio = getElement('#hasReturnStops');
  if (directReturnFlightRadio && hasReturnStopsRadio) {
    directReturnFlightRadio.addEventListener('change', () => toggleReturnStopsSection(false));
    hasReturnStopsRadio.addEventListener('change', () => toggleReturnStopsSection(true));
    // Initial state
    toggleReturnStopsSection(hasReturnStopsRadio.checked);
  }

  // ====== ADD STOP BUTTONS ======
  
  // Dynamically add more stops
  const addStopBtn = getElement('#add-stop');
  if (addStopBtn) {
    addStopBtn.addEventListener('click', function() {
      // Create a new row container div
      const newStopRow = document.createElement('div');
      newStopRow.className = 'stop-row grid grid-cols-1 md:grid-cols-2 gap-6 mb-4';
      
      // Create city input field
      const cityDiv = document.createElement('div');
      cityDiv.innerHTML = `
        <label class="block text-sm font-medium text-gray-700 mb-1">Stop City</label>
        <input type="text" name="stop_city[]" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" maxlength="12" placeholder="e.g., Dubai">
      `;
      
      // Create duration input field
      const durationDiv = document.createElement('div');
      durationDiv.innerHTML = `
        <label class="block text-sm font-medium text-gray-700 mb-1">Stop Duration (hours)</label>
        <div class="flex">
          <input type="text" name="stop_duration[]" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="e.g., 4">
          <button type="button" class="ml-2 py-2 px-3 bg-red-600 text-white rounded-lg hover:bg-red-700">Remove</button>
        </div>
      `;
      
      // Add remove button functionality
      const removeButton = durationDiv.querySelector('button');
      removeButton.addEventListener('click', function() {
        newStopRow.remove();
      });
      
      // Append both divs to the row
      newStopRow.appendChild(cityDiv);
      newStopRow.appendChild(durationDiv);
      
      // Add required attribute if necessary
      if (getElement('input[name="has_stops"]:checked')?.value === "1") {
        newStopRow.querySelectorAll('input').forEach(input => {
          input.setAttribute('required', 'required');
        });
      }
      
      // Insert before the button's parent container
      const buttonContainer = addStopBtn.closest('.text-right');
      if (buttonContainer) {
        buttonContainer.before(newStopRow);
      }
      
      // Initialize validation for new fields
      const newCityInput = newStopRow.querySelector('input[name="stop_city[]"]');
      const newDurationInput = newStopRow.querySelector('input[name="stop_duration[]"]');
      if (window.initStopCityValidation && newCityInput) {
        window.initStopCityValidation(newCityInput);
      }
      if (window.initStopDurationValidation && newDurationInput) {
        window.initStopDurationValidation(newDurationInput);
      }
    });
  }

  // Add event listener for the "Add Another Return Stop" button
  const addReturnStopBtn = getElement('#add-return-stop');
  if (addReturnStopBtn) {
    addReturnStopBtn.addEventListener('click', function() {
      // Create a new row container div
      const newReturnStopRow = document.createElement('div');
      newReturnStopRow.className = 'return-stop-row grid grid-cols-1 md:grid-cols-2 gap-6 mb-4';
      
      // Create city input field
      const cityDiv = document.createElement('div');
      cityDiv.innerHTML = `
        <label class="block text-sm font-medium text-gray-700 mb-1">Return Stop City</label>
        <input type="text" name="return_stop_city[]" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="e.g., Dubai" maxlength="50">
      `;
      
      // Create duration input field
      const durationDiv = document.createElement('div');
      durationDiv.innerHTML = `
        <label class="block text-sm font-medium text-gray-700 mb-1">Return Stop Duration (hours)</label>
        <div class="flex">
          <input type="text" name="return_stop_duration[]" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="e.g., 2">
          <button type="button" class="ml-2 py-2 px-3 bg-red-600 text-white rounded-lg hover:bg-red-700">Remove</button>
        </div>
      `;
      
      // Add remove button functionality
      const removeButton = durationDiv.querySelector('button');
      removeButton.addEventListener('click', function() {
        newReturnStopRow.remove();
      });
      
      // Append both divs to the row
      newReturnStopRow.appendChild(cityDiv);
      newReturnStopRow.appendChild(durationDiv);
      
      // Add required attribute if necessary
      const hasReturnStops = getElement('input[name="has_return_stops"]:checked')?.value === "1";
      const hasReturn = getElement('input[name="has_return"]:checked')?.value === "1";
      if (hasReturnStops && hasReturn) {
        newReturnStopRow.querySelectorAll('input').forEach(input => {
          input.setAttribute('required', 'required');
        });
      }
      
      // Insert before the button's parent container
      const buttonContainer = addReturnStopBtn.closest('.text-right');
      if (buttonContainer) {
        buttonContainer.before(newReturnStopRow);
      }
      
      // Initialize validation for new fields
      const newCityInput = newReturnStopRow.querySelector('input[name="return_stop_city[]"]');
      const newDurationInput = newReturnStopRow.querySelector('input[name="return_stop_duration[]"]');
      if (window.initReturnStopCityValidation && newCityInput) {
        window.initReturnStopCityValidation(newCityInput);
      }
      if (window.initReturnStopDurationValidation && newDurationInput) {
        window.initReturnStopDurationValidation(newDurationInput);
      }
    });
  }

  // ====== FLIGHT NUMBER FORMATTING ======
  
  // Flight Number formatting
  const flightNumInput = getElement('#flight_number');
  if (flightNumInput) {
    flightNumInput.addEventListener('input', function() {
      let value = this.value.toUpperCase(); // Convert to uppercase
      let letters = '';
      let numbers = '';
      
      // Separate letters and numbers
      for (let i = 0; i < value.length; i++) {
        const char = value[i];
        if (/[A-Z]/.test(char)) {
          letters += char;
        } else if (/[0-9]/.test(char)) {
          numbers += char;
        }
        // Ignore any other characters
      }
      
      // Format the input
      if (letters && numbers) {
        this.value = letters + '-' + numbers;
      } else if (letters) {
        this.value = letters;
      } else if (numbers) {
        this.value = numbers;
      } else {
        this.value = '';
      }
    });
  }

  // Return flight number formatting
  const returnFlightNumInput = getElement('#return_flight_number');
  if (returnFlightNumInput) {
    returnFlightNumInput.addEventListener('input', function() {
      let value = this.value.toUpperCase(); // Convert to uppercase
      let letters = '';
      let numbers = '';
      
      // Separate letters and numbers
      for (let i = 0; i < value.length; i++) {
        const char = value[i];
        if (/[A-Z]/.test(char)) {
          letters += char;
        } else if (/[0-9]/.test(char)) {
          numbers += char;
        }
        // Ignore any other characters
      }
      
      // Format the input
      if (letters && numbers) {
        this.value = letters + '-' + numbers;
      } else if (letters) {
        this.value = letters;
      } else if (numbers) {
        this.value = numbers;
      } else {
        this.value = '';
      }
    });
  }

  // ====== TIME INPUT FORMATTING ======
  
  // Time format validation
  const timeInputs = document.querySelectorAll('input[name="departure_time"], input[name="return_time"]');
  timeInputs.forEach(input => {
    input.addEventListener('input', function(e) {
      let value = e.target.value.replace(/[^0-9:]/g, '');
      
      // Add colon after hours if needed
      if (value.length === 2 && !value.includes(':')) {
        value += ':';
      }
      
      // Restrict to proper format
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
      
      // Update input value
      e.target.value = value;
    });
    
    input.addEventListener('blur', function() {
      const timePattern = /^([01]?[0-9]|2[0-3]):([0-5][0-9])$/;
      const value = this.value.trim();
      
      // Skip if empty
      if (!value) return;
      
      // Check format
      if (!timePattern.test(value)) {
        // Try to fix the time format
        const parts = value.split(':');
        if (parts.length === 2) {
          let hours = parseInt(parts[0]);
          let minutes = parseInt(parts[1]);
          
          if (!isNaN(hours) && !isNaN(minutes)) {
            hours = Math.max(0, Math.min(23, hours));
            minutes = Math.max(0, Math.min(59, minutes));
            
            this.value = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
          }
        }
      } else {
        // Format properly if valid
        const parts = value.split(':');
        const hours = parseInt(parts[0]);
        const minutes = parseInt(parts[1]);
        
        this.value = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
      }
    });
  });

  // ====== VALIDATION FUNCTIONS ======
  
  // Initialize stop city validation
  window.initStopCityValidation = function(input) {
    if (!input) return;
    
    input.addEventListener('input', function() {
      // Remove numbers and invalid characters, allow letters, spaces, and hyphens
      this.value = this.value.replace(/[^A-Za-z\s-]/g, '');
      
      // Capitalize first letter of each word
      this.value = this.value.replace(/\b\w/g, char => char.toUpperCase());
    });
  };
  
  // Initialize stop duration validation
  window.initStopDurationValidation = function(input) {
    if (!input) return;
    
    input.addEventListener('input', function() {
      // Remove any non-numeric characters except decimal point
      let value = this.value.replace(/[^0-9.]/g, '');
      
      // Ensure only one decimal point
      const decimalCount = (value.match(/\./g) || []).length;
      if (decimalCount > 1) {
        const firstDecimalPos = value.indexOf('.');
        value = value.substring(0, firstDecimalPos + 1) + 
               value.substring(firstDecimalPos + 1).replace(/\./g, '');
      }
      
      // Limit to one decimal place
      if (value.includes('.')) {
        const parts = value.split('.');
        if (parts[1] && parts[1].length > 1) {
          parts[1] = parts[1].substring(0, 1);
          value = parts.join('.');
        }
      }
      
      // Ensure reasonable hour values (0-72 hours)
      const numValue = parseFloat(value);
      if (!isNaN(numValue) && numValue > 72) {
        value = '72';
      }
      
      // Update the input value
      this.value = value;
    });
  };
  
  // Initialize return stop city validation
  window.initReturnStopCityValidation = function(input) {
    window.initStopCityValidation(input); // Reuse the same validation
  };
  
  // Initialize return stop duration validation
  window.initReturnStopDurationValidation = function(input) {
    window.initStopDurationValidation(input); // Reuse the same validation
  };
  
  // Apply initial validation to all existing fields
  document.querySelectorAll('input[name="stop_city[]"]').forEach(input => {
    window.initStopCityValidation(input);
  });
  
  document.querySelectorAll('input[name="stop_duration[]"]').forEach(input => {
    window.initStopDurationValidation(input);
  });
  
  document.querySelectorAll('input[name="return_stop_city[]"]').forEach(input => {
    window.initReturnStopCityValidation(input);
  });
  
  document.querySelectorAll('input[name="return_stop_duration[]"]').forEach(input => {
    window.initReturnStopDurationValidation(input);
  });

  // ====== FORM VALIDATION ======
  
  // Form submission validation
  const form = getElement('#flightForm');
  if (form) {
    form.addEventListener('submit', function(e) {
      let isValid = true;
      
      // Validate flight number
      const flightNumber = getElement('#flight_number')?.value;
      if (flightNumber) {
        const pattern = /^[A-Z]{2,3}-\d{1,4}$/;
        if (!pattern.test(flightNumber)) {
          e.preventDefault();
          alert('Please enter a valid flight number (e.g., PK-309)');
          getElement('#flight_number')?.focus();
          return;
        }
      }
      
      // Validate return flight number if round trip is selected
      if (getElement('input[name="has_return"]:checked')?.value === "1") {
        const returnFlightNumber = getElement('#return_flight_number')?.value;
        if (returnFlightNumber) {
          const pattern = /^[A-Z]{2,3}-\d{1,4}$/;
          if (!pattern.test(returnFlightNumber)) {
            e.preventDefault();
            alert('Please enter a valid return flight number (e.g., PK-310)');
            getElement('#return_flight_number')?.focus();
            return;
          }
        }
        
        // Check return date is after departure date
        const departureDate = new Date(getElement('#departure_date')?.value);
        const returnDate = new Date(getElement('#return_date')?.value);
        if (!isNaN(departureDate.getTime()) && !isNaN(returnDate.getTime()) && returnDate <= departureDate) {
          e.preventDefault();
          alert('Return date must be after departure date');
          getElement('#return_date')?.focus();
          return;
        }
      }
      
      // If we get here, the form can be submitted
      return true;
    });
  }
});