/**
 * ORPL Sales Manager
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

      // Enter key in search
      $("#search-invoice").on("keypress", function (e) {
        if (e.which === 13) {
          $("#search-sales").click();
        }
      });

      // Per page change
      $("#per-page-select").on("change", function () {
        self.config.perPage = parseInt($(this).val());
        self.loadORPLSales(1);
      });

      // Pagination handlers
      $(".first-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadORPLSales(1);
      });

      $(".prev-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadORPLSales(self.config.currentPage - 1);
      });

      $(".next-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadORPLSales(self.config.currentPage + 1);
      });

      $(".last-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadORPLSales(self.config.totalPages);
      });

      $("#current-page-selector").on("keypress", function (e) {
        if (e.which === 13) {
          // Enter key
          let page = parseInt($(this).val());
          if (page >= 1 && page <= self.config.totalPages) {
            self.loadORPLSales(page);
          }
        }
      });

      // Print sale (delegated)
      $(document).on("click", ".print-sale", function () {
        self.handlePrintSale(this);
      });

      // Delete sale (delegated)
      $(document).on("click", ".delete-sale", function () {
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
      return saleType.charAt(0).toUpperCase() + saleType.slice(1).replace(/([A-Z])/g, " $1");
    },

    /**
     * Format sale status for display
     */
    formatSaleStatus: function (status) {
      if (!status) return this.config.strings.na || "N/A";
      if (status === "saveSale") return this.config.strings.saved || "Saved";
      return status.charAt(0).toUpperCase() + status.slice(1);
    },

    /**
     * Update pagination UI
     */
    updatePagination: function (pagination) {
      this.config.totalPages = pagination.total_pages;
      this.config.totalItems = pagination.total_items;

      // Update displaying text
      $("#displaying-num").text(pagination.total_items + " " + this.config.strings.items);

      // Update page input and total pages
      $("#current-page-selector").val(this.config.currentPage);
      $(".total-pages").text(this.config.totalPages);

      // Update pagination buttons state
      $(".first-page, .prev-page").prop("disabled", this.config.currentPage === 1);
      $(".next-page, .last-page").prop("disabled", this.config.currentPage === this.config.totalPages);
    },

    /**
     * Load sales via AJAX
     */
    loadORPLSales: function (page = 1) {
      var self = this;
      self.config.currentPage = page;

      let tbody = $("#sales-list");
      tbody.html('<tr><td colspan="11" class="text-center p-4"><span class="spinner is-active"></span> ' + self.config.strings.loading_sales + "</td></tr>");

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
          let tbody = $("#sales-list").empty();
          if (response.success) {
            self.updatePagination({
              total_items: response.data.total,
              total_pages: Math.ceil(response.data.total / self.config.perPage),
            });

            if (!response.data.sales.length) {
              let message = self.config.currentSearch || self.config.dateFrom || self.config.dateTo || self.config.saleType || self.config.saleStatus ? self.config.strings.no_sales_matching : self.config.strings.no_sales;
              tbody.append('<tr><td colspan="11" class="text-center p-4 text-muted">' + message + "</td></tr>");
              return;
            }

            $.each(response.data.sales, function (_, sale) {
              let row = $("<tr>").attr("data-sale-id", sale.id);

              // Invoice ID
              row.append($("<td>").append($("<strong>").text(sale.invoice_id || self.config.strings.na)));

              // Date
              let saleDate = new Date(sale.created_at);
              let formattedDate = saleDate.toLocaleDateString() + " " + saleDate.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
              row.append($("<td>").text(formattedDate));

              // Customer
              row.append($("<td>").text(sale.customer_name || self.config.strings.walkin_customer));

              // Sale Type
              let typeText = self.formatSaleType(sale.sale_type);
              row.append(
                $("<td>").append(
                  $("<span>")
                    .addClass("sale-type-" + sale.sale_type)
                    .text(typeText)
                )
              );

              // Net Price
              row.append($("<td>").text(self.formatCurrency(sale.net_price)));

              // Tax
              let totalTax = parseFloat(sale.tax_amount || 0);
              row.append($("<td>").text(self.formatCurrency(totalTax)));

              // VAT
              let totalVat = parseFloat(sale.vat_amount || 0);
              row.append($("<td>").text(self.formatCurrency(totalVat)));

              // Discount
              row.append($("<td>").text(self.formatCurrency(sale.discount_amount)));

              // Grand Total
              row.append($("<td>").append($("<strong>").text(self.formatCurrency(sale.grand_total))));

              // Status
              let statusText = self.formatSaleStatus(sale.status);
              row.append(
                $("<td>").append(
                  $("<span>")
                    .addClass("status-" + sale.status)
                    .text(statusText)
                )
              );

              // Actions
              let actionsTd = $("<td class='text-center'>");
              let actionsDiv = $("<div>").addClass("sale-actions d-flex gap-2 justify-content-center");

              // Print button
              actionsDiv.append($("<button>").addClass("btn btn-sm btn-primary print-sale").text(self.config.strings.print));

              // Delete button
              actionsDiv.append($("<button>").addClass("btn btn-sm btn-danger delete-sale ml-1").text(self.config.strings.delete));

              actionsTd.append(actionsDiv);
              row.append(actionsTd);

              tbody.append(row);
            });
          } else {
            tbody.append('<tr><td colspan="11" class="text-center text-danger">' + response.data + "</td></tr>");
          }
        },
        error: function () {
          $("#sales-list").html('<tr><td colspan="11" class="text-center text-danger">' + self.config.strings.failed_load + "</td></tr>");
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
        alert(self.config.strings.cannot_print);
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
            // Open print window with sale data
            let printWindow = window.open("", "_blank");
            let sale = response.data;
            let shopInfo = self.config.shopInfo;

            let printContent = `
<!DOCTYPE html>
<html>
<head>
    <title>${self.config.strings.invoice} ${sale.invoice_id || self.config.strings.na}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .invoice-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .shop-info { margin-bottom: 15px; }
        .invoice-details { margin-bottom: 20px; display: flex; justify-content: space-between; }
        .customer-info, .invoice-info { flex: 1; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background: #f8f9fa; border: 1px solid #ddd; padding: 10px; text-align: left; }
        .items-table td { border: 1px solid #ddd; padding: 8px; }
        .summary-table { width: 300px; margin-left: auto; border-collapse: collapse; }
        .summary-table td { padding: 8px; border-bottom: 1px solid #ddd; }
        .summary-table tr.total td { font-weight: bold; border-top: 2px solid #333; }
        .thank-you { text-align: center; margin-top: 30px; font-style: italic; color: #666; }
        @media print { 
            body { margin: 0; font-size: 12px; }
            .invoice-header { border-bottom-color: #000; }
            .summary-table tr.total td { border-top-color: #000; }
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div class="shop-info">
            ${shopInfo.name ? `<h2>${shopInfo.name}</h2>` : ""}
            ${shopInfo.address ? `<p>${shopInfo.address}</p>` : ""}
            ${shopInfo.phone ? `<p>${self.config.strings.phone}: ${shopInfo.phone}</p>` : ""}
        </div>
        <h3>${self.config.strings.invoice.toUpperCase()} #${sale.invoice_id || self.config.strings.na}</h3>
    </div>
    
    <div class="invoice-details">
        <div class="customer-info">
            <p><strong>${self.config.strings.customer}:</strong> ${sale.customer_name || self.config.strings.walkin_customer}</p>
            ${
              sale.customer_mobile
                ? `
            <p><strong>${self.config.strings.mobile}:</strong> ${sale.customer_mobile}</p>
            `
                : ""
            }
            ${
              sale.customer_email
                ? `
            <p><strong>${self.config.strings.email}:</strong> ${sale.customer_email}</p>
            `
                : ""
            }
            ${
              sale.customer_address
                ? `
            <p><strong>${self.config.strings.address}:</strong> ${sale.customer_address}</p>
            `
                : ""
            }
            <p><strong>${self.config.strings.order_type}:</strong> ${self.formatSaleType(sale.sale_type)}</p>
            ${sale.cooking_instructions ? `<p><strong>${self.config.strings.cooking_instructions}:</strong> ${sale.cooking_instructions}</p>` : ""}
        </div>
        <div class="invoice-info">
            <p><strong>${self.config.strings.date}:</strong> ${new Date(sale.created_at).toLocaleString()}</p>
            <p><strong>${self.config.strings.status}:</strong> ${self.formatSaleStatus(sale.status)}</p>
        </div>
    </div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th>${self.config.strings.item}</th>
                <th>${self.config.strings.quantity}</th>
                <th>${self.config.strings.price}</th>
                <th>${self.config.strings.total}</th>
            </tr>
        </thead>
        <tbody>
            ${
              sale.items && sale.items.length > 0
                ? sale.items
                    .map(
                      (item) => `
                <tr>
                    <td>${item.product_name}</td>
                    <td>${item.quantity}</td>
                    <td>${self.formatCurrency(item.unit_price)}</td>
                    <td>${self.formatCurrency(item.total_price)}</td>
                </tr>
            `
                    )
                    .join("")
                : `<tr><td colspan="4" style="text-align:center;">${self.config.strings.no_items}</td></tr>`
            }
        </tbody>
    </table>
    
    <table class="summary-table">
        <tr>
            <td>${self.config.strings.net_price}:</td>
            <td>${self.formatCurrency(sale.net_price || 0)}</td>
        </tr>
        <tr>
            <td>${self.config.strings.tax}:</td>
            <td>${self.formatCurrency(parseFloat(sale.tax_amount || 0))}</td>
        </tr>
        <tr>
            <td>${self.config.strings.vat}:</td>
            <td>${self.formatCurrency(parseFloat(sale.vat_amount || 0))}</td>
        </tr>
        <tr>
            <td>${self.config.strings.shipping}:</td>
            <td>${self.formatCurrency(sale.shipping_cost || 0)}</td>
        </tr>
        <tr>
            <td>${self.config.strings.discount}:</td>
            <td>${self.formatCurrency(sale.discount_amount || 0)}</td>
        </tr>
        <tr class="total">
            <td>${self.config.strings.grand_total}:</td>
            <td>${self.formatCurrency(sale.grand_total || 0)}</td>
        </tr>
    </table>
    
    ${sale.note ? `<div style="margin-top: 20px;"><strong>${self.config.strings.note}:</strong> ${sale.note}</div>` : ""}
</body>
</html>
                        `;

            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
          } else {
            alert(self.config.strings.error + ": " + response.data);
          }
        },
        error: function (xhr, status, error) {
          alert(self.config.strings.request_failed + ": " + error);
        },
      });
    },

    /**
     * Handle delete sale button click
     */
    handleDeleteSale: function (button) {
      var self = this;

      if (!confirm(self.config.strings.confirm_delete)) {
        return;
      }

      let $button = $(button);
      let originalText = $button.text();
      let saleId = $button.closest("tr").data("sale-id");

      // Disable button and show loading
      $button.prop("disabled", true).text(self.config.strings.deleting);

      $.post(
        self.config.ajaxUrl,
        {
          action: "orpl_delete_sale",
          id: saleId,
          nonce: self.config.nonces.delete_sale,
        },
        function (res) {
          if (res.success) {
            self.loadORPLSales(self.config.currentPage);
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
