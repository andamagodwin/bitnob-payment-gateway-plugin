/******/ (() => { // webpackBootstrap
/*!***********************************!*\
  !*** ./src/bitnobgateway/view.js ***!
  \***********************************/
/**
 * Bitnob Gateway Frontend JavaScript
 * Handles AJAX form submission and UI interactions
 */

document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('bitnob-form');
  const loadingDiv = document.getElementById('bitnob-loading');
  const errorDiv = document.getElementById('bitnob-error');
  const invoiceDiv = document.getElementById('bitnob-invoice');
  const submitBtn = document.getElementById('bitnob-submit-btn');
  const copyBtn = document.getElementById('copy-invoice');
  const createAnotherBtn = document.getElementById('create-another');
  const changeEmailBtn = document.getElementById('change-email-btn');
  const emailInput = document.getElementById('bitnob-email');
  if (!form || !window.bitnobData) return;

  // Form submission handler
  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    // Clear previous states
    hideAllMessages();
    clearFieldErrors();

    // Get form data
    const formData = new FormData();
    formData.append('action', 'bitnob_create_invoice');
    formData.append('nonce', window.bitnobData.nonce);
    formData.append('amount', document.getElementById('bitnob-amount').value);
    formData.append('email', document.getElementById('bitnob-email').value);
    formData.append('description', document.getElementById('bitnob-description').value);

    // Validate form
    if (!validateForm()) {
      return;
    }

    // Show loading state
    showLoading();
    setButtonLoading(true);
    try {
      const response = await fetch(window.bitnobData.ajaxUrl, {
        method: 'POST',
        body: formData
      });
      const result = await response.json();
      if (result.success) {
        showInvoice(result.data);
      } else {
        showError(result.data || 'An error occurred while creating the invoice.');
      }
    } catch (error) {
      console.error('AJAX Error:', error);
      showError('Network error. Please check your connection and try again.');
    } finally {
      hideLoading();
      setButtonLoading(false);
    }
  });

  // Copy to clipboard functionality
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      const invoiceRequest = document.getElementById('invoice-request').textContent;
      navigator.clipboard.writeText(invoiceRequest).then(function () {
        // Show success feedback
        const originalText = copyBtn.querySelector('.copy-text').textContent;
        copyBtn.querySelector('.copy-text').textContent = 'Copied!';
        copyBtn.classList.add('copied');
        setTimeout(function () {
          copyBtn.querySelector('.copy-text').textContent = originalText;
          copyBtn.classList.remove('copied');
        }, 2000);
      }).catch(function (err) {
        console.error('Failed to copy: ', err);
        // Fallback for older browsers
        fallbackCopyTextToClipboard(invoiceRequest);
      });
    });
  }

  // Create another invoice
  if (createAnotherBtn) {
    createAnotherBtn.addEventListener('click', function () {
      hideAllMessages();
      form.style.display = 'block';
      form.reset();

      // Restore user email if logged in
      if (window.bitnobData.user.isLoggedIn) {
        setTimeout(() => {
          emailInput.value = window.bitnobData.user.email;
          emailInput.setAttribute('readonly', 'readonly');
          emailInput.removeAttribute('required');
          if (changeEmailBtn) {
            changeEmailBtn.textContent = 'Change';
          }
          // Reset visual styling
          emailInput.style.borderColor = '#333';
          emailInput.style.backgroundColor = '#2c2c2e';
        }, 0);
      }
      document.getElementById('bitnob-description').value = 'Lightning payment';
    });
  }

  // Change email functionality for logged-in users
  if (changeEmailBtn && window.bitnobData.user.isLoggedIn) {
    changeEmailBtn.addEventListener('click', function () {
      const currentlyReadonly = emailInput.hasAttribute('readonly');
      if (currentlyReadonly) {
        // Enable editing
        emailInput.removeAttribute('readonly');
        emailInput.focus();
        emailInput.select();
        emailInput.setAttribute('required', 'required');
        changeEmailBtn.textContent = 'Restore';

        // Add visual indication
        emailInput.style.borderColor = '#f7931a';
        emailInput.style.backgroundColor = '#2c2c2e';
      } else {
        // Restore original email
        emailInput.value = window.bitnobData.user.email;
        emailInput.setAttribute('readonly', 'readonly');
        emailInput.removeAttribute('required');
        changeEmailBtn.textContent = 'Change';

        // Remove visual indication
        emailInput.style.borderColor = '#333';
        emailInput.style.backgroundColor = '#2c2c2e';
      }
    });

    // Handle form reset to restore original email
    form.addEventListener('reset', function () {
      if (window.bitnobData.user.isLoggedIn) {
        setTimeout(() => {
          emailInput.value = window.bitnobData.user.email;
          emailInput.setAttribute('readonly', 'readonly');
          emailInput.removeAttribute('required');
          if (changeEmailBtn) {
            changeEmailBtn.textContent = 'Change';
          }
        }, 0);
      }
    });
  }

  // Utility functions
  function validateForm() {
    let isValid = true;
    const amount = document.getElementById('bitnob-amount');
    const email = document.getElementById('bitnob-email');
    if (!amount.value || parseInt(amount.value) < 1) {
      showFieldError('amount-error', 'Please enter a valid amount (minimum 1 satoshi)');
      isValid = false;
    }

    // Only validate email if it's not readonly (i.e., user is not logged in or has chosen to change email)
    if (!email.hasAttribute('readonly')) {
      if (!email.value || !isValidEmail(email.value)) {
        showFieldError('email-error', 'Please enter a valid email address');
        isValid = false;
      }
    } else if (window.bitnobData.user.isLoggedIn && !email.value) {
      // This shouldn't happen, but just in case
      showFieldError('email-error', 'Email is required');
      isValid = false;
    }
    return isValid;
  }
  function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }
  function showFieldError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
      errorElement.textContent = message;
      errorElement.style.display = 'block';
    }
  }
  function clearFieldErrors() {
    const errorElements = document.querySelectorAll('.bitnob-field-error');
    errorElements.forEach(element => {
      element.textContent = '';
      element.style.display = 'none';
    });
  }
  function showLoading() {
    loadingDiv.style.display = 'block';
    form.style.display = 'none';
  }
  function hideLoading() {
    loadingDiv.style.display = 'none';
  }
  function showError(message) {
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    form.style.display = 'block';
  }
  function showInvoice(data) {
    // Populate invoice data
    document.getElementById('invoice-amount').textContent = data.amount.toLocaleString();
    document.getElementById('invoice-description').textContent = data.description;
    document.getElementById('invoice-request').textContent = data.request;
    document.getElementById('invoice-qr').src = data.qr_url;

    // Show invoice section
    invoiceDiv.style.display = 'block';
    form.style.display = 'none';

    // Show success message for logged-in users
    if (window.bitnobData.user.isLoggedIn) {
      showSuccessMessage(`Invoice created for ${window.bitnobData.user.displayName}`);
    }
  }
  function showSuccessMessage(message) {
    // Create a temporary success message
    const successMsg = document.createElement('div');
    successMsg.className = 'bitnob-success-message';
    successMsg.innerHTML = `
            <div class="success-icon">✅</div>
            <div class="success-text">${message}</div>
        `;
    invoiceDiv.insertBefore(successMsg, invoiceDiv.firstChild);

    // Remove after 5 seconds
    setTimeout(() => {
      if (successMsg.parentNode) {
        successMsg.parentNode.removeChild(successMsg);
      }
    }, 5000);
  }
  function hideAllMessages() {
    errorDiv.style.display = 'none';
    invoiceDiv.style.display = 'none';
    loadingDiv.style.display = 'none';
  }
  function setButtonLoading(loading) {
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    if (loading) {
      btnText.style.display = 'none';
      btnLoading.style.display = 'flex';
      submitBtn.disabled = true;
    } else {
      btnText.style.display = 'block';
      btnLoading.style.display = 'none';
      submitBtn.disabled = false;
    }
  }
  function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;

    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
      const successful = document.execCommand('copy');
      if (successful) {
        copyBtn.querySelector('.copy-text').textContent = 'Copied!';
        copyBtn.classList.add('copied');
        setTimeout(function () {
          copyBtn.querySelector('.copy-text').textContent = 'Copy';
          copyBtn.classList.remove('copied');
        }, 2000);
      }
    } catch (err) {
      console.error('Fallback: Oops, unable to copy', err);
    }
    document.body.removeChild(textArea);
  }
});
/******/ })()
;
//# sourceMappingURL=view.js.map