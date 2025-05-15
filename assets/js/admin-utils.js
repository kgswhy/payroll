/**
 * Admin utility functions for the payroll system
 */

/**
 * Show loading indicator in a container
 * @param {HTMLElement} container - The container to show loading in
 * @param {string} message - Optional loading message
 */
function showLoading(container, message = 'Loading...') {
  container.innerHTML = `<div class="loading"><i class="fas fa-spinner fa-spin"></i> ${message}</div>`;
}

/**
 * Show error message in a container
 * @param {HTMLElement} container - The container to show error in
 * @param {string} message - Error message to display
 */
function showError(container, message) {
  container.innerHTML = `
    <div class="admin-alert admin-alert-danger">
      <i class="fas fa-exclamation-circle"></i> ${message}
    </div>
  `;
}

/**
 * Show empty state message in a container
 * @param {HTMLElement} container - The container to show empty state
 * @param {string} message - Message to display
 * @param {string} icon - FontAwesome icon class (without 'fas fa-')
 */
function showEmptyState(container, message, icon = 'inbox') {
  container.innerHTML = `
    <div class="no-data">
      <i class="fas fa-${icon}"></i>
      <p>${message}</p>
    </div>
  `;
}

/**
 * Format date to localized string
 * @param {string} dateString - Date string in any format
 * @param {boolean} includeTime - Whether to include time in the output
 * @returns {string} Formatted date string
 */
function formatDate(dateString, includeTime = false) {
  const date = new Date(dateString);
  const options = {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  };
  
  if (includeTime) {
    options.hour = '2-digit';
    options.minute = '2-digit';
  }
  
  return date.toLocaleDateString('id-ID', options);
}

/**
 * Format money value to Indonesian Rupiah
 * @param {number} amount - Amount to format
 * @returns {string} Formatted amount in Rupiah
 */
function formatRupiah(amount) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount);
}

/**
 * Execute a fetch request with error handling
 * @param {string} url - URL to fetch
 * @param {Object} options - Fetch options
 * @returns {Promise} Promise that resolves to parsed JSON or rejects with error
 */
async function fetchWithErrorHandling(url, options = {}) {
  try {
    const response = await fetch(url, options);
    
    if (!response.ok) {
      throw new Error(`Server responded with ${response.status}: ${response.statusText}`);
    }
    
    const data = await response.json();
    
    if (data.status === 'error') {
      throw new Error(data.message || 'Unknown error occurred');
    }
    
    return data;
  } catch (error) {
    console.error('Fetch error:', error);
    throw error;
  }
}

/**
 * Toggle visibility of an element
 * @param {string} elementId - ID of the element to toggle
 */
function toggleElement(elementId) {
  const element = document.getElementById(elementId);
  if (!element) return;
  
  element.style.display = element.style.display === 'none' ? 'block' : 'none';
}

/**
 * Create a modal and append to body
 * @param {string} title - Modal title
 * @param {string} content - HTML content for modal body
 * @param {function} onSubmit - Function to call on submit
 * @returns {Object} Modal control object with show and hide methods
 */
function createModal(title, content, onSubmit) {
  // Create modal elements
  const modalId = 'modal-' + Math.random().toString(36).substr(2, 9);
  const modalHtml = `
    <div id="${modalId}" class="modal">
      <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h2>${title}</h2>
        <div class="modal-body">
          ${content}
        </div>
        <div class="form-actions">
          <button type="button" class="admin-btn admin-btn-primary" id="${modalId}-submit">Submit</button>
          <button type="button" class="admin-btn" id="${modalId}-cancel">Cancel</button>
        </div>
      </div>
    </div>
  `;
  
  // Append to body
  document.body.insertAdjacentHTML('beforeend', modalHtml);
  
  const modal = document.getElementById(modalId);
  const closeBtn = modal.querySelector('.modal-close');
  const submitBtn = document.getElementById(`${modalId}-submit`);
  const cancelBtn = document.getElementById(`${modalId}-cancel`);
  
  // Add event listeners
  closeBtn.addEventListener('click', () => {
    modal.style.display = 'none';
  });
  
  cancelBtn.addEventListener('click', () => {
    modal.style.display = 'none';
  });
  
  submitBtn.addEventListener('click', () => {
    if (typeof onSubmit === 'function') {
      onSubmit();
    }
  });
  
  // Close when clicking outside
  window.addEventListener('click', (event) => {
    if (event.target === modal) {
      modal.style.display = 'none';
    }
  });
  
  return {
    show: () => {
      modal.style.display = 'block';
    },
    hide: () => {
      modal.style.display = 'none';
    },
    getElement: () => modal
  };
}

/**
 * Formats a number with separator for thousands
 * @param {number} number - The number to format
 * @param {number} decimals - Number of decimal places (default: 0)
 * @returns {string} Formatted number
 */
function formatNumber(number, decimals = 0) {
    return Number(number).toLocaleString('id-ID', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

/**
 * Formats currency in Indonesian Rupiah
 * @param {number} amount - The amount to format
 * @returns {string} Formatted currency
 */
function formatCurrency(amount) {
    return 'Rp ' + formatNumber(amount, 0);
}

/**
 * Formats a date string
 * @param {string} dateString - Date string to format
 * @param {string} format - Format type ('short', 'long', 'time')
 * @returns {string} Formatted date
 */
function formatDate(dateString, format = 'short') {
    const date = new Date(dateString);
    
    if (isNaN(date.getTime())) {
        return 'Invalid date';
    }
    
    if (format === 'short') {
        return date.toLocaleDateString('id-ID');
    } else if (format === 'long') {
        return date.toLocaleDateString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } else if (format === 'time') {
        return date.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        });
    } else if (format === 'datetime') {
        return date.toLocaleDateString('id-ID') + ' ' + 
               date.toLocaleTimeString('id-ID', {
                   hour: '2-digit',
                   minute: '2-digit'
               });
    }
    
    return dateString;
}

/**
 * Enhances stats cards with animations and interaction
 */
function enhanceStatsCards() {
    const statCards = document.querySelectorAll('.stat-card');
    
    statCards.forEach(card => {
        // Add slight delay to each card for staggered animation effect
        const randomDelay = Math.floor(Math.random() * 300);
        card.style.animationDelay = `${randomDelay}ms`;
        card.classList.add('animate-in');
        
        // Add hover effect for icon
        const icon = card.querySelector('.stat-icon');
        if (icon) {
            card.addEventListener('mouseenter', () => {
                icon.classList.add('pulse');
            });
            
            card.addEventListener('mouseleave', () => {
                icon.classList.remove('pulse');
            });
        }
    });
}

/**
 * Initializes the page
 */
document.addEventListener('DOMContentLoaded', function() {
    // Format all currency elements
    document.querySelectorAll('.format-currency').forEach(el => {
        const value = parseFloat(el.textContent.replace(/[^\d.-]/g, ''));
        if (!isNaN(value)) {
            el.textContent = formatCurrency(value);
        }
    });
    
    // Format all number elements
    document.querySelectorAll('.format-number').forEach(el => {
        const value = parseFloat(el.textContent.replace(/[^\d.-]/g, ''));
        const decimals = parseInt(el.dataset.decimals || 0);
        if (!isNaN(value)) {
            el.textContent = formatNumber(value, decimals);
        }
    });
    
    // Format all date elements
    document.querySelectorAll('.format-date').forEach(el => {
        const dateString = el.textContent;
        const format = el.dataset.format || 'short';
        el.textContent = formatDate(dateString, format);
    });
    
    // Enhance stats cards
    enhanceStatsCards();
}); 