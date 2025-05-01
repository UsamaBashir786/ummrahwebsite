// Flight number validation script
document.addEventListener('DOMContentLoaded', function() {
  const flightNumberInput = document.getElementById('flight_number');
  
  flightNumberInput.addEventListener('input', function(e) {
    let value = e.target.value.toUpperCase(); // Convert to uppercase
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
      e.target.value = letters + '-' + numbers;
    } else if (letters) {
      e.target.value = letters;
    } else if (numbers) {
      e.target.value = numbers;
    } else {
      e.target.value = '';
    }
  });
  
  // Validate the flight number format on form submission
  const form = flightNumberInput.closest('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      const flightNumber = flightNumberInput.value;
      const pattern = /^[A-Z]{2,3}-\d{1,4}$/;
      
      if (!pattern.test(flightNumber)) {
        e.preventDefault();
        alert('Please enter a valid flight number (e.g., PK-309)');
        flightNumberInput.focus();
      }
    });
  }
});









document.addEventListener('DOMContentLoaded', function() {
  // Get all stop city input fields
  const stopCityInputs = document.querySelectorAll('input[name="stop_city[]"]');

  stopCityInputs.forEach(input => {
    // Validation on input
    input.addEventListener('input', function() {
      // Remove numbers and invalid characters, allow letters, spaces, and hyphens
      this.value = this.value.replace(/[^A-Za-z\s-]/g, '');
      
      // Capitalize first letter of each word
      this.value = this.value.replace(/\b\w/g, char => char.toUpperCase());
    });

    // Validation on blur
    input.addEventListener('blur', function() {
      const value = this.value.trim();

      // Skip validation if empty (assuming optional unless stops are required)
      if (!value) {
        this.classList.remove('is-invalid');
        this.classList.remove('is-valid');
        return;
      }

      // Check if input contains at least one letter and only allowed characters
      const isValid = /^[A-Za-z\s-]+$/.test(value) && /[A-Za-z]/.test(value);

      if (!isValid) {
        this.classList.add('is-invalid');
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Please enter a valid city or country name (letters, spaces, or hyphens only)';
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
  });

  // Function to dynamically add new stop city fields
  window.addNewStopCity = function() {
    const container = document.querySelector('.stop-cities-container');
    if (container) {
      const newRow = document.createElement('div');
      newRow.className = 'col-md-6 mb-3 mb-md-0';
      newRow.innerHTML = `
        <label class="form-label">Stop City</label>
        <input type="text" name="stop_city[]" class="form-control" maxlength="50" placeholder="e.g., Dubai">
        <div class="invalid-feedback"></div>
        <button type="button" class="btn btn-sm btn-danger mt-1" onclick="this.parentElement.remove()">Remove</button>
      `;

      container.appendChild(newRow);

      // Initialize validation for the new input
      const newInput = newRow.querySelector('input[name="stop_city[]"]');
      if (newInput) {
        newInput.addEventListener('input', function() {
          this.value = this.value.replace(/[^A-Za-z\s-]/g, '');
          this.value = this.value.replace(/\b\w/g, char => char.toUpperCase());
        });

        newInput.addEventListener('blur', function() {
          const value = this.value.trim();

          if (!value) {
            this.classList.remove('is-invalid');
            this.classList.remove('is-valid');
            return;
          }

          const isValid = /^[A-Za-z\s-]+$/.test(value) && /[A-Za-z]/.test(value);

          if (!isValid) {
            this.classList.add('is-invalid');
            let feedback = this.nextElementSibling;
            if (!feedback || !feedback.classList.contains('invalid-feedback')) {
              feedback = document.createElement('div');
              feedback.classList.add('invalid-feedback');
              this.after(feedback);
            }
            feedback.textContent = 'Please enter a valid city or country name (letters, spaces, or hyphens only)';
          } else {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            const feedback = this.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
              feedback.remove();
            }
          }
        });
      }
    }
  };

  // Form validation on submit
  const form = stopCityInputs.length > 0 ? stopCityInputs[0].closest('form') : null;
  if (form) {
    form.addEventListener('submit', function(e) {
      let isValid = true;

      // Check if stops are required (assuming similar radio button setup as return stops)
      const hasStops = document.querySelector('input[name="has_stops"]:checked');
      if (hasStops && hasStops.value === '1') {
        const cityInputs = document.querySelectorAll('input[name="stop_city[]"]');

        if (cityInputs.length === 0) {
          e.preventDefault();
          alert('Please add at least one stop city.');
          return;
        }

        cityInputs.forEach(input => {
          const value = input.value.trim();
          const isValidInput = value && /^[A-Za-z\s-]+$/.test(value) && /[A-Za-z]/.test(value);

          if (!isValidInput) {
            input.classList.add('is-invalid');
            let feedback = input.nextElementSibling;
            if (!feedback || !feedback.classList.contains('invalid-feedback')) {
              feedback = document.createElement('div');
              feedback.classList.add('invalid-feedback');
              input.after(feedback);
            }
            feedback.textContent = value ? 'Please enter a valid city or country name (letters, spaces, or hyphens only)' : 'Stop city is required';
            isValid = false;
          }
        });
      }

      if (!isValid) {
        e.preventDefault();
        alert('Please correct the highlighted errors before submitting.');
      }
    });
  }
});




// Stop Duration validation script
document.addEventListener('DOMContentLoaded', function() {
  // Find all stop duration inputs (since they use array notation)
  const stopDurationInputs = document.querySelectorAll('input[name="stop_duration[]"]');
  
  stopDurationInputs.forEach(input => {
    // Input validation - only allow numbers and decimal point
    input.addEventListener('input', function(e) {
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
        if (parts[1].length > 1) {
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
    
    // Validation on blur
    input.addEventListener('blur', function() {
      const value = this.value.trim();
      
      // Skip validation if empty
      if (!value) return;
      
      const numValue = parseFloat(value);
      
      // Check if input is a valid number
      if (isNaN(numValue)) {
        this.value = '';
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Please enter a valid number of hours';
        return;
      }
      
      // Format to one decimal place if needed
      if (value.includes('.')) {
        this.value = numValue.toFixed(1);
      } else {
        this.value = numValue.toString();
      }
      
      // Validation for reasonable duration
      if (numValue < 0.5) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Duration must be at least 0.5 hours';
      } else if (numValue > 72) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Duration cannot exceed 72 hours';
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
  });
  
  // Function to initialize validation for dynamically added inputs
  window.initStopDurationValidation = function(input) {
    if (!input) return;
    
    // Input validation - only allow numbers and decimal point
    input.addEventListener('input', function(e) {
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
        if (parts[1].length > 1) {
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
    
    // Same blur validation as above
    input.addEventListener('blur', function() {
      // (validation code same as above)
      const value = this.value.trim();
      
      // Skip validation if empty
      if (!value) return;
      
      const numValue = parseFloat(value);
      
      // Check if input is a valid number
      if (isNaN(numValue)) {
        this.value = '';
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Please enter a valid number of hours';
        return;
      }
      
      // Format to one decimal place if needed
      if (value.includes('.')) {
        this.value = numValue.toFixed(1);
      } else {
        this.value = numValue.toString();
      }
      
      // Validation for reasonable duration
      if (numValue < 0.5) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Duration must be at least 0.5 hours';
      } else if (numValue > 72) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Duration cannot exceed 72 hours';
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
  };
  
  // Add function to dynamically add new stop duration fields if needed
  // This should be coordinated with adding new stop city fields
  window.addNewStopDuration = function() {
    const container = document.querySelector('.stop-durations-container');
    if (container) {
      const newRow = document.createElement('div');
      newRow.className = 'col-md-6';
      newRow.innerHTML = `
        <label class="form-label">Stop Duration (hours)</label>
        <input type="text" name="stop_duration[]" class="form-control" placeholder="e.g., 4">
        <button type="button" class="btn btn-sm btn-danger mt-1" onclick="this.parentElement.remove()">Remove</button>
      `;
      
      container.appendChild(newRow);
      
      // Initialize validation for the new input
      const newInput = newRow.querySelector('input[name="stop_duration[]"]');
      if (newInput) {
        window.initStopDurationValidation(newInput);
      }
    }
  };
});


// Flight Schedule and Duration Validation
document.addEventListener('DOMContentLoaded', function() {
  // Get references to the form elements
  const departureDate = document.getElementById('departure_date');
  const departureTime = document.getElementById('departure_time');
  const flightDuration = document.getElementById('flight_duration');
  
  // ===== DEPARTURE DATE VALIDATION =====
  if (departureDate) {
    // Set min date to today
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const todayFormatted = `${yyyy}-${mm}-${dd}`;
    
    departureDate.setAttribute('min', todayFormatted);
    
    // Set max date to one year from today
    const nextYear = new Date();
    nextYear.setFullYear(nextYear.getFullYear() + 1);
    const nextYearYYYY = nextYear.getFullYear();
    const nextYearMM = String(nextYear.getMonth() + 1).padStart(2, '0');
    const nextYearDD = String(nextYear.getDate()).padStart(2, '0');
    const nextYearFormatted = `${nextYearYYYY}-${nextYearMM}-${nextYearDD}`;
    
    departureDate.setAttribute('max', nextYearFormatted);
    
    // Validate on change
    departureDate.addEventListener('change', function() {
      const selectedDate = new Date(this.value);
      const now = new Date();
      
      // Clear custom validity
      this.setCustomValidity('');
      
      // Check if date is valid
      if (isNaN(selectedDate.getTime())) {
        this.setCustomValidity('Please enter a valid date');
        this.reportValidity();
        return;
      }
      
      // Add visual feedback
      if (selectedDate < now) {
        this.classList.add('is-invalid');
        this.setCustomValidity('Departure date cannot be in the past');
        this.reportValidity();
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      }
    });
  }
  
  // ===== DEPARTURE TIME VALIDATION =====
  if (departureTime) {
    // Time format validation using mask
    departureTime.addEventListener('input', function(e) {
      let value = e.target.value.replace(/[^0-9]/g, '');
      
      // Add colon after hours if needed
      if (value.length > 2) {
        value = value.substring(0, 2) + ':' + value.substring(2);
      }
      
      // Restrict to proper format
      if (value.length > 5) {
        value = value.substring(0, 5);
      }
      
      // Update input value
      e.target.value = value;
    });
    
    // Validate time on blur
    departureTime.addEventListener('blur', function() {
      const timePattern = /^([01]?[0-9]|2[0-3]):([0-5][0-9])$/;
      const value = this.value.trim();
      
      // Skip if empty
      if (!value) return;
      
      // Check format
      if (!timePattern.test(value)) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        
        // Parse what we have
        const parts = value.split(':');
        let hours = parseInt(parts[0]);
        
        // Specific error messages based on the issue
        if (parts.length !== 2) {
          feedback.textContent = 'Please use the format HH:MM';
        } else if (isNaN(hours) || hours < 0 || hours > 23) {
          feedback.textContent = 'Hours must be between 00-23';
        } else {
          feedback.textContent = 'Minutes must be between 00-59';
        }
        
        // Try to fix the time if possible
        if (parts.length === 2) {
          hours = Math.max(0, Math.min(23, isNaN(hours) ? 0 : hours));
          let minutes = parseInt(parts[1]);
          minutes = Math.max(0, Math.min(59, isNaN(minutes) ? 0 : minutes));
          
          this.value = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
          this.classList.remove('is-invalid');
          if (feedback) feedback.remove();
        }
      } else {
        // Format properly if valid
        const parts = value.split(':');
        const hours = parseInt(parts[0]);
        const minutes = parseInt(parts[1]);
        
        this.value = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
  }
  
  // ===== FLIGHT DURATION VALIDATION =====
  if (flightDuration) {
    // Validate on input
    flightDuration.addEventListener('input', function() {
      // Ensure value stays within reasonable limits
      const value = parseFloat(this.value);
      if (!isNaN(value)) {
        if (value > 24) {
          this.value = 24;
        } else if (value < 0.5 && value !== 0) {
          this.value = 0.5;
        }
      }
    });
    
    // Validate on blur
    flightDuration.addEventListener('blur', function() {
      const value = parseFloat(this.value);
      
      // Skip if empty
      if (this.value.trim() === '') return;
      
      // Check if value is a valid number
      if (isNaN(value)) {
        this.classList.add('is-invalid');
        this.value = '';
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Please enter a valid duration';
        return;
      }
      
      // Validate reasonable flight duration
      if (value < 0.5) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Flight duration must be at least 0.5 hours';
      } else if (value > 24) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Flight duration cannot exceed 24 hours';
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Format to one decimal place
        this.value = value.toFixed(1);
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
  }
  
  // Form validation on submit
  const form = departureDate.closest('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      let isValid = true;
      
      // Validate departure date
      if (departureDate.value === '') {
        departureDate.classList.add('is-invalid');
        isValid = false;
      }
      
      // Validate departure time
      if (departureTime.value === '') {
        departureTime.classList.add('is-invalid');
        isValid = false;
      } else {
        const timePattern = /^([01]?[0-9]|2[0-3]):([0-5][0-9])$/;
        if (!timePattern.test(departureTime.value)) {
          departureTime.classList.add('is-invalid');
          isValid = false;
        }
      }
      
      // Validate flight duration
      if (flightDuration.value === '') {
        flightDuration.classList.add('is-invalid');
        isValid = false;
      } else {
        const value = parseFloat(flightDuration.value);
        if (isNaN(value) || value < 0.5 || value > 24) {
          flightDuration.classList.add('is-invalid');
          isValid = false;
        }
      }
      
      // Prevent form submission if invalid
      if (!isValid) {
        e.preventDefault();
        alert('Please correct the highlighted errors before submitting.');
      }
    });
  }
});



// Distance Field Validation
document.addEventListener('DOMContentLoaded', function() {
  // Get reference to the distance input field
  const distanceInput = document.getElementById('distance');
  
  if (distanceInput) {
    // Set min attribute to prevent negative distance
    distanceInput.setAttribute('min', '1');
    
    // Set max attribute for a reasonable maximum flight distance
    // The longest commercial flight is around 18,000 km, so 20,000 is a reasonable maximum
    distanceInput.setAttribute('max', '20000');
    
    // Input validation
    distanceInput.addEventListener('input', function() {
      // Remove any non-numeric characters
      this.value = this.value.replace(/[^\d]/g, '');
      
      // Ensure value stays within reasonable limits
      const value = parseInt(this.value);
      if (!isNaN(value)) {
        if (value > 20000) {
          this.value = 20000;
        } else if (value < 1 && value !== 0) {
          this.value = 1;
        }
      }
    });
    
    // Blur validation (when user leaves the field)
    distanceInput.addEventListener('blur', function() {
      const value = parseInt(this.value);
      
      // Skip if empty
      if (this.value.trim() === '') return;
      
      // Check if value is a valid number
      if (isNaN(value)) {
        this.classList.add('is-invalid');
        this.value = '';
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Please enter a valid distance';
        return;
      }
      
      // Validate reasonable distance values
      if (value < 1) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Distance must be at least 1 km';
      } else if (value > 20000) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Distance cannot exceed 20,000 km';
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
    
    // Form validation - include in existing form submit event
    const form = distanceInput.closest('form');
    if (form) {
      form.addEventListener('submit', function(e) {
        // This check assumes you might have other validation scripts
        // already attached to the form's submit event
        if (!e.defaultPrevented) {
          const value = parseInt(distanceInput.value);
          
          // Validate distance
          if (distanceInput.value === '') {
            distanceInput.classList.add('is-invalid');
            e.preventDefault();
          } else if (isNaN(value) || value < 1 || value > 20000) {
            distanceInput.classList.add('is-invalid');
            e.preventDefault();
          }
        }
      });
    }
    
    // Optional: Add a lookup feature to estimate distances between common airports
    // This is just a small sample of airport distances
    const commonDistances = {
      'JFK-LHR': 5541, // New York to London
      'LAX-NRT': 8773, // Los Angeles to Tokyo
      'DXB-SYD': 12051, // Dubai to Sydney
      'SIN-LHR': 10885, // Singapore to London
      'JFK-LAX': 3983, // New York to Los Angeles
      'LHR-DXB': 5502, // London to Dubai
      'ISB-KHI': 1180, // Islamabad to Karachi
      'ISB-LHR': 6065, // Islamabad to London
      'ISB-DXB': 2098, // Islamabad to Dubai
      'KHI-DXB': 1193  // Karachi to Dubai
    };
    
    // Add a helper button next to the distance field
    const helperButton = document.createElement('button');
    helperButton.type = 'button';
    helperButton.className = 'btn btn-sm btn-outline-secondary mt-2';
    helperButton.textContent = 'Need help estimating distance?';
    distanceInput.parentNode.appendChild(helperButton);
    
    // Show lookup dialog when helper button is clicked
    helperButton.addEventListener('click', function() {
      let airportPairs = '';
      for (const [pair, distance] of Object.entries(commonDistances)) {
        airportPairs += `${pair}: ${distance} km\n`;
      }
      
      const userInput = prompt(
        `Enter airport codes (e.g., 'JFK-LHR') to get an estimate, or choose from:\n${airportPairs}`,
        ''
      );
      
      if (userInput && commonDistances[userInput.toUpperCase()]) {
        distanceInput.value = commonDistances[userInput.toUpperCase()];
        distanceInput.classList.add('is-valid');
      }
    });
  }
});


// Return Flight Number validation script
document.addEventListener('DOMContentLoaded', function() {
  const returnFlightNumberInput = document.getElementById('return_flight_number');
  
  if (returnFlightNumberInput) {
    returnFlightNumberInput.addEventListener('input', function(e) {
      let value = e.target.value.toUpperCase(); // Convert to uppercase
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
        e.target.value = letters + '-' + numbers;
      } else if (letters) {
        e.target.value = letters;
      } else if (numbers) {
        e.target.value = numbers;
      } else {
        e.target.value = '';
      }
    });
    
    // Validate the return flight number format on blur
    returnFlightNumberInput.addEventListener('blur', function() {
      // Skip validation if field is empty (since it's optional)
      if (!this.value.trim()) {
        this.classList.remove('is-invalid');
        this.classList.remove('is-valid');
        return;
      }
      
      const flightNumber = this.value;
      const pattern = /^[A-Z]{2,3}-\d{1,4}$/;
      
      if (!pattern.test(flightNumber)) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Please enter a valid flight number (e.g., PK-310)';
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
    
    // Copy from outbound flight number if needed
    const outboundFlightInput = document.getElementById('flight_number');
    if (outboundFlightInput) {
      // Add a helper button to suggest return flight number based on outbound
      const helperButton = document.createElement('button');
      helperButton.type = 'button';
      helperButton.className = 'btn btn-sm btn-outline-secondary mt-2';
      helperButton.textContent = 'Suggest return flight';
      returnFlightNumberInput.parentNode.appendChild(helperButton);
      
      helperButton.addEventListener('click', function() {
        const outboundNumber = outboundFlightInput.value;
        
        // Only proceed if outbound flight number is valid
        const pattern = /^([A-Z]{2,3})-(\d{1,4})$/;
        const match = outboundNumber.match(pattern);
        
        if (match) {
          const airline = match[1];
          let flightNum = parseInt(match[2]);
          
          // Common patterns for return flights:
          // 1. Add 1 to the flight number (most common)
          // 2. For even numbers, add 1; for odd numbers, subtract 1
          
          // Let's use pattern 1: add 1 to flight number
          flightNum++;
          
          returnFlightNumberInput.value = airline + '-' + flightNum;
          returnFlightNumberInput.classList.add('is-valid');
        } else {
          alert('Please enter a valid outbound flight number first');
        }
      });
    }
  }
});



document.addEventListener('DOMContentLoaded', function() {
  // Get references to the form elements
  const returnDate = document.getElementById('return_date');
  const returnTime = document.getElementById('return_time');
  const returnFlightDuration = document.getElementById('return_flight_duration');

  // ===== RETURN DATE VALIDATION =====
  if (returnDate) {
    // Set min date to today
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const todayFormatted = `${yyyy}-${mm}-${dd}`;
    
    returnDate.setAttribute('min', todayFormatted);
    
    // Set max date to one year from today
    const nextYear = new Date();
    nextYear.setFullYear(nextYear.getFullYear() + 1);
    const nextYearYYYY = nextYear.getFullYear();
    const nextYearMM = String(nextYear.getMonth() + 1).padStart(2, '0');
    const nextYearDD = String(nextYear.getDate()).padStart(2, '0');
    const nextYearFormatted = `${nextYearYYYY}-${nextYearMM}-${nextYearDD}`;
    
    returnDate.setAttribute('max', nextYearFormatted);
    
    // Validate on change
    returnDate.addEventListener('change', function() {
      const selectedDate = new Date(this.value);
      const now = new Date();
      
      // Clear custom validity
      this.setCustomValidity('');
      
      // Check if date is valid
      if (isNaN(selectedDate.getTime())) {
        this.setCustomValidity('Please enter a valid date');
        this.reportValidity();
        return;
      }
      
      // Add visual feedback
      if (selectedDate < now) {
        this.classList.add('is-invalid');
        this.setCustomValidity('Return date cannot be in the past');
        this.reportValidity();
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      }
    });
  }

  // ===== RETURN TIME VALIDATION =====
  if (returnTime) {
    // Time format validation using mask
    returnTime.addEventListener('input', function(e) {
      let value = e.target.value.replace(/[^0-9]/g, '');
      
      // Add colon after hours if needed
      if (value.length > 2) {
        value = value.substring(0, 2) + ':' + value.substring(2);
      }
      
      // Restrict to proper format
      if (value.length > 5) {
        value = value.substring(0, 5);
      }
      
      // Update input value
      e.target.value = value;
    });
    
    // Validate time on blur
    returnTime.addEventListener('blur', function() {
      const timePattern = /^([01]?[0-9]|2[0-3]):([0-5][0-9])$/;
      const value = this.value.trim();
      
      // Skip if empty (optional field)
      if (!value) {
        this.classList.remove('is-invalid');
        this.classList.remove('is-valid');
        return;
      }
      
      // Check format
      if (!timePattern.test(value)) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        
        // Parse what we have
        const parts = value.split(':');
        let hours = parseInt(parts[0]);
        
        // Specific error messages based on the issue
        if (parts.length !== 2) {
          feedback.textContent = 'Please use the format HH:MM';
        } else if (isNaN(hours) || hours < 0 || hours > 23) {
          feedback.textContent = 'Hours must be between 00-23';
        } else {
          feedback.textContent = 'Minutes must be between 00-59';
        }
        
        // Try to fix the time if possible
        if (parts.length === 2) {
          hours = Math.max(0, Math.min(23, isNaN(hours) ? 0 : hours));
          let minutes = parseInt(parts[1]);
          minutes = Math.max(0, Math.min(59, isNaN(minutes) ? 0 : minutes));
          
          this.value = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
          this.classList.remove('is-invalid');
          if (feedback) feedback.remove();
        }
      } else {
        // Format properly if valid
        const parts = value.split(':');
        const hours = parseInt(parts[0]);
        const minutes = parseInt(parts[1]);
        
        this.value = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
  }

  // ===== RETURN FLIGHT DURATION VALIDATION =====
  if (returnFlightDuration) {
    // Validate on input
    returnFlightDuration.addEventListener('input', function() {
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
        if (parts[1].length > 1) {
          parts[1] = parts[1].substring(0, 1);
          value = parts.join('.');
        }
      }
      
      // Ensure value stays within reasonable limits
      const numValue = parseFloat(value);
      if (!isNaN(numValue)) {
        if (numValue > 24) {
          value = '24';
        } else if (numValue < 0.5 && numValue !== 0) {
          value = '0.5';
        }
      }
      
      // Update the input value
      this.value = value;
    });
    
    // Validate on blur
    returnFlightDuration.addEventListener('blur', function() {
      const value = parseFloat(this.value);
      
      // Skip if empty (optional field)
      if (this.value.trim() === '') {
        this.classList.remove('is-invalid');
        this.classList.remove('is-valid');
        return;
      }
      
      // Check if value is a valid number
      if (isNaN(value)) {
        this.classList.add('is-invalid');
        this.value = '';
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Please enter a valid duration';
        return;
      }
      
      // Validate reasonable flight duration
      if (value < 0.5) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Flight duration must be at least 0.5 hours';
      } else if (value > 24) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Flight duration cannot exceed 24 hours';
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Format to one decimal place
        this.value = value.toFixed(1);
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
  }

  // Form validation on submit
  const form = returnDate.closest('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      let isValid = true;

      // Validate return date (optional, only validate if filled)
      if (returnDate.value) {
        const selectedDate = new Date(returnDate.value);
        const now = new Date();
        if (isNaN(selectedDate.getTime()) || selectedDate < now) {
          returnDate.classList.add('is-invalid');
          isValid = false;
        }
      }

      // Validate return time (optional, only validate if filled)
      if (returnTime.value) {
        const timePattern = /^([01]?[0-9]|2[0-3]):([0-5][0-9])$/;
        if (!timePattern.test(returnTime.value)) {
          returnTime.classList.add('is-invalid');
          isValid = false;
        }
      }

      // Validate return flight duration (optional, only validate if filled)
      if (returnFlightDuration.value) {
        const value = parseFloat(returnFlightDuration.value);
        if (isNaN(value) || value < 0.5 || value > 24) {
          returnFlightDuration.classList.add('is-invalid');
          isValid = false;
        }
      }

      // Prevent form submission if invalid
      if (!isValid) {
        e.preventDefault();
        alert('Please correct the highlighted errors before submitting.');
      }
    });
  }
});




// Flight number validation script
document.addEventListener('DOMContentLoaded', function() {
  const flightNumberInput = document.getElementById('flight_number');
  
  flightNumberInput.addEventListener('input', function(e) {
    let value = e.target.value.toUpperCase(); // Convert to uppercase
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
      e.target.value = letters + '-' + numbers;
    } else if (letters) {
      e.target.value = letters;
    } else if (numbers) {
      e.target.value = numbers;
    } else {
      e.target.value = '';
    }
  });
  
  // Validate the flight number format on form submission
  const form = flightNumberInput.closest('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      const flightNumber = flightNumberInput.value;
      const pattern = /^[A-Z]{2,3}-\d{1,4}$/;
      
      if (!pattern.test(flightNumber)) {
        e.preventDefault();
        alert('Please enter a valid flight number (e.g., PK-309)');
        flightNumberInput.focus();
      }
    });
  }
});









document.addEventListener('DOMContentLoaded', function() {
  // Get all stop city input fields
  const stopCityInputs = document.querySelectorAll('input[name="stop_city[]"]');

  stopCityInputs.forEach(input => {
    // Validation on input
    input.addEventListener('input', function() {
      // Remove numbers and invalid characters, allow letters, spaces, and hyphens
      this.value = this.value.replace(/[^A-Za-z\s-]/g, '');
      
      // Capitalize first letter of each word
      this.value = this.value.replace(/\b\w/g, char => char.toUpperCase());
    });

    // Validation on blur
    input.addEventListener('blur', function() {
      const value = this.value.trim();

      // Skip validation if empty (assuming optional unless stops are required)
      if (!value) {
        this.classList.remove('is-invalid');
        this.classList.remove('is-valid');
        return;
      }

      // Check if input contains at least one letter and only allowed characters
      const isValid = /^[A-Za-z\s-]+$/.test(value) && /[A-Za-z]/.test(value);

      if (!isValid) {
        this.classList.add('is-invalid');
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Please enter a valid city or country name (letters, spaces, or hyphens only)';
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
  });

  // Function to dynamically add new stop city fields
  window.addNewStopCity = function() {
    const container = document.querySelector('.stop-cities-container');
    if (container) {
      const newRow = document.createElement('div');
      newRow.className = 'col-md-6 mb-3 mb-md-0';
      newRow.innerHTML = `
        <label class="form-label">Stop City</label>
        <input type="text" name="stop_city[]" class="form-control" maxlength="50" placeholder="e.g., Dubai">
        <div class="invalid-feedback"></div>
        <button type="button" class="btn btn-sm btn-danger mt-1" onclick="this.parentElement.remove()">Remove</button>
      `;

      container.appendChild(newRow);

      // Initialize validation for the new input
      const newInput = newRow.querySelector('input[name="stop_city[]"]');
      if (newInput) {
        newInput.addEventListener('input', function() {
          this.value = this.value.replace(/[^A-Za-z\s-]/g, '');
          this.value = this.value.replace(/\b\w/g, char => char.toUpperCase());
        });

        newInput.addEventListener('blur', function() {
          const value = this.value.trim();

          if (!value) {
            this.classList.remove('is-invalid');
            this.classList.remove('is-valid');
            return;
          }

          const isValid = /^[A-Za-z\s-]+$/.test(value) && /[A-Za-z]/.test(value);

          if (!isValid) {
            this.classList.add('is-invalid');
            let feedback = this.nextElementSibling;
            if (!feedback || !feedback.classList.contains('invalid-feedback')) {
              feedback = document.createElement('div');
              feedback.classList.add('invalid-feedback');
              this.after(feedback);
            }
            feedback.textContent = 'Please enter a valid city or country name (letters, spaces, or hyphens only)';
          } else {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            const feedback = this.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
              feedback.remove();
            }
          }
        });
      }
    }
  };

  // Form validation on submit
  const form = stopCityInputs.length > 0 ? stopCityInputs[0].closest('form') : null;
  if (form) {
    form.addEventListener('submit', function(e) {
      let isValid = true;

      // Check if stops are required (assuming similar radio button setup as return stops)
      const hasStops = document.querySelector('input[name="has_stops"]:checked');
      if (hasStops && hasStops.value === '1') {
        const cityInputs = document.querySelectorAll('input[name="stop_city[]"]');

        if (cityInputs.length === 0) {
          e.preventDefault();
          alert('Please add at least one stop city.');
          return;
        }

        cityInputs.forEach(input => {
          const value = input.value.trim();
          const isValidInput = value && /^[A-Za-z\s-]+$/.test(value) && /[A-Za-z]/.test(value);

          if (!isValidInput) {
            input.classList.add('is-invalid');
            let feedback = input.nextElementSibling;
            if (!feedback || !feedback.classList.contains('invalid-feedback')) {
              feedback = document.createElement('div');
              feedback.classList.add('invalid-feedback');
              input.after(feedback);
            }
            feedback.textContent = value ? 'Please enter a valid city or country name (letters, spaces, or hyphens only)' : 'Stop city is required';
            isValid = false;
          }
        });
      }

      if (!isValid) {
        e.preventDefault();
        alert('Please correct the highlighted errors before submitting.');
      }
    });
  }
});




// Stop Duration validation script
document.addEventListener('DOMContentLoaded', function() {
  // Find all stop duration inputs (since they use array notation)
  const stopDurationInputs = document.querySelectorAll('input[name="stop_duration[]"]');
  
  stopDurationInputs.forEach(input => {
    // Input validation - only allow numbers and decimal point
    input.addEventListener('input', function(e) {
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
        if (parts[1].length > 1) {
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
    
    // Validation on blur
    input.addEventListener('blur', function() {
      const value = this.value.trim();
      
      // Skip validation if empty
      if (!value) return;
      
      const numValue = parseFloat(value);
      
      // Check if input is a valid number
      if (isNaN(numValue)) {
        this.value = '';
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Please enter a valid number of hours';
        return;
      }
      
      // Format to one decimal place if needed
      if (value.includes('.')) {
        this.value = numValue.toFixed(1);
      } else {
        this.value = numValue.toString();
      }
      
      // Validation for reasonable duration
      if (numValue < 0.5) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Duration must be at least 0.5 hours';
      } else if (numValue > 72) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Duration cannot exceed 72 hours';
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
  });
  
  // Function to initialize validation for dynamically added inputs
  window.initStopDurationValidation = function(input) {
    if (!input) return;
    
    // Input validation - only allow numbers and decimal point
    input.addEventListener('input', function(e) {
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
        if (parts[1].length > 1) {
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
    
    // Same blur validation as above
    input.addEventListener('blur', function() {
      // (validation code same as above)
      const value = this.value.trim();
      
      // Skip validation if empty
      if (!value) return;
      
      const numValue = parseFloat(value);
      
      // Check if input is a valid number
      if (isNaN(numValue)) {
        this.value = '';
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Please enter a valid number of hours';
        return;
      }
      
      // Format to one decimal place if needed
      if (value.includes('.')) {
        this.value = numValue.toFixed(1);
      } else {
        this.value = numValue.toString();
      }
      
      // Validation for reasonable duration
      if (numValue < 0.5) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Duration must be at least 0.5 hours';
      } else if (numValue > 72) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Duration cannot exceed 72 hours';
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
  };
  
  // Add function to dynamically add new stop duration fields if needed
  // This should be coordinated with adding new stop city fields
  window.addNewStopDuration = function() {
    const container = document.querySelector('.stop-durations-container');
    if (container) {
      const newRow = document.createElement('div');
      newRow.className = 'col-md-6';
      newRow.innerHTML = `
        <label class="form-label">Stop Duration (hours)</label>
        <input type="text" name="stop_duration[]" class="form-control" placeholder="e.g., 4">
        <button type="button" class="btn btn-sm btn-danger mt-1" onclick="this.parentElement.remove()">Remove</button>
      `;
      
      container.appendChild(newRow);
      
      // Initialize validation for the new input
      const newInput = newRow.querySelector('input[name="stop_duration[]"]');
      if (newInput) {
        window.initStopDurationValidation(newInput);
      }
    }
  };
});


// Flight Schedule and Duration Validation
document.addEventListener('DOMContentLoaded', function() {
  // Get references to the form elements
  const departureDate = document.getElementById('departure_date');
  const departureTime = document.getElementById('departure_time');
  const flightDuration = document.getElementById('flight_duration');
  
  // ===== DEPARTURE DATE VALIDATION =====
  if (departureDate) {
    // Set min date to today
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const todayFormatted = `${yyyy}-${mm}-${dd}`;
    
    departureDate.setAttribute('min', todayFormatted);
    
    // Set max date to one year from today
    const nextYear = new Date();
    nextYear.setFullYear(nextYear.getFullYear() + 1);
    const nextYearYYYY = nextYear.getFullYear();
    const nextYearMM = String(nextYear.getMonth() + 1).padStart(2, '0');
    const nextYearDD = String(nextYear.getDate()).padStart(2, '0');
    const nextYearFormatted = `${nextYearYYYY}-${nextYearMM}-${nextYearDD}`;
    
    departureDate.setAttribute('max', nextYearFormatted);
    
    // Validate on change
    departureDate.addEventListener('change', function() {
      const selectedDate = new Date(this.value);
      const now = new Date();
      
      // Clear custom validity
      this.setCustomValidity('');
      
      // Check if date is valid
      if (isNaN(selectedDate.getTime())) {
        this.setCustomValidity('Please enter a valid date');
        this.reportValidity();
        return;
      }
      
      // Add visual feedback
      if (selectedDate < now) {
        this.classList.add('is-invalid');
        this.setCustomValidity('Departure date cannot be in the past');
        this.reportValidity();
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      }
    });
  }
  
  // ===== DEPARTURE TIME VALIDATION =====
  if (departureTime) {
    // Time format validation using mask
    departureTime.addEventListener('input', function(e) {
      let value = e.target.value.replace(/[^0-9]/g, '');
      
      // Add colon after hours if needed
      if (value.length > 2) {
        value = value.substring(0, 2) + ':' + value.substring(2);
      }
      
      // Restrict to proper format
      if (value.length > 5) {
        value = value.substring(0, 5);
      }
      
      // Update input value
      e.target.value = value;
    });
    
    // Validate time on blur
    departureTime.addEventListener('blur', function() {
      const timePattern = /^([01]?[0-9]|2[0-3]):([0-5][0-9])$/;
      const value = this.value.trim();
      
      // Skip if empty
      if (!value) return;
      
      // Check format
      if (!timePattern.test(value)) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        
        // Parse what we have
        const parts = value.split(':');
        let hours = parseInt(parts[0]);
        
        // Specific error messages based on the issue
        if (parts.length !== 2) {
          feedback.textContent = 'Please use the format HH:MM';
        } else if (isNaN(hours) || hours < 0 || hours > 23) {
          feedback.textContent = 'Hours must be between 00-23';
        } else {
          feedback.textContent = 'Minutes must be between 00-59';
        }
        
        // Try to fix the time if possible
        if (parts.length === 2) {
          hours = Math.max(0, Math.min(23, isNaN(hours) ? 0 : hours));
          let minutes = parseInt(parts[1]);
          minutes = Math.max(0, Math.min(59, isNaN(minutes) ? 0 : minutes));
          
          this.value = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
          this.classList.remove('is-invalid');
          if (feedback) feedback.remove();
        }
      } else {
        // Format properly if valid
        const parts = value.split(':');
        const hours = parseInt(parts[0]);
        const minutes = parseInt(parts[1]);
        
        this.value = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
  }
  
  // ===== FLIGHT DURATION VALIDATION =====
  if (flightDuration) {
    // Validate on input
    flightDuration.addEventListener('input', function() {
      // Ensure value stays within reasonable limits
      const value = parseFloat(this.value);
      if (!isNaN(value)) {
        if (value > 24) {
          this.value = 24;
        } else if (value < 0.5 && value !== 0) {
          this.value = 0.5;
        }
      }
    });
    
    // Validate on blur
    flightDuration.addEventListener('blur', function() {
      const value = parseFloat(this.value);
      
      // Skip if empty
      if (this.value.trim() === '') return;
      
      // Check if value is a valid number
      if (isNaN(value)) {
        this.classList.add('is-invalid');
        this.value = '';
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Please enter a valid duration';
        return;
      }
      
      // Validate reasonable flight duration
      if (value < 0.5) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Flight duration must be at least 0.5 hours';
      } else if (value > 24) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Flight duration cannot exceed 24 hours';
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Format to one decimal place
        this.value = value.toFixed(1);
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
  }
  
  // Form validation on submit
  const form = departureDate.closest('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      let isValid = true;
      
      // Validate departure date
      if (departureDate.value === '') {
        departureDate.classList.add('is-invalid');
        isValid = false;
      }
      
      // Validate departure time
      if (departureTime.value === '') {
        departureTime.classList.add('is-invalid');
        isValid = false;
      } else {
        const timePattern = /^([01]?[0-9]|2[0-3]):([0-5][0-9])$/;
        if (!timePattern.test(departureTime.value)) {
          departureTime.classList.add('is-invalid');
          isValid = false;
        }
      }
      
      // Validate flight duration
      if (flightDuration.value === '') {
        flightDuration.classList.add('is-invalid');
        isValid = false;
      } else {
        const value = parseFloat(flightDuration.value);
        if (isNaN(value) || value < 0.5 || value > 24) {
          flightDuration.classList.add('is-invalid');
          isValid = false;
        }
      }
      
      // Prevent form submission if invalid
      if (!isValid) {
        e.preventDefault();
        alert('Please correct the highlighted errors before submitting.');
      }
    });
  }
});



// Distance Field Validation
document.addEventListener('DOMContentLoaded', function() {
  // Get reference to the distance input field
  const distanceInput = document.getElementById('distance');
  
  if (distanceInput) {
    // Set min attribute to prevent negative distance
    distanceInput.setAttribute('min', '1');
    
    // Set max attribute for a reasonable maximum flight distance
    // The longest commercial flight is around 18,000 km, so 20,000 is a reasonable maximum
    distanceInput.setAttribute('max', '20000');
    
    // Input validation
    distanceInput.addEventListener('input', function() {
      // Remove any non-numeric characters
      this.value = this.value.replace(/[^\d]/g, '');
      
      // Ensure value stays within reasonable limits
      const value = parseInt(this.value);
      if (!isNaN(value)) {
        if (value > 20000) {
          this.value = 20000;
        } else if (value < 1 && value !== 0) {
          this.value = 1;
        }
      }
    });
    
    // Blur validation (when user leaves the field)
    distanceInput.addEventListener('blur', function() {
      const value = parseInt(this.value);
      
      // Skip if empty
      if (this.value.trim() === '') return;
      
      // Check if value is a valid number
      if (isNaN(value)) {
        this.classList.add('is-invalid');
        this.value = '';
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Please enter a valid distance';
        return;
      }
      
      // Validate reasonable distance values
      if (value < 1) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Distance must be at least 1 km';
      } else if (value > 20000) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Distance cannot exceed 20,000 km';
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
    
    // Form validation - include in existing form submit event
    const form = distanceInput.closest('form');
    if (form) {
      form.addEventListener('submit', function(e) {
        // This check assumes you might have other validation scripts
        // already attached to the form's submit event
        if (!e.defaultPrevented) {
          const value = parseInt(distanceInput.value);
          
          // Validate distance
          if (distanceInput.value === '') {
            distanceInput.classList.add('is-invalid');
            e.preventDefault();
          } else if (isNaN(value) || value < 1 || value > 20000) {
            distanceInput.classList.add('is-invalid');
            e.preventDefault();
          }
        }
      });
    }
    
    // Optional: Add a lookup feature to estimate distances between common airports
    // This is just a small sample of airport distances
    const commonDistances = {
      'JFK-LHR': 5541, // New York to London
      'LAX-NRT': 8773, // Los Angeles to Tokyo
      'DXB-SYD': 12051, // Dubai to Sydney
      'SIN-LHR': 10885, // Singapore to London
      'JFK-LAX': 3983, // New York to Los Angeles
      'LHR-DXB': 5502, // London to Dubai
      'ISB-KHI': 1180, // Islamabad to Karachi
      'ISB-LHR': 6065, // Islamabad to London
      'ISB-DXB': 2098, // Islamabad to Dubai
      'KHI-DXB': 1193  // Karachi to Dubai
    };
    
    // Add a helper button next to the distance field
    const helperButton = document.createElement('button');
    helperButton.type = 'button';
    helperButton.className = 'btn btn-sm btn-outline-secondary mt-2';
    helperButton.textContent = 'Need help estimating distance?';
    distanceInput.parentNode.appendChild(helperButton);
    
    // Show lookup dialog when helper button is clicked
    helperButton.addEventListener('click', function() {
      let airportPairs = '';
      for (const [pair, distance] of Object.entries(commonDistances)) {
        airportPairs += `${pair}: ${distance} km\n`;
      }
      
      const userInput = prompt(
        `Enter airport codes (e.g., 'JFK-LHR') to get an estimate, or choose from:\n${airportPairs}`,
        ''
      );
      
      if (userInput && commonDistances[userInput.toUpperCase()]) {
        distanceInput.value = commonDistances[userInput.toUpperCase()];
        distanceInput.classList.add('is-valid');
      }
    });
  }
});


// Return Flight Number validation script
document.addEventListener('DOMContentLoaded', function() {
  const returnFlightNumberInput = document.getElementById('return_flight_number');
  
  if (returnFlightNumberInput) {
    returnFlightNumberInput.addEventListener('input', function(e) {
      let value = e.target.value.toUpperCase(); // Convert to uppercase
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
        e.target.value = letters + '-' + numbers;
      } else if (letters) {
        e.target.value = letters;
      } else if (numbers) {
        e.target.value = numbers;
      } else {
        e.target.value = '';
      }
    });
    
    // Validate the return flight number format on blur
    returnFlightNumberInput.addEventListener('blur', function() {
      // Skip validation if field is empty (since it's optional)
      if (!this.value.trim()) {
        this.classList.remove('is-invalid');
        this.classList.remove('is-valid');
        return;
      }
      
      const flightNumber = this.value;
      const pattern = /^[A-Z]{2,3}-\d{1,4}$/;
      
      if (!pattern.test(flightNumber)) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Please enter a valid flight number (e.g., PK-310)';
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
    
    // Copy from outbound flight number if needed
    const outboundFlightInput = document.getElementById('flight_number');
    if (outboundFlightInput) {
      // Add a helper button to suggest return flight number based on outbound
      const helperButton = document.createElement('button');
      helperButton.type = 'button';
      helperButton.className = 'btn btn-sm btn-outline-secondary mt-2';
      helperButton.textContent = 'Suggest return flight';
      returnFlightNumberInput.parentNode.appendChild(helperButton);
      
      helperButton.addEventListener('click', function() {
        const outboundNumber = outboundFlightInput.value;
        
        // Only proceed if outbound flight number is valid
        const pattern = /^([A-Z]{2,3})-(\d{1,4})$/;
        const match = outboundNumber.match(pattern);
        
        if (match) {
          const airline = match[1];
          let flightNum = parseInt(match[2]);
          
          // Common patterns for return flights:
          // 1. Add 1 to the flight number (most common)
          // 2. For even numbers, add 1; for odd numbers, subtract 1
          
          // Let's use pattern 1: add 1 to flight number
          flightNum++;
          
          returnFlightNumberInput.value = airline + '-' + flightNum;
          returnFlightNumberInput.classList.add('is-valid');
        } else {
          alert('Please enter a valid outbound flight number first');
        }
      });
    }
  }
});



document.addEventListener('DOMContentLoaded', function() {
  // Get references to the form elements
  const returnDate = document.getElementById('return_date');
  const returnTime = document.getElementById('return_time');
  const returnFlightDuration = document.getElementById('return_flight_duration');

  // ===== RETURN DATE VALIDATION =====
  if (returnDate) {
    // Set min date to today
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const todayFormatted = `${yyyy}-${mm}-${dd}`;
    
    returnDate.setAttribute('min', todayFormatted);
    
    // Set max date to one year from today
    const nextYear = new Date();
    nextYear.setFullYear(nextYear.getFullYear() + 1);
    const nextYearYYYY = nextYear.getFullYear();
    const nextYearMM = String(nextYear.getMonth() + 1).padStart(2, '0');
    const nextYearDD = String(nextYear.getDate()).padStart(2, '0');
    const nextYearFormatted = `${nextYearYYYY}-${nextYearMM}-${nextYearDD}`;
    
    returnDate.setAttribute('max', nextYearFormatted);
    
    // Validate on change
    returnDate.addEventListener('change', function() {
      const selectedDate = new Date(this.value);
      const now = new Date();
      
      // Clear custom validity
      this.setCustomValidity('');
      
      // Check if date is valid
      if (isNaN(selectedDate.getTime())) {
        this.setCustomValidity('Please enter a valid date');
        this.reportValidity();
        return;
      }
      
      // Add visual feedback
      if (selectedDate < now) {
        this.classList.add('is-invalid');
        this.setCustomValidity('Return date cannot be in the past');
        this.reportValidity();
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      }
    });
  }

  // ===== RETURN TIME VALIDATION =====
  if (returnTime) {
    // Time format validation using mask
    returnTime.addEventListener('input', function(e) {
      let value = e.target.value.replace(/[^0-9]/g, '');
      
      // Add colon after hours if needed
      if (value.length > 2) {
        value = value.substring(0, 2) + ':' + value.substring(2);
      }
      
      // Restrict to proper format
      if (value.length > 5) {
        value = value.substring(0, 5);
      }
      
      // Update input value
      e.target.value = value;
    });
    
    // Validate time on blur
    returnTime.addEventListener('blur', function() {
      const timePattern = /^([01]?[0-9]|2[0-3]):([0-5][0-9])$/;
      const value = this.value.trim();
      
      // Skip if empty (optional field)
      if (!value) {
        this.classList.remove('is-invalid');
        this.classList.remove('is-valid');
        return;
      }
      
      // Check format
      if (!timePattern.test(value)) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        
        // Parse what we have
        const parts = value.split(':');
        let hours = parseInt(parts[0]);
        
        // Specific error messages based on the issue
        if (parts.length !== 2) {
          feedback.textContent = 'Please use the format HH:MM';
        } else if (isNaN(hours) || hours < 0 || hours > 23) {
          feedback.textContent = 'Hours must be between 00-23';
        } else {
          feedback.textContent = 'Minutes must be between 00-59';
        }
        
        // Try to fix the time if possible
        if (parts.length === 2) {
          hours = Math.max(0, Math.min(23, isNaN(hours) ? 0 : hours));
          let minutes = parseInt(parts[1]);
          minutes = Math.max(0, Math.min(59, isNaN(minutes) ? 0 : minutes));
          
          this.value = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
          this.classList.remove('is-invalid');
          if (feedback) feedback.remove();
        }
      } else {
        // Format properly if valid
        const parts = value.split(':');
        const hours = parseInt(parts[0]);
        const minutes = parseInt(parts[1]);
        
        this.value = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
  }

  // ===== RETURN FLIGHT DURATION VALIDATION =====
  if (returnFlightDuration) {
    // Validate on input
    returnFlightDuration.addEventListener('input', function() {
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
        if (parts[1].length > 1) {
          parts[1] = parts[1].substring(0, 1);
          value = parts.join('.');
        }
      }
      
      // Ensure value stays within reasonable limits
      const numValue = parseFloat(value);
      if (!isNaN(numValue)) {
        if (numValue > 24) {
          value = '24';
        } else if (numValue < 0.5 && numValue !== 0) {
          value = '0.5';
        }
      }
      
      // Update the input value
      this.value = value;
    });
    
    // Validate on blur
    returnFlightDuration.addEventListener('blur', function() {
      const value = parseFloat(this.value);
      
      // Skip if empty (optional field)
      if (this.value.trim() === '') {
        this.classList.remove('is-invalid');
        this.classList.remove('is-valid');
        return;
      }
      
      // Check if value is a valid number
      if (isNaN(value)) {
        this.classList.add('is-invalid');
        this.value = '';
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Please enter a valid duration';
        return;
      }
      
      // Validate reasonable flight duration
      if (value < 0.5) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Flight duration must be at least 0.5 hours';
      } else if (value > 24) {
        this.classList.add('is-invalid');
        
        // Create or update invalid feedback
        let feedback = this.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.classList.add('invalid-feedback');
          this.after(feedback);
        }
        feedback.textContent = 'Flight duration cannot exceed 24 hours';
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        
        // Format to one decimal place
        this.value = value.toFixed(1);
        
        // Remove any feedback message
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.remove();
        }
      }
    });
  }

  // Form validation on submit
  const form = returnDate.closest('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      let isValid = true;

      // Validate return date (optional, only validate if filled)
      if (returnDate.value) {
        const selectedDate = new Date(returnDate.value);
        const now = new Date();
        if (isNaN(selectedDate.getTime()) || selectedDate < now) {
          returnDate.classList.add('is-invalid');
          isValid = false;
        }
      }

      // Validate return time (optional, only validate if filled)
      if (returnTime.value) {
        const timePattern = /^([01]?[0-9]|2[0-3]):([0-5][0-9])$/;
        if (!timePattern.test(returnTime.value)) {
          returnTime.classList.add('is-invalid');
          isValid = false;
        }
      }

      // Validate return flight duration (optional, only validate if filled)
      if (returnFlightDuration.value) {
        const value = parseFloat(returnFlightDuration.value);
        if (isNaN(value) || value < 0.5 || value > 24) {
          returnFlightDuration.classList.add('is-invalid');
          isValid = false;
        }
      }

      // Prevent form submission if invalid
      if (!isValid) {
        e.preventDefault();
        alert('Please correct the highlighted errors before submitting.');
      }
    });
  }
});




// Guard to prevent multiple executions
if (!window.returnStopValidationInitialized) {
  window.returnStopValidationInitialized = true;

  document.addEventListener('DOMContentLoaded', function() {
    // Get references to elements
    const directReturnFlight = document.getElementById('directReturnFlight');
    const hasReturnStops = document.getElementById('hasReturnStops');
    const returnStopsContainer = document.getElementById('return-stops-container');
    const addReturnStopButton = document.getElementById('add-return-stop');

    // Exit if elements are missing
    if (!directReturnFlight || !hasReturnStops || !returnStopsContainer || !addReturnStopButton) {
      console.warn('One or more return stop elements are missing:', {
        directReturnFlight, hasReturnStops, returnStopsContainer, addReturnStopButton
      });
      return;
    }

    // Toggle visibility of return stops container
    function toggleReturnStopsContainer() {
      if (hasReturnStops.checked) {
        returnStopsContainer.classList.remove('hidden'); // Tailwind class
      } else {
        returnStopsContainer.classList.add('hidden'); // Tailwind class
      }
    }

    // Initialize toggle on page load
    toggleReturnStopsContainer();

    // Add event listeners for radio buttons
    directReturnFlight.addEventListener('change', toggleReturnStopsContainer);
    hasReturnStops.addEventListener('change', toggleReturnStopsContainer);

    // Function to initialize validation for return stop city inputs
    function initReturnStopCityValidation(input) {
      input.addEventListener('input', function() {
        // Remove numbers and invalid characters, allow letters, spaces, and hyphens
        this.value = this.value.replace(/[^A-Za-z\s-]/g, '');
        
        // Capitalize first letter of each word
        this.value = this.value.replace(/\b\w/g, char => char.toUpperCase());
      });

      input.addEventListener('blur', function() {
        const value = this.value.trim();

        // Skip validation if empty
        if (!value) {
          // Tailwind border classes for validation
          this.classList.remove('border-red-500');
          this.classList.remove('border-green-500');
          const feedback = this.parentNode.querySelector('.invalid-feedback');
          if (feedback) feedback.textContent = '';
          return;
        }

        // Check if input contains at least one letter and only allowed characters
        const isValid = /^[A-Za-z\s-]+$/.test(value) && /[A-Za-z]/.test(value);

        if (!isValid) {
          // Tailwind border classes for validation
          this.classList.add('border-red-500');
          this.classList.remove('border-green-500');
          
          let feedback = this.parentNode.querySelector('.invalid-feedback');
          if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback text-red-500 text-sm mt-1';
            this.parentNode.appendChild(feedback);
          }
          feedback.textContent = 'Please enter a valid city or country name (letters, spaces, or hyphens only)';
        } else {
          // Tailwind border classes for validation
          this.classList.remove('border-red-500');
          this.classList.add('border-green-500');
          
          const feedback = this.parentNode.querySelector('.invalid-feedback');
          if (feedback) feedback.textContent = '';
        }
      });
    }

    // Function to initialize validation for return stop duration inputs
    function initReturnStopDurationValidation(input) {
      input.addEventListener('input', function() {
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
          if (parts[1].length > 1) {
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

      input.addEventListener('blur', function() {
        const value = this.value.trim();
        
        // Skip validation if empty
        if (!value) {
          // Tailwind border classes for validation
          this.classList.remove('border-red-500');
          this.classList.remove('border-green-500');
          const feedback = this.parentNode.querySelector('.invalid-feedback');
          if (feedback) feedback.textContent = '';
          return;
        }
        
        const numValue = parseFloat(value);
        
        // Check if input is a valid number
        if (isNaN(numValue)) {
          this.value = '';
          // Tailwind border classes for validation
          this.classList.add('border-red-500');
          this.classList.remove('border-green-500');
          
          let feedback = this.parentNode.querySelector('.invalid-feedback');
          if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback text-red-500 text-sm mt-1';
            this.parentNode.appendChild(feedback);
          }
          feedback.textContent = 'Please enter a valid number of hours';
          return;
        }
        
        // Format to one decimal place if needed
        if (value.includes('.')) {
          this.value = numValue.toFixed(1);
        } else {
          this.value = numValue.toString();
        }
        
        // Validation for reasonable duration
        if (numValue < 0.5) {
          // Tailwind border classes for validation
          this.classList.add('border-red-500');
          this.classList.remove('border-green-500');
          
          let feedback = this.parentNode.querySelector('.invalid-feedback');
          if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback text-red-500 text-sm mt-1';
            this.parentNode.appendChild(feedback);
          }
          feedback.textContent = 'Duration must be at least 0.5 hours';
        } else if (numValue > 72) {
          // Tailwind border classes for validation
          this.classList.add('border-red-500');
          this.classList.remove('border-green-500');
          
          let feedback = this.parentNode.querySelector('.invalid-feedback');
          if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback text-red-500 text-sm mt-1';
            this.parentNode.appendChild(feedback);
          }
          feedback.textContent = 'Duration cannot exceed 72 hours';
        } else {
          // Tailwind border classes for validation
          this.classList.remove('border-red-500');
          this.classList.add('border-green-500');
          
          const feedback = this.parentNode.querySelector('.invalid-feedback');
          if (feedback) feedback.textContent = '';
        }
      });
    }

    // Initialize validation for existing inputs
    const returnStopCityInputs = document.querySelectorAll('input[name="return_stop_city[]"]');
    const returnStopDurationInputs = document.querySelectorAll('input[name="return_stop_duration[]"]');
    returnStopCityInputs.forEach(input => initReturnStopCityValidation(input));
    returnStopDurationInputs.forEach(input => initReturnStopDurationValidation(input));

    // Handle adding new return stops
    function addReturnStopHandler() {
      const newRow = document.createElement('div');
      newRow.className = 'return-stop-row grid grid-cols-1 md:grid-cols-2 gap-4 mt-4';
      newRow.innerHTML = `
        <div>
          <label class="block text-sm font-medium text-gray-700">Return Stop City</label>
          <input type="text" name="return_stop_city[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., Dubai" maxlength="50">
          <div class="invalid-feedback text-red-500 text-sm mt-1"></div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Return Stop Duration (hours)</label>
          <div class="flex">
            <input type="text" name="return_stop_duration[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., 2">
            <button type="button" class="ml-2 mt-1 px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700" onclick="this.closest('.return-stop-row').remove()">Remove</button>
          </div>
          <div class="invalid-feedback text-red-500 text-sm mt-1"></div>
        </div>
      `;
      
      // Insert before the add button container
      const addButtonContainer = addReturnStopButton.closest('div');
      returnStopsContainer.insertBefore(newRow, addButtonContainer);
      
      // Initialize validation for the new inputs
      const newCityInput = newRow.querySelector('input[name="return_stop_city[]"]');
      const newDurationInput = newRow.querySelector('input[name="return_stop_duration[]"]');
      initReturnStopCityValidation(newCityInput);
      initReturnStopDurationValidation(newDurationInput);
    }

    // Add click handler for adding new stops
    addReturnStopButton.addEventListener('click', addReturnStopHandler);

    // Form validation on submit
    const form = returnStopsContainer.closest('form');
    if (form) {
      form.addEventListener('submit', function(e) {
        let isValid = true;
        
        if (hasReturnStops.checked) {
          const cityInputs = document.querySelectorAll('input[name="return_stop_city[]"]');
          const durationInputs = document.querySelectorAll('input[name="return_stop_duration[]"]');
          
          if (cityInputs.length === 0 || durationInputs.length === 0) {
            e.preventDefault();
            alert('Please add at least one return stop city and duration.');
            return;
          }
          
          cityInputs.forEach(input => {
            const value = input.value.trim();
            const isValidCity = value && /^[A-Za-z\s-]+$/.test(value) && /[A-Za-z]/.test(value);
            
            if (!isValidCity) {
              // Tailwind border classes for validation
              input.classList.add('border-red-500');
              input.classList.remove('border-green-500');
              
              let feedback = input.parentNode.querySelector('.invalid-feedback');
              if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback text-red-500 text-sm mt-1';
                input.parentNode.appendChild(feedback);
              }
              feedback.textContent = value ? 'Please enter a valid city or country name (letters, spaces, or hyphens only)' : 'Stop city is required';
              isValid = false;
            }
          });
          
          durationInputs.forEach(input => {
            const value = parseFloat(input.value);
            if (!input.value.trim() || isNaN(value) || value < 0.5 || value > 72) {
              // Tailwind border classes for validation
              input.classList.add('border-red-500');
              input.classList.remove('border-green-500');
              
              let feedback = input.parentNode.querySelector('.invalid-feedback');
              if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback text-red-500 text-sm mt-1';
                input.parentNode.appendChild(feedback);
              }
              feedback.textContent = !input.value.trim() ? 'Please enter a duration' :
                                    isNaN(value) ? 'Please enter a valid number' :
                                    value < 0.5 ? 'Duration must be at least 0.5 hours' :
                                    'Duration cannot exceed 72 hours';
              isValid = false;
            }
          });
        }
        
        if (!isValid) {
          e.preventDefault();
          alert('Please correct the highlighted errors before submitting.');
        }
      });
    }
  });
}

document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector("form"); // Make sure your form tag is wrapped properly
  form.addEventListener("submit", function (e) {
    // Get fields
    const economy = document.getElementById("economy_seats");
    const business = document.getElementById("business_seats");
    const firstClass = document.getElementById("first_class_seats");

    // Reset previous errors
    [economy, business, firstClass].forEach(field => {
      field.classList.remove("is-invalid");
    });

    let isValid = true;

    // Validate each seat field
    [economy, business, firstClass].forEach(field => {
      const value = parseInt(field.value.trim());
      if (isNaN(value) || value <= 0) {
        field.classList.add("is-invalid");
        isValid = false;
      }
    });

    if (!isValid) {
      e.preventDefault(); // Prevent form submission
      alert("Please enter valid seat numbers (positive integers) in all required fields.");
    }
  });
});






document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector("form"); // Make sure your form tag is wrapped properly
  form.addEventListener("submit", function (e) {
    // Get fields
    const economy = document.getElementById("economy_seats");
    const business = document.getElementById("business_seats");
    const firstClass = document.getElementById("first_class_seats");

    // Reset previous errors
    [economy, business, firstClass].forEach(field => {
      field.classList.remove("is-invalid");
    });

    let isValid = true;

    // Validate each seat field
    [economy, business, firstClass].forEach(field => {
      const value = parseInt(field.value.trim());
      if (isNaN(value) || value <= 0) {
        field.classList.add("is-invalid");
        isValid = false;
      }
    });

    if (!isValid) {
      e.preventDefault(); // Prevent form submission
      alert("Please enter valid seat numbers (positive integers) in all required fields.");
    }
  });
});




