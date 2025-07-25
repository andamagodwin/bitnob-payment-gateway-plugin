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
      document.getElementById('bitnob-description').value = 'Lightning payment';
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
    if (!email.value || !isValidEmail(email.value)) {
      showFieldError('email-error', 'Please enter a valid email address');
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