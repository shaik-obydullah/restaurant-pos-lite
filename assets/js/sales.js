/**
 * Sales Management
 * Plugin: Obydullah_Restaurant_POS_Lite
 * Version: 1.0.1
 */
(function ($) {
  "use strict";
  let ORPLSales = {
    // Configuration (will be populated from wp_localize_script)
    config: {
      currentPage: 1,
      perPage: 10,
      totalPages: 1,
      totalItems: 0,
      currentSearch: "",
      dateFrom: "",
      dateTo: "",
      saleType: "",
      saleStatus: "",
      ajaxUrl: "",
      strings: {},
      currencyTemplate: "",
      shopInfo: {},
      nonces: {},
      searchTimeout: null,
    },

    /**
     * Initialize sales module
     * Called from document ready
     */
    init: function () {
      // Load configuration from localized script
      if (typeof orplSalesData !== "undefined") {
        this.config.ajaxUrl = orplSalesData.ajaxUrl || "";
        this.config.nonces = {
          get_sales: orplSalesData.nonce_get_sales || "",
          print_sale: orplSalesData.nonce_print_sale || "",
          delete_sale: orplSalesData.nonce_delete_sale || "",
        };
        this.config.currencyTemplate = orplSalesData.currency_template || "";
        this.config.shopInfo = orplSalesData.shop_info || {};
        this.config.strings = orplSalesData.strings || {};
      }

      // Bind events
      this.bindEvents();

      // Load initial sales
      this.loadORPLSales();
    },

    /**
     * Bind all event handlers
     */
    bindEvents: function () {
      var self = this;

      // Search functionality
      $("#search-sales").on("click", function () {
        self.config.currentSearch = $("#search-invoice").val().trim();
        self.config.dateFrom = $("#date-from").val();
        self.config.dateTo = $("#date-to").val();
        self.config.saleType = $("#sale-type").val();
        self.config.saleStatus = $("#sale-status").val();
        self.config.currentPage = 1;
        self.loadORPLSales();
      });

      // Reset filters
      $("#reset-filters").on("click", function () {
        $("#search-invoice").val("");
        $("#date-from").val("");
        $("#date-to").val("");
        $("#sale-type").val("");
        $("#sale-status").val("");
        self.config.currentSearch = "";
        self.config.dateFrom = "";
        self.config.dateTo = "";
        self.config.saleType = "";
        self.config.saleStatus = "";
        self.config.currentPage = 1;
        self.loadORPLSales();
      });

      // Real-time search
      $("#search-invoice").on("input", function () {
        clearTimeout(self.config.searchTimeout);
        self.config.currentSearch = $(this).val().trim();

        self.config.searchTimeout = setTimeout(() => {
          self.config.currentPage = 1;
          self.loadORPLSales();
        }, 500);
      });

      // Per page change
      $("#per-page-select").on("change", function () {
        self.config.perPage = parseInt($(this).val());
        self.config.currentPage = 1;
        self.loadORPLSales();
      });

      // Pagination handlers
      $(document).on("click", ".first-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) {
          self.config.currentPage = 1;
          self.loadORPLSales();
        }
      });

      $(document).on("click", ".prev-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) {
          self.config.currentPage--;
          self.loadORPLSales();
        }
      });

      $(document).on("click", ".next-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) {
          self.config.currentPage++;
          self.loadORPLSales();
        }
      });

      $(document).on("click", ".last-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) {
          self.config.currentPage = self.config.totalPages;
          self.loadORPLSales();
        }
      });

      // Handle Enter key in page selector
      $("#current-page-selector").on("keypress", function (e) {
        if (e.which === 13) {
          let page = parseInt($(this).val());
          if (page >= 1 && page <= self.config.totalPages) {
            self.config.currentPage = page;
            self.loadORPLSales();
          } else {
            alert("Please enter a page between 1 and " + self.config.totalPages);
          }
        }
      });

      // Print sale (delegated)
      $(document).on("click", ".pos-action.print", function () {
        self.handlePrintSale(this);
      });

      // Delete sale (delegated)
      $(document).on("click", ".pos-action.delete", function () {
        self.handleDeleteSale(this);
      });
    },

    /**
     * Format currency using PHP helper output
     */
    formatCurrency: function (amount) {
      if (amount === null || amount === undefined) return this.config.currencyTemplate;
      const amountFormatted = parseFloat(amount).toFixed(2);
      return this.config.currencyTemplate.replace("0.00", amountFormatted);
    },

    /**
     * Format sale type for display
     */
    formatSaleType: function (saleType) {
      if (!saleType) return this.config.strings.na || "N/A";
      if (saleType === "dineIn") return "Dine In";
      if (saleType === "takeAway") return "Take Away";
      if (saleType === "pickUp") return "Pick Up";
      if (saleType === "delivery") return "Delivery";
      return saleType;
    },

    /**
     * Format sale status for display
     */
    formatSaleStatus: function (status) {
      if (!status) return this.config.strings.na || "N/A";
      if (status === "saveSale") return this.config.strings.saved || "Saved";
      if (status === "completed") return "Completed";
      if (status === "pending") return "Pending";
      if (status === "canceled") return "Canceled";
      return status;
    },

    /**
     * Update pagination UI
     */
    updatePagination: function (pagination) {
      // Use total_pages from server response or calculate it
      this.config.totalPages = pagination.total_pages || Math.ceil(pagination.total / pagination.per_page);
      this.config.totalItems = pagination.total;

      // Update displaying text
      $("#displaying-num").text(pagination.total + " " + (this.config.strings.items || "items"));

      // Update page input and total pages
      $("#current-page-selector").val(this.config.currentPage);
      $(".total-pages").text(this.config.totalPages);

      // Update pagination buttons state
      $(".first-page, .prev-page").prop("disabled", this.config.currentPage === 1);
      $(".next-page, .last-page").prop("disabled", this.config.currentPage === this.config.totalPages);
    },

    /**
     * Load sales with pagination and filters
     */
    loadORPLSales: function () {
      var self = this;

      let tbody = $("#sales-list");
      tbody.html('<tr><td colspan="11" class="loading-sales"><span class="spinner is-active"></span> ' + (self.config.strings.loadingSales || "Loading sales...") + "</td></tr>");

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "orpl_get_sales",
          page: self.config.currentPage,
          per_page: self.config.perPage,
          search: self.config.currentSearch,
          date_from: self.config.dateFrom,
          date_to: self.config.dateTo,
          sale_type: self.config.saleType,
          status: self.config.saleStatus,
          nonce: self.config.nonces.get_sales,
        },
        success: function (response) {
          tbody.empty();
          if (response.success) {
            if (!response.data.sales || response.data.sales.length === 0) {
              tbody.append('<tr><td colspan="11" class="no-sales">' + (self.config.strings.noSales || "No sales found.") + "</td></tr>");
              self.updatePagination(response.data);
              return;
            }

            $.each(response.data.sales, function (_, sale) {
              let row = $("<tr>").attr("data-sale-id", sale.id);

              // Invoice ID column
              row.append($("<td>").text(sale.invoice_id || "N/A"));

              // Date column
              let saleDate = new Date(sale.created_at);
              let formattedDate = saleDate.toLocaleDateString() + " " + saleDate.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
              row.append($("<td>").text(formattedDate));

              // Customer column
              row.append($("<td>").text(sale.customer_name || self.config.strings.walkin_customer || "Walk-in"));

              // Type column
              let typeClass = sale.sale_type === "dineIn" ? "badge bg-primary" : sale.sale_type === "takeAway" ? "badge bg-info" : sale.sale_type === "pickUp" ? "badge bg-warning" : "badge bg-secondary";
              let typeText = self.formatSaleType(sale.sale_type);

              row.append(
                $("<td>")
                  .addClass("compact-status")
                  .append(
                    $("<span>")
                      .addClass(typeClass + " badge-status")
                      .text(typeText)
                  )
              );

              // Net Price column
              row.append($("<td>").text(self.formatCurrency(sale.net_price)));

              // Tax column
              row.append($("<td>").text(self.formatCurrency(sale.tax_amount)));

              // VAT column
              row.append($("<td>").text(self.formatCurrency(sale.vat_amount)));

              // Discount column
              let discountAmount = parseFloat(sale.discount_amount || 0);
              let discountCell = $("<td>").text(self.formatCurrency(discountAmount));
              if (discountAmount > 0) {
                discountCell.addClass("discount-applied");
              }
              row.append(discountCell);

              // Grand Total column
              row.append($("<td>").text(self.formatCurrency(sale.grand_total)));

              // Status column
              let statusClass = sale.status === "completed" ? "badge bg-success" : sale.status === "saveSale" ? "badge bg-warning" : sale.status === "pending" ? "badge bg-info" : "badge bg-danger";
              let statusText = self.formatSaleStatus(sale.status);

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
                      .addClass("pos-action print")
                      .text(self.config.strings.print || "Print")
                      .attr("data-id", sale.id)
                  )
                  .append(
                    $("<button>")
                      .addClass("pos-action delete")
                      .text(self.config.strings.delete || "Delete")
                      .attr("data-id", sale.id)
                  )
              );

              tbody.append(row);
            });

            self.updatePagination(response.data);
          } else {
            tbody.append('<tr><td colspan="11" class="error-message">' + response.data + "</td></tr>");
          }
        },
        error: function () {
          $("#sales-list").html('<tr><td colspan="11" class="error-message">' + (self.config.strings.loadError || "Failed to load sales.") + "</td></tr>");
        },
      });
    },

    /**
     * Handle print sale button click
     */
    handlePrintSale: function (button) {
      var self = this;
      let saleId = $(button).closest("tr").data("sale-id");

      if (!saleId) {
        alert(self.config.strings.cannot_print || "Cannot print: No sale ID available");
        return;
      }

      $.ajax({
        url: self.config.ajaxUrl,
        type: "POST",
        data: {
          action: "orpl_print_sale",
          sale_id: saleId,
          nonce: self.config.nonces.print_sale,
        },
        success: function (response) {
          if (response.success) {
            let sale = response.data;
            let shop = sale.shop_info || self.config.shop_info || {};

            let printContent = `
<!DOCTYPE html>
<html>
<head>
    <title>Invoice #${sale.invoice_id || sale.id}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Courier New', monospace; 
            font-size: 14px;
            line-height: 1.3;
            padding: 10px;
            max-width: 80mm;
            margin: 0 auto;
        }
        .header { 
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #000;
        }
        .shop-name { 
            font-weight: bold;
            font-size: 18px;
            text-transform: uppercase;
        }
        .shop-address { 
            font-size: 12px;
            margin: 2px 0;
        }
        .shop-phone { 
            font-size: 12px;
            margin-bottom: 5px;
        }
        .invoice-title { 
            font-weight: bold;
            text-align: center;
            margin: 5px 0;
            font-size: 16px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            font-size: 12px;
        }
        .info-label {
            font-weight: bold;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
        }
        .items-table th {
            text-align: left;
            padding: 3px 0;
            border-bottom: 1px dashed #000;
            font-weight: bold;
        }
        .items-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .items-table .name {
            width: 60%;
        }
        .items-table .qty {
            width: 15%;
            text-align: center;
        }
        .items-table .price {
            width: 25%;
            text-align: right;
        }
        .totals {
            margin-top: 10px;
            width: 100%;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
        }
        .total-row.total {
            font-weight: bold;
            border-top: 2px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #000;
            font-size: 11px;
        }
        @media print {
            body { 
                padding: 0;
                margin: 0;
            }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        ${shop.name ? `<div class="shop-name">${shop.name}</div>` : ""}
        ${shop.address ? `<div class="shop-address">${shop.address}</div>` : ""}
        ${shop.phone ? `<div class="shop-phone">Tel: ${shop.phone}</div>` : ""}
    </div>
    
    <!-- Invoice Title -->
    <div class="invoice-title">INVOICE</div>
    
    <!-- Basic Info -->
    <div class="info-row">
        <span class="info-label">Invoice #:</span>
        <span>${sale.invoice_id || sale.id}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Date:</span>
        <span>${new Date(sale.created_at).toLocaleDateString()}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Time:</span>
        <span>${new Date(sale.created_at).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Customer:</span>
        <span>${sale.customer_name || "Walk-in Customer"}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Type:</span>
        <span>${self.formatSaleType(sale.sale_type)}</span>
    </div>
    ${sale.table_number ? `<div class="info-row"><span class="info-label">Table:</span><span>${sale.table_number}</span></div>` : ""}
    
    <div class="divider"></div>
    
    <!-- Items -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="name">ITEM</th>
                <th class="qty">QTY</th>
                <th class="price">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            ${
              sale.items && sale.items.length > 0
                ? sale.items
                    .map(
                      (item) => `
            <tr>
                <td class="name">${item.product_name || "Item"}</td>
                <td class="qty">${item.quantity}</td>
                <td class="price">${self.formatCurrency(item.total_price)}</td>
            </tr>`
                    )
                    .join("")
                : `<tr><td colspan="3" style="text-align: center;">No items</td></tr>`
            }
        </tbody>
    </table>
    
    <div class="divider"></div>
    
    <div class="totals">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>${self.formatCurrency(sale.net_price || 0)}</span>
        </div>
        ${sale.tax_amount && parseFloat(sale.tax_amount) > 0 ? `<div class="total-row"><span>Tax (${parseFloat(sale.tax_rate || 0)}%):</span><span>${self.formatCurrency(sale.tax_amount)}</span></div>` : ""}
        ${sale.vat_amount && parseFloat(sale.vat_amount) > 0 ? `<div class="total-row"><span>VAT (${parseFloat(sale.vat_rate || 0)}%):</span><span>${self.formatCurrency(sale.vat_amount)}</span></div>` : ""}
        ${sale.discount_amount && parseFloat(sale.discount_amount) > 0 ? `<div class="total-row"><span>Discount:</span><span>-${self.formatCurrency(sale.discount_amount)}</span></div>` : ""}
        ${sale.shipping_cost && parseFloat(sale.shipping_cost) > 0 ? `<div class="total-row"><span>Shipping:</span><span>${self.formatCurrency(sale.shipping_cost)}</span></div>` : ""}
        <div class="total-row total">
            <span>TOTAL:</span>
            <span>${self.formatCurrency(sale.grand_total || 0)}</span>
        </div>
    </div>
    
    <!-- Payment Method -->
    ${sale.payment_method ? `<div class="info-row" style="margin-top: 8px;"><span class="info-label">Payment:</span><span>${sale.payment_method}</span></div>` : ""}
    
    <!-- Notes -->
    ${sale.note ? `<div style="margin-top: 10px; padding: 5px; border: 1px dashed #666; font-size: 11px;"><strong>Note:</strong> ${sale.note}</div>` : ""}
    
    ${sale.cooking_instructions ? `<div style="margin-top: 5px; padding: 5px; border: 1px dashed #666; font-size: 11px;"><strong>Cooking Instructions:</strong> ${sale.cooking_instructions}</div>` : ""}
    
    <!-- Footer -->
    <div class="footer">
        Thank you for your business!<br>
        Please keep this receipt<br>
        ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}
    </div>
</body>
</html>`;

            let printWindow = window.open("", "_blank");
            if (!printWindow) {
              alert("Please allow popups to print the invoice.");
              return;
            }

            printWindow.document.head.innerHTML = `<title>Invoice #${sale.invoice_id || sale.id}</title>`;
            printWindow.document.body.innerHTML = printContent;

            printWindow.onload = function () {
              setTimeout(() => {
                printWindow.focus();
                printWindow.print();
                printWindow.onafterprint = function () {
                  printWindow.close();
                };
              }, 100);
            };
          } else {
            alert((self.config.strings.error || "Error:") + " " + response.data);
          }
        },
        error: function () {
          alert((self.config.strings.request_failed || "Request failed:") + " " + error);
        },
      });
    },

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml: function (text) {
      if (text === null || text === undefined) return "";
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    },

    /**
     * Handle delete sale
     */
    handleDeleteSale: function (button) {
      var self = this;
      var $button = $(button);
      var originalText = $button.text();
      var id = $button.closest("tr").data("sale-id");

      showLimeConfirm(
        self.config.strings.confirmDelete || "Are you sure you want to delete this sale?",
        function onYes() {
          $button.prop("disabled", true).text(self.config.strings.deleting || "Deleting...");

          $.post(self.config.ajaxUrl, {
            action: "orpl_delete_sale",
            id: id,
            nonce: self.config.nonces.delete_sale,
          })
            .done(function (res) {
              if (res.success) {
                self.loadORPLSales();
                showLimeModal(res.data, "Success");
              } else {
                showLimeModal(res.data, "Error");
              }
            })
            .fail(function () {
              showLimeModal(self.config.strings.deleteFailed || "Delete request failed. Please try again.", "Error");
            })
            .always(function () {
              $button.prop("disabled", false).text(originalText);
            });
        },
        "Confirm Delete"
      );
    },
  };

  /**
   * Initialize when document is ready
   */
  $(document).ready(function () {
    if ($("#search-sales").length) {
      ORPLSales.init();
    }
  });
})(jQuery);