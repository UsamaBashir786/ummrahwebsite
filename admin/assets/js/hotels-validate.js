document.addEventListener('DOMContentLoaded', function () {
  // Form elements
  const form = document.getElementById('hotelForm');
  const hotelImagesInput = document.getElementById('hotel_images');
  const fileList = document.getElementById('file-list');
  const selectedFiles = document.getElementById('selected-files');
  const fileError = document.getElementById('file-error');
  const sizeWarning = document.getElementById('size-warning');
  const hotelNameInput = document.getElementById('hotel_name');
  const hotelNameError = document.getElementById('hotel_name_error');
  const hotelNameCounter = document.getElementById('hotel_name_counter');
  const locationSelect = document.querySelector('select[name="location"]');
  const roomCountInput = document.getElementById('room_count');
  const roomCountError = document.getElementById('room_count_error');
  const priceInput = document.getElementById('price');
  const priceError = document.getElementById('price_error');
  const descriptionInput = document.getElementById('description');
  const descError = document.getElementById('desc_error');
  const wordCount = document.getElementById('word_count');
  const limitReached = document.getElementById('limit_reached');

  // Validation settings
  const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB in bytes
  const ALLOWED_FILE_TYPES = ['image/jpeg', 'image/png'];
  const MAX_HOTEL_NAME_LENGTH = 25;
  const HOTEL_NAME_REGEX = /^[A-Za-z\s]*$/;
  const MIN_ROOMS = 1;
  const MAX_ROOMS = 10;
  const MAX_PRICE = 50000;
  const MAX_WORDS = 200;

  // Helper function to count words
  function countWords(text) {
      const words = text.trim().split(/\s+/);
      return words.length === 1 && words[0] === '' ? 0 : words.length;
  }

  // Helper function to show temporary error
  function showTempError(element, message, duration = 3000) {
      element.textContent = message;
      element.classList.remove('hidden');
      setTimeout(() => {
          if (element.textContent === message) {
              element.classList.add('hidden');
          }
      }, duration);
  }

  // Hotel Images Validation (unchanged, as no typing involved)
  function validateImages() {
      const files = hotelImagesInput.files;
      let isValid = true;
      let errorMessages = [];
      selectedFiles.innerHTML = '';
      sizeWarning.classList.add('hidden');

      if (files.length === 0) {
          errorMessages.push('At least one image is required.');
          isValid = false;
      } else {
          Array.from(files).forEach((file) => {
              const li = document.createElement('li');
              li.textContent = `${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
              selectedFiles.appendChild(li);

              if (!ALLOWED_FILE_TYPES.includes(file.type)) {
                  errorMessages.push(`File ${file.name} is not a valid type (PNG or JPG only).`);
                  isValid = false;
              }
              if (file.size > MAX_FILE_SIZE) {
                  errorMessages.push(`File ${file.name} exceeds 10MB.`);
                  sizeWarning.classList.remove('hidden');
                  sizeWarning.textContent = `File ${file.name} is too large (max 10MB).`;
                  isValid = false;
              }
          });
      }

      fileList.classList.toggle('hidden', files.length === 0);
      fileError.textContent = errorMessages.join(' ');
      fileError.classList.toggle('hidden', errorMessages.length === 0);
      return isValid;
  }

  // Hotel Name Validation
  function validateHotelName() {
      let value = hotelNameInput.value;
      let isValid = true;

      if (!value.trim()) {
          showTempError(hotelNameError, 'Hotel name is required.');
          isValid = false;
      } else if (!HOTEL_NAME_REGEX.test(value)) {
          // Filter out invalid characters
          value = value.replace(/[^A-Za-z\s]/g, '');
          hotelNameInput.value = value;
          showTempError(hotelNameError, 'Only letters and spaces are allowed.');
          isValid = false;
      } else if (value.length > MAX_HOTEL_NAME_LENGTH) {
          hotelNameInput.value = value.slice(0, MAX_HOTEL_NAME_LENGTH);
          showTempError(hotelNameError, `Maximum ${MAX_HOTEL_NAME_LENGTH} characters allowed.`);
          isValid = false;
      } else {
          hotelNameError.classList.add('hidden');
      }

      hotelNameCounter.textContent = hotelNameInput.value.length;
      hotelNameInput.classList.toggle('border-red-500', !isValid);
      return isValid;
  }

  // Prevent invalid characters for Hotel Name
  hotelNameInput.addEventListener('beforeinput', function (event) {
      if (event.data) {
          if (!HOTEL_NAME_REGEX.test(event.data)) {
              event.preventDefault();
              showTempError(hotelNameError, 'Only letters and spaces are allowed.');
          } else if (hotelNameInput.value.length >= MAX_HOTEL_NAME_LENGTH) {
              event.preventDefault();
              showTempError(hotelNameError, `Maximum ${MAX_HOTEL_NAME_LENGTH} characters allowed.`);
          }
      }
  });

  // Location Validation (unchanged, as no typing involved)
  function validateLocation() {
      const value = locationSelect.value;
      const isValid = value !== '';
      locationSelect.classList.toggle('border-red-500', !isValid);
      return isValid;
  }

  // Room Count Validation
  function validateRoomCount() {
      let value = roomCountInput.value;
      let isValid = true;

      if (!value || isNaN(value) || value < MIN_ROOMS) {
          showTempError(roomCountError, `Number of rooms must be between ${MIN_ROOMS} and ${MAX_ROOMS}.`);
          isValid = false;
      } else if (value > MAX_ROOMS) {
          roomCountInput.value = MAX_ROOMS;
          showTempError(roomCountError, `Maximum ${MAX_ROOMS} rooms allowed.`);
          isValid = false;
      } else {
          roomCountError.classList.add('hidden');
      }

      roomCountInput.classList.toggle('border-red-500', !isValid);
      return isValid;
  }

  // Prevent invalid input for Room Count
  roomCountInput.addEventListener('beforeinput', function (event) {
      if (event.data) {
          const newValue = (roomCountInput.value + event.data).replace(/^0+/, '');
          const numValue = parseInt(newValue, 10);

          if (!/^[0-9]$/.test(event.data)) {
              event.preventDefault();
              showTempError(roomCountError, 'Only numbers are allowed.');
          } else if (numValue > MAX_ROOMS || newValue.length > 2) {
              event.preventDefault();
              roomCountInput.value = MAX_ROOMS;
              showTempError(roomCountError, `Maximum ${MAX_ROOMS} rooms allowed.`);
          }
      }
  });

  // Price Validation
  function validatePrice() {
      let value = priceInput.value;
      let isValid = true;

      if (!value || isNaN(value) || value <= 0) {
          showTempError(priceError, `Price must be between 1 and ${MAX_PRICE.toLocaleString()} PKR.`);
          isValid = false;
      } else if (value > MAX_PRICE) {
          priceInput.value = MAX_PRICE;
          showTempError(priceError, `Maximum ${MAX_PRICE.toLocaleString()} PKR allowed.`);
          isValid = false;
      } else {
          priceError.classList.add('hidden');
      }

      priceInput.classList.toggle('border-red-500', !isValid);
      return isValid;
  }

  // Prevent invalid input for Price
  priceInput.addEventListener('beforeinput', function (event) {
      if (event.data) {
          const newValue = (priceInput.value + event.data).replace(/^0+/, '');
          const numValue = parseInt(newValue, 10);

          if (!/^[0-9]$/.test(event.data)) {
              event.preventDefault();
              showTempError(priceError, 'Only numbers are allowed.');
          } else if (numValue > MAX_PRICE || newValue.length > 5) {
              event.preventDefault();
              priceInput.value = MAX_PRICE;
              showTempError(priceError, `Maximum ${MAX_PRICE.toLocaleString()} PKR allowed.`);
          }
      }
  });

  // Description Validation
  function validateDescription() {
      const value = descriptionInput.value;
      const wordCountValue = countWords(value);
      let isValid = true;

      if (!value.trim()) {
          showTempError(descError, 'Description is required.');
          isValid = false;
      } else if (wordCountValue > MAX_WORDS) {
          const words = value.trim().split(/\s+/).slice(0, MAX_WORDS);
          descriptionInput.value = words.join(' ');
          showTempError(descError, `Description cannot exceed ${MAX_WORDS} words.`);
          isValid = false;
      } else {
          descError.classList.add('hidden');
      }

      wordCount.textContent = wordCountValue;
      limitReached.classList.toggle('hidden', wordCountValue < MAX_WORDS);
      descriptionInput.classList.toggle('border-red-500', !isValid);
      return isValid;
  }

  // Prevent exceeding word limit for Description
  descriptionInput.addEventListener('beforeinput', function (event) {
      const currentWords = countWords(descriptionInput.value);
      if (currentWords >= MAX_WORDS && event.data && !event.data.match(/[\s]/)) {
          event.preventDefault();
          showTempError(descError, `Description cannot exceed ${MAX_WORDS} words.`);
          limitReached.classList.remove('hidden');
      }
  });

  // Real-time validation events
  hotelImagesInput.addEventListener('change', validateImages);
  hotelNameInput.addEventListener('input', validateHotelName);
  roomCountInput.addEventListener('input', validateRoomCount);
  priceInput.addEventListener('input', validatePrice);
  descriptionInput.addEventListener('input', validateDescription);

  // Form submission validation
  form.addEventListener('submit', function (event) {
      const isImagesValid = validateImages();
      const isHotelNameValid = validateHotelName();
      const isLocationValid = validateLocation();
      const isRoomCountValid = validateRoomCount();
      const isPriceValid = validatePrice();
      const isDescriptionValid = validateDescription();

      if (!isImagesValid || !isHotelNameValid || !isLocationValid || !isRoomCountValid || !isPriceValid || !isDescriptionValid) {
          event.preventDefault();
          alert('Please fix the errors in the form before submitting.');
      }
  });
});