/**
 * ORPL Stock Adjustments Manager
 */
(function ($) {
  "use strict";

  let ORPLStockAdjustments = {
    // Configuration
    config: {
      isSubmitting: false,
      addNonce: "",
      getNonce: "",
      deleteNonce: "",
      getProductsNonce: "",
      getStockNonce: "",
      ajaxUrl: "",
      strings: {},
      currentPage: 1,
      perPage: 10,
      totalPages: 1,
      totalItems: 0,
      searchTerm: "",
      typeFilter: "",
      dateFilter: "",
    },

    /**
     * Initialize module
     */
    init: function () {
      // Load configuration from localized script
      if (typeof orplStockAdjustments !== "undefined") {
        this.config.addNonce = orplStockAdjustments.addNonce || "";
        this.config.getNonce = orplStockAdjustments.getNonce || "";
        this.config.deleteNonce = orplStockAdjustments.deleteNonce || "";
        this.config.getProductsNonce = orplStockAdjustments.getProductsNonce || "";
        this.config.getStockNonce = orplStockAdjustments.getStockNonce || "";
        this.config.ajaxUrl = orplStockAdjustments.ajaxUrl || "";
        this.config.strings = orplStockAdjustments.strings || {};
      }

      // Bind events
      this.bindEvents();

      // Load initial data
      this.loadStocks();
      this.loadAdjustments(1);
    },

    /**
     * Bind all event handlers
     */
    bindEvents: function () {
      var self = this;

      // Form submission
      $("#add-adjustment-form").on("submit", function (e) {
        e.preventDefault();
        self.handleAdjustmentSubmit();
      });

      // Stock selection change
      $("#adjustment-product").on("change", function () {
        var stockId = $(this).val();
        if (stockId) {
          self.loadCurrentStock(stockId);
        } else {
          $("#current-stock").text("0");
          self.calculateNewStock();
        }
      });

      // Type and quantity changes
      $("#adjustment-type, #adjustment-quantity").on("change input", function () {
        self.calculateNewStock();
      });

      // Search functionality
      var searchTimeout;
      $("#adjustment-search").on("input", function () {
        clearTimeout(searchTimeout);
        self.config.searchTerm = $(this).val().trim();

        searchTimeout = setTimeout(function () {
          self.loadAdjustments(1);
        }, 500);
      });

      // Type filter
      $("#type-filter").on("change", function () {
        self.config.typeFilter = $(this).val();
        self.loadAdjustments(1);
      });

      // Date filter
      $("#date-filter").on("change", function () {
        self.config.dateFilter = $(this).val();
        self.loadAdjustments(1);
      });

      // Per page change
      $("#per-page-select").on("change", function () {
        self.config.perPage = parseInt($(this).val());
        self.loadAdjustments(1);
      });

      // Refresh button
      $("#refresh-adjustments").on("click", function () {
        self.loadAdjustments(self.config.currentPage);
      });

      // Reset filters
      $("#reset-filters").on("click", function () {
        self.resetFilters();
      });

      // Pagination handlers
      $(document).on("click", ".first-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadAdjustments(1);
      });

      $(document).on("click", ".prev-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadAdjustments(self.config.currentPage - 1);
      });

      $(document).on("click", ".next-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadAdjustments(self.config.currentPage + 1);
      });

      $(document).on("click", ".last-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadAdjustments(self.config.totalPages);
      });

      $(document).on("keypress", "#current-page-selector", function (e) {
        if (e.which === 13) {
          var page = parseInt($(this).val());
          if (page >= 1 && page <= self.config.totalPages) {
            self.loadAdjustments(page);
          }
        }
      });

      // Delete adjustment (delegated)
      $(document).on("click", ".delete-adjustment", function () {
        self.handleDeleteAdjustment(this);
      });
    },

    /**
     * Load stocks for dropdown
     */
    loadStocks: function () {
      var self = this;

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "orpl_get_products_for_adjustments",
          nonce: self.config.getProductsNonce,
        },
        success: function (response) {
          if (response.success) {
            var select = $("#adjustment-product");
            select.empty().append('<option value="">' + self.config.strings.selectStock + "</option>");

            $.each(response.data, function (_, stock) {
              var displayText = stock.name;
              select.append($("<option>").val(stock.stock_id).text(displayText));
            });
          }
        },
      });
    },

    /**
     * Load current stock for selected product
     */
    loadCurrentStock: function (stockId) {
      var self = this;

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "orpl_get_current_stock",
          stock_id: stockId,
          nonce: self.config.getStockNonce,
        },
        success: function (response) {
          if (response.success) {
            $("#current-stock").text(response.data.current_stock || 0);
            self.calculateNewStock();
          }
        },
      });
    },

    /**
     * Load adjustments with pagination
     */
    loadAdjustments: function (page) {
      var self = this;
      
      // If page is undefined, use current page or default to 1
      self.config.currentPage = page || self.config.currentPage || 1;

      var tbody = $("#adjustment-list");
      tbody.html('<tr><td colspan="6" class="text-center p-5"><span class="spinner is-active"></span> ' + self.config.strings.loadingAdjustments + "</td></tr>");

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "orpl_get_stock_adjustments",
          page: self.config.currentPage,
          per_page: self.config.perPage,
          search: self.config.searchTerm,
          type: self.config.typeFilter,
          date: self.config.dateFilter,
          nonce: self.config.getNonce,
        },
        success: function (response) {
          tbody.empty();
          if (response.success) {
            if (!response.data.adjustments.length) {
              tbody.append('<tr><td colspan="6" class="text-center p-5 text-muted">' + self.config.strings.noAdjustments + "</td></tr>");
              self.updatePagination(response.data.pagination);
              return;
            }

            $.each(response.data.adjustments, function (_, adjustment) {
              var row = $("<tr>").attr("data-adjustment-id", adjustment.id);

              // Date column
              var date = new Date(adjustment.created_at);
              var formattedDate =
                date.toLocaleDateString() +
                " " +
                date.toLocaleTimeString([], {
                  hour: "2-digit",
                  minute: "2-digit",
                });
              row.append($("<td>").text(formattedDate));

              // Stock column
              row.append($("<td>").text(adjustment.product_name || "N/A"));

              // Type column
              var typeText = adjustment.adjustment_type === "increase" ? self.config.strings.increase : self.config.strings.decrease;
              row.append($("<td>").text(typeText));

              // Quantity column
              var quantityText = (adjustment.adjustment_type === "increase" ? "+" : "-") + adjustment.quantity;
              var quantityClass = adjustment.adjustment_type === "increase" ? "text-success" : "text-danger";
              row.append($("<td>").append($("<span>").addClass(quantityClass).text(quantityText)));

              // Note column
              row.append($("<td>").text(adjustment.note || "-"));

              // Actions column
              var actions = $('<td class="text-center">');
              actions.append($("<button>").addClass("btn btn-sm btn-danger delete-adjustment").text(self.config.strings.delete));
              row.append(actions);

              tbody.append(row);
            });

            self.updatePagination(response.data.pagination);
          } else {
            tbody.append('<tr><td colspan="6" class="text-center text-danger">' + response.data + "</td></tr>");
          }
        },
        error: function () {
          tbody.html('<tr><td colspan="6" class="text-center text-danger">' + self.config.strings.loadError + "</td></tr>");
        },
      });
    },

    /**
     * Update pagination controls
     */
    updatePagination: function (pagination) {
      var self = this;
      
      // Set values from pagination response
      self.config.totalPages = pagination.total_pages;
      self.config.totalItems = pagination.total_items;

      // Update displaying text
      $("#displaying-num").text(pagination.total_items + " " + self.config.strings.items);

      // Update page input and total pages
      $("#current-page-selector").val(self.config.currentPage);
      $(".total-pages").text(self.config.totalPages);

      // Update pagination buttons state
      $(".first-page, .prev-page").prop("disabled", self.config.currentPage === 1);
      $(".next-page, .last-page").prop("disabled", self.config.currentPage === self.config.totalPages);
    },

    /**
     * Calculate new stock based on adjustments
     */
    calculateNewStock: function () {
      var currentStock = parseInt($("#current-stock").text()) || 0;
      var adjustmentType = $("#adjustment-type").val();
      var quantity = parseInt($("#adjustment-quantity").val()) || 0;

      var adjustmentDisplay = (adjustmentType === "increase" ? "+" : "-") + quantity;
      var newStock = adjustmentType === "increase" ? currentStock + quantity : currentStock - quantity;

      $("#adjustment-display").text(adjustmentDisplay);
      $("#new-stock").text(newStock);

      // Update colors based on values
      $("#adjustment-display")
        .toggleClass("text-success", adjustmentType === "increase")
        .toggleClass("text-danger", adjustmentType === "decrease");

      $("#new-stock")
        .toggleClass("text-danger", newStock < 0)
        .toggleClass("text-warning", newStock === 0)
        .toggleClass("text-primary", newStock > 0);
    },

    /**
     * Handle adjustment form submission
     */
    handleAdjustmentSubmit: function () {
      var self = this;

      // Prevent double submission
      if (self.config.isSubmitting) {
        return false;
      }

      var stockId = $("#adjustment-product").val();
      var adjustmentType = $("#adjustment-type").val();
      var quantity = $("#adjustment-quantity").val();
      var note = $("#adjustment-note").val();

      // Validation
      if (!stockId) {
        alert(self.config.strings.selectStockError);
        return false;
      }
      if (quantity <= 0) {
        alert(self.config.strings.invalidQuantity);
        return false;
      }

      // Check if decrease would result in negative stock
      var currentStock = parseInt($("#current-stock").text()) || 0;
      var newStock = adjustmentType === "increase" ? currentStock + parseInt(quantity) : currentStock - parseInt(quantity);

      if (newStock < 0) {
        if (!confirm(self.config.strings.negativeStockConfirm)) {
          return false;
        }
      }

      // Set submitting state
      self.config.isSubmitting = true;
      self.setButtonLoading(true);

      $.post(
        self.config.ajaxUrl,
        {
          action: "orpl_add_stock_adjustment",
          stock_id: stockId,
          adjustment_type: adjustmentType,
          quantity: quantity,
          note: note,
          nonce: self.config.addNonce,
        },
        function (response) {
          if (response.success) {
            self.resetForm();
            self.loadAdjustments(1);
            // Reload current stock for the selected stock
            if (stockId) {
              self.loadCurrentStock(stockId);
            }
          } else {
            alert(self.config.strings.error + ": " + response.data);
          }
        }
      )
        .fail(function () {
          alert(self.config.strings.requestFailed);
        })
        .always(function () {
          // Reset submitting state
          self.config.isSubmitting = false;
          self.setButtonLoading(false);
        });
    },

    /**
     * Handle delete adjustment
     */
    handleDeleteAdjustment: function (button) {
      var self = this;

      if (!confirm(self.config.strings.confirmDelete)) {
        return;
      }

      var $button = $(button);
      var originalText = $button.text();
      var id = $button.closest("tr").data("adjustment-id");

      // Disable button and show loading
      $button.prop("disabled", true).text(self.config.strings.deleting);

      $.post(
        self.config.ajaxUrl,
        {
          action: "orpl_delete_stock_adjustment",
          id: id,
          nonce: self.config.deleteNonce,
        },
        function (response) {
          if (response.success) {
            self.loadAdjustments(self.config.currentPage);
          } else {
            alert(response.data);
          }
        }
      )
        .fail(function () {
          alert(self.config.strings.deleteFailed);
        })
        .always(function () {
          // Re-enable button
          $button.prop("disabled", false).text(originalText);
        });
    },

    /**
     * Reset all filters
     */
    resetFilters: function () {
      $("#adjustment-search").val("");
      $("#type-filter").val("");
      $("#date-filter").val("");

      this.config.searchTerm = "";
      this.config.typeFilter = "";
      this.config.dateFilter = "";

      this.loadAdjustments(1);
    },

    /**
     * Set loading state for submit button
     */
    setButtonLoading: function (loading) {
      var button = $("#submit-adjustment");
      var spinner = button.find(".spinner");
      var btnText = button.find(".btn-text");

      if (loading) {
        button.prop("disabled", true);
        spinner.show();
        btnText.text(this.config.strings.applying);
      } else {
        button.prop("disabled", false);
        spinner.hide();
        btnText.text(this.config.strings.applyAdjustment);
      }
    },

    /**
     * Reset form to initial state
     */
    resetForm: function () {
      $("#adjustment-quantity").val("1");
      $("#adjustment-note").val("");
      this.calculateNewStock();
      this.setButtonLoading(false);
    },
  };

  /**
   * Initialize when document is ready
   */
  $(document).ready(function () {
    if ($("#add-adjustment-form").length) {
      ORPLStockAdjustments.init();
    }
  });
})(jQuery);