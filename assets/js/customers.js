/**
 * ORPL Customers Manager
 */
(function ($) {
  "use strict";

  const ORPLCustomers = {
    // Configuration
    config: {
      isSubmitting: false,
      currentPage: 1,
      perPage: 10,
      totalPages: 1,
      totalItems: 0,
      searchTerm: "",
      statusFilter: "",
      ajaxUrl: "",
      strings: {},
      nonces: {},
    },

    /**
     * Initialize customers module
     */
    init: function () {
      // Load configuration from localized script
      if (typeof orplCustomersData !== "undefined") {
        this.config.ajaxUrl = orplCustomersData.ajaxUrl || "";
        this.config.nonces = {
          get_customers: orplCustomersData.nonce_get_customers || "",
          add_customer: orplCustomersData.nonce_add_customer || "",
          edit_customer: orplCustomersData.nonce_edit_customer || "",
          delete_customer: orplCustomersData.nonce_delete_customer || "",
        };
        this.config.strings = orplCustomersData.strings || {};
      } else {
        alert("Configuration error: Please refresh the page.");
        return;
      }

      // Bind events with proper context
      this.bindEvents();

      // Load initial customers
      this.loadORPLCustomers();
    },

    /**
     * Bind all event handlers with proper context
     */
    bindEvents: function () {
      // Use arrow functions to preserve 'this' context
      let self = this;

      // Search functionality with debounce
      let searchTimeout;
      $("#customer-search").on("input", () => {
        clearTimeout(searchTimeout);
        self.config.searchTerm = $("#customer-search").val().trim();

        searchTimeout = setTimeout(() => {
          self.loadORPLCustomers(1);
        }, 500);
      });

      // Status filter
      $("#status-filter").on("change", () => {
        self.config.statusFilter = $("#status-filter").val();
        self.loadORPLCustomers(1);
      });

      // Per page change
      $("#per-page-select").on("change", () => {
        self.config.perPage = parseInt($("#per-page-select").val());
        self.loadORPLCustomers(1);
      });

      // Refresh button
      $("#refresh-customers").on("click", () => {
        self.loadORPLCustomers(self.config.currentPage);
      });

      // Pagination handlers
      $(".first-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadORPLCustomers(1);
      });

      $(".prev-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadORPLCustomers(self.config.currentPage - 1);
      });

      $(".next-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadORPLCustomers(self.config.currentPage + 1);
      });

      $(".last-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadORPLCustomers(self.config.totalPages);
      });

      $("#current-page-selector").on("keypress", function (e) {
        if (e.which === 13) {
          // Enter key
          let page = parseInt($(this).val());
          if (page >= 1 && page <= self.config.totalPages) {
            self.loadORPLCustomers(page);
          }
        }
      });

      // Form submission
      $("#add-customer-form").on("submit", (e) => {
        e.preventDefault();
        self.handleCustomerSubmit();
      });

      // Cancel edit
      $("#cancel-edit").on("click", () => {
        self.resetForm();
      });

      // Edit customer (delegated)
      $(document).on("click", ".edit-customer", function () {
        self.handleEditCustomer(this);
      });

      // Delete customer (delegated)
      $(document).on("click", ".delete-customer", function () {
        self.handleDeleteCustomer(this);
      });
    },

    /**
     * Load customers via AJAX
     */
    loadORPLCustomers: function (page = 1) {
      const self = this;
      self.config.currentPage = page;

      let tbody = $("#customer-list");
      tbody.html('<tr><td colspan="6" class="text-center p-5"><span class="spinner is-active"></span> ' + self.config.strings.loading_customers + "</td></tr>");

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "orpl_get_customers",
          page: self.config.currentPage,
          per_page: self.config.perPage,
          search: self.config.searchTerm,
          status: self.config.statusFilter,
          nonce: self.config.nonces.get_customers,
        },
        success: function (response) {
          tbody.empty();
          if (response.success) {
            if (!response.data.customers.length) {
              tbody.append('<tr><td colspan="6" class="text-center p-5 text-muted">' + self.config.strings.no_customers + "</td></tr>");
              self.updateSummaryORPLCards();
              self.updateORPLPagination(response.data.pagination);
              return;
            }

            $.each(response.data.customers, function (_, customer) {
              let row = $("<tr>").attr("data-customer-id", customer.id);

              // Name column
              row.append($("<td>").text(customer.name));

              // Email column
              row.append($("<td>").text(customer.email));

              // Mobile column
              row.append($("<td>").text(customer.mobile || "-"));

              // Address column
              row.append($("<td>").text(customer.address || "-"));

              // Status
              let statusText = customer.status === "active" ? self.config.strings.active : self.config.strings.inactive;
              row.append($("<td>").text(statusText));

              // Actions column
              let actions = $("<td class='text-center'>");
              actions.append($("<button>").addClass("btn btn-sm btn-secondary mr-2 edit-customer").text(self.config.strings.edit));
              actions.append($("<button>").addClass("btn btn-sm btn-danger delete-customer").text(self.config.strings.delete));
              row.append(actions);

              tbody.append(row);
            });

            self.updateSummaryORPLCards(response.data.customers);
            self.updateORPLPagination(response.data.pagination);
          } else {
            tbody.append('<tr><td colspan="6" class="text-center text-danger">' + response.data + "</td></tr>");
          }
        },
        error: function () {
          $("#customer-list").html('<tr><td colspan="6" class="text-center text-danger">' + self.config.strings.failed_load + "</td></tr>");
        },
      });
    },

    /**
     * Update summary cards
     */
    updateSummaryORPLCards: function (customers = []) {
      let active = 0,
        inactive = 0,
        total = customers.length;

      if (customers.length > 0) {
        customers.forEach((customer) => {
          if (customer.status === "active") {
            active++;
          } else if (customer.status === "inactive") {
            inactive++;
          }
        });
      }

      // Update the summary cards using IDs
      $("#active-customers-count").text(active);
      $("#inactive-customers-count").text(inactive);
      $("#total-customers-count").text(total);
    },

    /**
     * Update pagination UI
     */
    updateORPLPagination: function (pagination) {
      this.config.totalPages = pagination.total_pages;
      this.config.totalItems = pagination.total_items;

      // Update displaying text
      $("#displaying-num").text(pagination.total_items + " " + this.config.strings.items);

      // Update page input and total pages
      $("#current-page-selector").val(this.config.currentPage);
      $(".total-pages").text(this.config.totalPages);

      // Update pagination buttons state
      $(".first-page, .prev-page").toggleClass("disabled", this.config.currentPage === 1);
      $(".next-page, .last-page").toggleClass("disabled", this.config.currentPage === this.config.totalPages);
    },

    /**
     * Handle customer form submission
     */
    handleCustomerSubmit: function () {
      const self = this;

      // Prevent double submission
      if (self.config.isSubmitting) {
        return false;
      }

      let id = $("#customer-id").val();
      let action = id ? "orpl_edit_customer" : "orpl_add_customer";
      let name = $("#customer-name").val().trim();
      let email = $("#customer-email").val().trim();
      let mobile = $("#customer-mobile").val().trim();
      let address = $("#customer-address").val().trim();
      let status = $("#customer-status").val();

      // Validation
      if (!name) {
        alert(self.config.strings.name_required);
        return false;
      }
      if (!email) {
        alert(self.config.strings.email_required);
        return false;
      }
      if (!this.isValidEmail(email)) {
        alert(self.config.strings.email_invalid);
        return false;
      }

      // Set submitting state
      self.config.isSubmitting = true;
      self.setButtonLoading(true);

      $.post(
        self.config.ajaxUrl,
        {
          action: action,
          id: id,
          name: name,
          email: email,
          mobile: mobile,
          address: address,
          status: status,
          nonce: id ? self.config.nonces.edit_customer : self.config.nonces.add_customer,
        },
        function (res) {
          if (res.success) {
            self.resetForm();
            self.loadORPLCustomers(self.config.currentPage);
          } else {
            alert(self.config.strings.error + ": " + res.data);
          }
        }
      )
        .fail(function () {
          alert(self.config.strings.request_failed);
        })
        .always(function () {
          // Reset submitting state
          self.config.isSubmitting = false;
          self.setButtonLoading(false);
        });
    },

    /**
     * Handle edit customer button click
     */
    handleEditCustomer: function (button) {
      const self = this;
      let row = $(button).closest("tr");
      let customerId = row.data("customer-id");

      // Get customer details
      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "orpl_get_customers",
          id: customerId,
          nonce: self.config.nonces.get_customers,
        },
        success: function (response) {
          if (response.success && response.data.customers.length > 0) {
            let customer = response.data.customers[0];
            if (customer) {
              $("#customer-id").val(customer.id);
              $("#customer-name").val(customer.name);
              $("#customer-email").val(customer.email);
              $("#customer-mobile").val(customer.mobile || "");
              $("#customer-address").val(customer.address || "");
              $("#customer-status").val(customer.status);

              $("#form-title").text(self.config.strings.edit_customer);
              $("#submit-customer").find(".btn-text").text(self.config.strings.update_customer);
              $("#cancel-edit").show();
            }
          }
        },
      });
    },

    /**
     * Handle delete customer button click
     */
    handleDeleteCustomer: function (button) {
      const self = this;

      if (!confirm(self.config.strings.confirm_delete)) {
        return;
      }

      let $button = $(button);
      let originalText = $button.text();
      let customerId = $button.closest("tr").data("customer-id");

      // Disable button and show loading
      $button.prop("disabled", true).text(self.config.strings.deleting);

      $.post(
        self.config.ajaxUrl,
        {
          action: "orpl_delete_customer",
          id: customerId,
          nonce: self.config.nonces.delete_customer,
        },
        function (res) {
          if (res.success) {
            self.loadORPLCustomers(self.config.currentPage);
          } else {
            alert(res.data);
          }
        }
      )
        .fail(function () {
          alert(self.config.strings.delete_failed);
        })
        .always(function () {
          // Re-enable button
          $button.prop("disabled", false).text(originalText);
        });
    },

    /**
     * Set loading state for submit button
     */
    setButtonLoading: function (loading) {
      let button = $("#submit-customer");
      let spinner = button.find(".spinner");
      let btnText = button.find(".btn-text");

      if (loading) {
        button.prop("disabled", true);
        spinner.show();
        btnText.text($("#customer-id").val() ? this.config.strings.updating : this.config.strings.saving);
      } else {
        button.prop("disabled", false);
        spinner.hide();
        btnText.text($("#customer-id").val() ? this.config.strings.update_customer : this.config.strings.save_customer);
      }
    },

    /**
     * Validate email address
     */
    isValidEmail: function (email) {
      var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    },

    /**
     * Reset form to initial state
     */
    resetForm: function () {
      $("#customer-id").val("");
      $("#customer-name").val("");
      $("#customer-email").val("");
      $("#customer-mobile").val("");
      $("#customer-address").val("");
      $("#customer-status").val("active");
      $("#form-title").text(this.config.strings.add_new_customer);
      $("#submit-customer").find(".btn-text").text(this.config.strings.save_customer);
      $("#cancel-edit").hide();
      $("#customer-name").focus();

      // Ensure button is enabled
      this.setButtonLoading(false);
    },
  };

  /**
   * Initialize when document is ready
   */
  $(document).ready(function () {
    if ($("#add-customer-form").length) {
      ORPLCustomers.init();
    }
  });
})(jQuery);
