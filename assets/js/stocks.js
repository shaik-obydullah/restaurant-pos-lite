/**
 * Stock Management
 * Plugin: Obydullah_Restaurant_POS_Lite
 * Version: 1.0.0
 */

(function ($) {
  "use strict";

  let ORPLStocks = {
    // Configuration
    config: {
      isSubmitting: false,
      currentPage: 1,
      perPage: 10,
      totalPages: 1,
      totalItems: 0,
      searchTerm: "",
      statusFilter: "",
      quantityFilter: "",
      addNonce: "",
      getNonce: "",
      deleteNonce: "",
      productsNonce: "",
      ajaxUrl: "",
      strings: {},
      searchTimeout: null,
    },

    /**
     * Initialize stocks module
     */
    init: function () {
      // Load configuration from localized script
      if (typeof orplStocks !== "undefined") {
        this.config.addNonce = orplStocks.addNonce || "";
        this.config.getNonce = orplStocks.getNonce || "";
        this.config.deleteNonce = orplStocks.deleteNonce || "";
        this.config.productsNonce = orplStocks.productsNonce || "";
        this.config.ajaxUrl = orplStocks.ajaxUrl || "";
        this.config.strings = orplStocks.strings || {};
      }

      // Bind events
      this.bindEvents();

      // Load initial data
      this.loadProducts();
      this.loadStocks();

      // Initial profit calculation
      this.calculateProfit();
    },

    /**
     * Bind all event handlers
     */
    bindEvents: function () {
      var self = this;

      // Form submission
      $("#add-stock-form").on("submit", function (e) {
        e.preventDefault();
        self.handleStockSubmit();
      });

      // Search functionality
      $("#stock-search").on("input", function () {
        clearTimeout(self.searchTimeout);
        self.config.searchTerm = $(this).val().trim();

        self.searchTimeout = setTimeout(() => {
          self.loadStocks(1);
        }, 500);
      });

      // Filters
      $("#status-filter").on("change", function () {
        self.config.statusFilter = $(this).val();
        self.loadStocks(1);
      });

      $("#quantity-filter").on("change", function () {
        self.config.quantityFilter = $(this).val();
        self.loadStocks(1);
      });

      // Per page change
      $("#per-page-select").on("change", function () {
        self.config.perPage = parseInt($(this).val());
        self.loadStocks(1);
      });

      // Pagination handlers
      $(".first-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadStocks(1);
      });

      $(".prev-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadStocks(self.config.currentPage - 1);
      });

      $(".next-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadStocks(self.config.currentPage + 1);
      });

      $(".last-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadStocks(self.config.totalPages);
      });

      // Page input
      $("#current-page-selector").on("keypress", function (e) {
        if (e.which === 13) {
          let page = parseInt($(this).val());
          if (page >= 1 && page <= self.config.totalPages) {
            self.loadStocks(page);
          }
        }
      });

      // Profit calculation
      $("#net-cost, #sale-cost, #stock-quantity").on("input", function () {
        self.calculateProfit();
      });

      // Delete stock (delegated)
      $(document).on("click", ".pos-action.delete", function () {
        self.handleDeleteStock(this);
      });
    },

    /**
     * Load products for dropdown
     */
    loadProducts: function () {
      var self = this;

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "orpl_get_products_for_stocks",
          nonce: self.config.productsNonce,
        },
        success: function (response) {
          if (response.success) {
            let select = $("#stock-product");
            select.empty().append('<option value="">' + (self.config.strings.selectProduct || "Select Product") + "</option>");

            $.each(response.data, function (_, product) {
              select.append($("<option>").val(product.id).text(product.name));
            });
          }
        },
      });
    },

    /**
     * Load stocks with pagination and filters
     */
    loadStocks: function (page = 1) {
      var self = this;
      self.config.currentPage = page;

      let tbody = $("#stock-list");
      tbody.html('<tr><td colspan="6" class="loading-stocks"><span class="spinner is-active"></span> ' + (self.config.strings.loadingStocks || "Loading stocks...") + "</td></tr>");

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "orpl_get_stocks",
          page: self.config.currentPage,
          per_page: self.config.perPage,
          search: self.config.searchTerm,
          status: self.config.statusFilter,
          quantity: self.config.quantityFilter,
          nonce: self.config.getNonce,
        },
        success: function (response) {
          tbody.empty();
          if (response.success) {
            if (!response.data.stocks.length) {
              tbody.append('<tr><td colspan="6" class="no-stocks">' + (self.config.strings.noStocks || "No stock entries found.") + "</td></tr>");
              self.updateSummaryCards();
              self.updatePagination(response.data.pagination);
              return;
            }

            $.each(response.data.stocks, function (_, stock) {
              let row = $("<tr>").attr("data-stock-id", stock.id);

              // Product column
              row.append($("<td>").text(stock.product_name || "N/A"));

              // Buy Price column
              row.append($("<td>").text(parseFloat(stock.net_cost).toFixed(2)));

              // Sale Price column
              row.append($("<td>").text(parseFloat(stock.sale_cost).toFixed(2)));

              // Quantity column with conditional styling
              let quantityCell = $("<td>").text(stock.quantity);
              let quantityNum = parseInt(stock.quantity);

              if (quantityNum === 0) {
                quantityCell.addClass("quantity-zero");
              } else if (quantityNum < 10) {
                quantityCell.addClass("quantity-low");
              }

              row.append(quantityCell);

              // Status column
              let statusClass = stock.status === "inStock" ? "badge bg-success" : stock.status === "outStock" ? "badge bg-danger" : "badge bg-warning"; // lowStock or any other

              // Capitalize first letter for display
              let statusText = stock.status.charAt(0).toUpperCase() + stock.status.slice(1);

              // Append to row
              row.append(
                $("<td>")
                  .addClass("compact-status")
                  .append(
                    $("<span>")
                      .addClass(statusClass + " badge-status")
                      .text(statusText)
                  )
              );

              // Actions column
              row.append(
                $("<td>")
                  .addClass("pos-row-actions")
                  .append(
                    $("<button>")
                      .addClass("pos-action delete")
                      .text(self.config.strings.delete || "Delete")
                      .attr("data-id", stock.id)
                  )
              );

              tbody.append(row);
            });

            self.updateSummaryCards(response.data.stocks);
            self.updatePagination(response.data.pagination);
          } else {
            tbody.append('<tr><td colspan="6" class="error-message">' + response.data + "</td></tr>");
          }
        },
        error: function () {
          $("#stock-list").html('<tr><td colspan="6" class="error-message">' + (self.config.strings.loadError || "Failed to load stocks.") + "</td></tr>");
        },
      });
    },

    /**
     * Update summary cards
     */
    updateSummaryCards: function (stocks = []) {
      let inStock = 0,
        outStock = 0,
        lowStock = 0,
        totalStocks = stocks.length;

      if (stocks.length > 0) {
        stocks.forEach((stock) => {
          if (stock.status === "inStock") inStock++;
          else if (stock.status === "outStock") outStock++;
          else if (stock.status === "lowStock") lowStock++;
        });
      }

      $("#in-stock-count").text(inStock);
      $("#out-stock-count").text(outStock);
      $("#low-stock-count").text(lowStock);
      $("#total-stocks-count").text(totalStocks);
    },

    /**
     * Update pagination controls
     */
    updatePagination: function (pagination) {
      this.config.totalPages = pagination.total_pages;
      this.config.totalItems = pagination.total_items;

      // Update displaying text
      $("#displaying-num").text(pagination.total_items + " " + (this.config.strings.items || "items"));

      // Update page input and total pages
      $("#current-page-selector").val(this.config.currentPage);
      $(".total-pages").text(this.config.totalPages);

      // Update pagination buttons state
      $(".first-page, .prev-page").prop("disabled", this.config.currentPage === 1);
      $(".next-page, .last-page").prop("disabled", this.config.currentPage === this.config.totalPages);
    },

    /**
     * Calculate profit based on form inputs
     */
    calculateProfit: function () {
      let netCost = parseFloat($("#net-cost").val()) || 0;
      let saleCost = parseFloat($("#sale-cost").val()) || 0;
      let quantity = parseInt($("#stock-quantity").val()) || 0;

      let profitPerUnit = saleCost - netCost;
      let totalProfit = profitPerUnit * quantity;
      let profitMargin = netCost > 0 ? (profitPerUnit / netCost) * 100 : 0;

      $("#profit-margin").text(profitMargin.toFixed(2) + "%");
      $("#total-profit").text(totalProfit.toFixed(2));

      // Color coding for profit
      if (profitMargin > 0) {
        $("#profit-margin, #total-profit").addClass("profit-positive");
        $("#profit-margin, #total-profit").removeClass("profit-negative profit-neutral");
      } else if (profitMargin < 0) {
        $("#profit-margin, #total-profit").addClass("profit-negative");
        $("#profit-margin, #total-profit").removeClass("profit-positive profit-neutral");
      } else {
        $("#profit-margin, #total-profit").addClass("profit-neutral");
        $("#profit-margin, #total-profit").removeClass("profit-positive profit-negative");
      }
    },

    /**
     * Handle stock form submission
     */
    handleStockSubmit: function () {
      var self = this;

      // Prevent double submission
      if (self.config.isSubmitting) {
        return false;
      }

      let fk_product_id = $("#stock-product").val();
      let net_cost = $("#net-cost").val();
      let sale_cost = $("#sale-cost").val();
      let quantity = $("#stock-quantity").val();
      let status = $("#stock-status").val();

      // Validation
      if (!fk_product_id) {
        showLimeModal(self.config.strings.selectProductRequired || "Please select a product", "Validation Error");
        return false;
      }

      if (net_cost <= 0) {
        showLimeModal(self.config.strings.validBuyPrice || "Please enter a valid buy price", "Validation Error");
        return false;
      }

      if (sale_cost <= 0) {
        showLimeModal(self.config.strings.validSalePrice || "Please enter a valid sale price", "Validation Error");
        return false;
      }

      if (quantity <= 0) {
        showLimeModal(self.config.strings.validQuantity || "Please enter a valid quantity greater than 0", "Validation Error");
        return false;
      }

      // Set submitting state
      self.config.isSubmitting = true;
      self.setButtonLoading(true);

      $.post(
        self.config.ajaxUrl,
        {
          action: "orpl_add_stock",
          fk_product_id: fk_product_id,
          net_cost: net_cost,
          sale_cost: sale_cost,
          quantity: quantity,
          status: status,
          nonce: self.config.addNonce,
        },
        function (res) {
          if (res.success) {
            showLimeModal(self.config.strings.successMessage || "Saved!", "Success");

            const modal = $("#lime-alert-modal");
            modal
              .find("#lime-alert-close")
              .off("click")
              .on("click", function () {
                self.resetForm();
                self.loadStocks(1);
                self.loadProducts();
                modal.addClass("d-none");
              });
          } else {
            showLimeModal(self.config.strings.error + " " + res.data, "Error");
          }
        }
      )
        .fail(function () {
          showLimeModal(self.config.strings.requestFailed || "Request failed. Please try again.", "Error");
        })
        .always(function () {
          // Reset submitting state
          self.config.isSubmitting = false;
          self.setButtonLoading(false);
        });
    },

    /**
     * Handle delete stock
     */
    handleDeleteStock: function (button) {
      var self = this;
      var $button = $(button);
      var originalText = $button.text();
      var id = $button.closest("tr").data("stock-id");

      // Show confirmation modal instead of default confirm
      showLimeConfirm(
        self.config.strings.confirmDelete || "Are you sure you want to delete this stock entry?",
        function onYes() {
          // Disable button and show deleting text
          $button.prop("disabled", true).text(self.config.strings.deleting || "Deleting...");

          // Send AJAX request to delete stock
          $.post(self.config.ajaxUrl, {
            action: "orpl_delete_stock",
            id: id,
            nonce: self.config.deleteNonce,
          })
            .done(function (res) {
              if (res.success) {
                self.loadStocks(self.config.currentPage);
                showLimeModal(res.data, "Success");
              } else {
                showLimeModal(res.data, "Error");
              }
            })
            .fail(function () {
              showLimeModal(self.config.strings.deleteFailed || "Delete request failed. Please try again.", "Error");
            })
            .always(function () {
              // Re-enable button
              $button.prop("disabled", false).text(originalText);
            });
        },
        "Confirm Delete"
      );
    },

    /**
     * Set loading state for submit button
     */
    setButtonLoading: function (loading) {
      let button = $("#submit-stock");
      let spinner = button.find(".spinner");
      let btnText = button.find(".btn-text");

      if (loading) {
        button.prop("disabled", true).addClass("button-loading");
        spinner.show();
        btnText.text(this.config.strings.saving || "Saving...");
      } else {
        button.prop("disabled", false).removeClass("button-loading");
        spinner.hide();
        btnText.text(this.config.strings.saveStock || "Save Stock");
      }
    },

    /**
     * Reset form to initial state
     */
    resetForm: function () {
      $("#stock-product").val("");
      $("#net-cost").val("0.00");
      $("#sale-cost").val("0.00");
      $("#stock-quantity").val("1");
      $("#stock-status").val("inStock");

      this.calculateProfit();
      this.setButtonLoading(false);
    },
  };

  /**
   * Initialize when document is ready
   */
  $(document).ready(function () {
    if ($(".orpl-stocks-page").length) {
      ORPLStocks.init();
    }
  });
})(jQuery);