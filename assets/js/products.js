/**
 * Product Management
 * Plugin: Obydullah_Restaurant_POS_Lite
 * Version: 1.0.1
 */
(function ($) {
  "use strict";

  let ORPLProducts = {
    // Configuration (will be populated from wp_localize_script)
    config: {
      isSubmitting: false,
      currentPage: 1,
      perPage: 10,
      totalPages: 1,
      totalItems: 0,
      searchTerm: "",
      searchTimeout: null,
      addNonce: "",
      editNonce: "",
      deleteNonce: "",
      getNonce: "",
      getCategoriesNonce: "",
      ajaxUrl: "",
      i18n: {},
    },

    /**
     * Initialize products module
     * Called from document ready
     */
    init: function () {
      // Load configuration from localized script
      if (typeof orplProducts !== "undefined") {
        this.config.addNonce = orplProducts.addNonce || "";
        this.config.editNonce = orplProducts.editNonce || "";
        this.config.deleteNonce = orplProducts.deleteNonce || "";
        this.config.getNonce = orplProducts.getNonce || "";
        this.config.getCategoriesNonce = orplProducts.getCategoriesNonce || "";
        this.config.ajaxUrl = orplProducts.ajaxUrl || "";
        this.config.i18n = orplProducts.strings || {};
      }

      // Bind events
      this.bindEvents();

      // Load initial data
      this.loadCategories();
      this.loadProducts();
    },

    /**
     * Bind all event handlers
     */
    bindEvents: function () {
      var self = this;

      // Form submission
      $("#add-product-form").on("submit", function (e) {
        e.preventDefault();
        self.handleProductSubmit();
      });

      // Cancel edit
      $("#cancel-edit").on("click", function () {
        self.resetForm();
      });

      // Edit product button (delegated)
      $(document).on("click", ".pos-action.edit", function () {
        self.handleEditProduct(this);
      });

      // Delete product button (delegated)
      $(document).on("click", ".pos-action.delete", function () {
        self.handleDeleteProduct(this);
      });

      // Search functionality
      $("#product-search").on("input", function () {
        self.handleSearchInput(this);
      });

      // Clear search
      $("#clear-search").on("click", function () {
        self.clearSearch();
      });

      // Per page change
      $("#per-page-select").on("change", function () {
        self.handlePerPageChange(this);
      });

      // Pagination handlers
      $(".first-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadProducts(1);
      });

      $(".prev-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadProducts(self.config.currentPage - 1);
      });

      $(".next-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadProducts(self.config.currentPage + 1);
      });

      $(".last-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadProducts(self.config.totalPages);
      });

      // Page input enter key
      $("#current-page-selector").on("keypress", function (e) {
        if (e.which === 13) {
          var page = parseInt($(this).val());
          if (page >= 1 && page <= self.config.totalPages) {
            self.loadProducts(page);
          }
        }
      });

      // Image preview
      $("#product-image").on("change", function (e) {
        self.handleImagePreview(e);
      });
    },

    /**
     * Load categories for dropdown
     */
    loadCategories: function () {
      var self = this;

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "orpl_get_categories_for_products",
          nonce: self.config.getCategoriesNonce,
        },
        success: function (response) {
          if (response.success) {
            var select = $("#product-category");
            select.empty().append('<option value="">' + self.config.i18n.selectCategory + "</option>");

            $.each(response.data, function (_, category) {
              select.append($("<option>").val(category.id).text(category.name));
            });
          }
        },
        error: function () {
          console.error("Failed to load categories");
        },
      });
    },

    /**
     * Load products with pagination
     */
    loadProducts: function (page) {
      var self = this;

      if (page) {
        self.config.currentPage = page;
      }

      var tbody = $("#product-list");
      tbody.html('<tr><td colspan="5" class="loading-products">' + '<span class="spinner is-active"></span> ' + self.config.i18n.loadingProducts + "</td></tr>");

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "orpl_get_products",
          page: self.config.currentPage,
          per_page: self.config.perPage,
          search: self.config.searchTerm,
          nonce: self.config.getNonce,
        },
        success: function (response) {
          tbody.empty();
          if (response.success) {
            if (!response.data.products.length) {
              var message = self.config.searchTerm ? self.config.i18n.noResults + ' "' + self.config.searchTerm + '".' : self.config.i18n.noProducts;
              tbody.append('<tr><td colspan="5" class="no-results">' + message + "</td></tr>");
              self.updatePagination(response.data.pagination);
              return;
            }

            $.each(response.data.products, function (_, product) {
              var row = $("<tr>").attr("data-product-id", product.id);

              var imageTd = $("<td>").addClass("compact-image-cell");
              if (product.image) {
                imageTd.append($("<img>").addClass("compact-thumb").attr("src", product.image).attr("alt", product.name));
              } else {
                imageTd.addClass("orpl-empty-cell").html("—");
              }
              row.append(imageTd);

              row.append($("<td>").addClass("text-ellipsis").text(product.name));

              row.append(
                $("<td>")
                  .addClass("compact-status")
                  .text(product.category_name || "—")
              );

              var statusClass = product.status === "active" ? "badge bg-success" : "badge bg-secondary";
              var statusText = product.status.charAt(0).toUpperCase() + product.status.slice(1);

              row.append(
                $("<td>")
                  .addClass("compact-status")
                  .append(
                    $("<span>")
                      .addClass(statusClass + " badge-status")
                      .text(statusText)
                  )
              );

              var actions = $("<td>").addClass("pos-row-actions");
              actions.append(
                $("<button>")
                  .addClass("pos-action edit")
                  .text(self.config.i18n.edit || "Edit")
                  .attr("data-id", product.id)
              );
              actions.append(
                $("<button>")
                  .addClass("pos-action delete")
                  .text(self.config.i18n.delete || "Delete")
                  .attr("data-id", product.id)
              );

              row.append(actions);
              tbody.append(row);
            });

            self.updatePagination(response.data.pagination);
          } else {
            tbody.append('<tr><td colspan="5" class="error-message">' + response.data + "</td></tr>");
          }
        },
        error: function () {
          tbody.html('<tr><td colspan="5" class="error-message">' + self.config.i18n.loadError + "</td></tr>");
        },
      });
    },

    /**
     * Update pagination controls
     */
    updatePagination: function (pagination) {
      var self = this;

      self.config.totalPages = pagination.total_pages;
      self.config.totalItems = pagination.total_items;

      // Update displaying text
      $("#displaying-num").text(pagination.total_items + " " + self.config.i18n.items);

      // Update page input and total pages
      $("#current-page-selector").val(self.config.currentPage);
      $(".total-pages").text(self.config.totalPages);

      // Update pagination buttons state
      $(".first-page, .prev-page").prop("disabled", self.config.currentPage === 1);
      $(".next-page, .last-page").prop("disabled", self.config.currentPage === self.config.totalPages);
    },

    /**
     * Handle product form submission
     */
    handleProductSubmit: function () {
      var self = this;

      // Prevent double submission
      if (self.config.isSubmitting) {
        return false;
      }

      var id = $("#product-id").val();
      var action = id ? "orpl_edit_product" : "orpl_add_product";
      var name = $("#product-name").val().trim();
      var fk_category_id = $("#product-category").val();
      var nonce = id ? self.config.editNonce : self.config.addNonce;

      // Validation

      if (!name) {
        showLimeModal(self.config.i18n.enterName, "Validation Error");
        return false;
      }

      if (!fk_category_id) {
        showLimeModal(self.config.i18n.selectCategoryError, "Validation Error");
        return false;
      }

      // Set submitting state
      self.config.isSubmitting = true;
      self.setButtonLoading(true);

      // Create FormData for file upload
      var formData = new FormData(document.getElementById("add-product-form"));
      formData.append("action", action);
      formData.append("id", id);
      formData.append("nonce", nonce);
      $.ajax({
        url: self.config.ajaxUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            showLimeModal(self.config.i18n.successMessage || "Saved!", "Success");
            const modal = $("#lime-alert-modal");
            modal
              .find("#lime-alert-close")
              .off("click")
              .on("click", function () {
                self.resetForm();
                self.loadProducts(self.config.currentPage);
                modal.addClass("d-none");
              });
          } else {
            showLimeModal(self.config.i18n.error + " " + response.data, "Error");
          }
        },
        error: function () {
          showLimeModal(self.config.i18n.requestFailed, "Error");
        },
        complete: function () {
          self.config.isSubmitting = false;
          self.setButtonLoading(false);
        },
      });
    },

    /**
     * Handle edit product button click
     */
    handleEditProduct: function (button) {
      var self = this;
      var productId = $(button).data("id");

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "orpl_get_products",
          id: productId,
          nonce: self.config.getNonce,
        },
        success: function (response) {
          if (response.success && response.data.products.length > 0) {
            var product = response.data.products[0];

            $("#product-id").val(product.id);
            $("#product-name").val(product.name);
            $("#product-category").val(product.fk_category_id);
            $("#product-status").val(product.status);

            if (product.image) {
              $("#preview-img").attr("src", product.image);
              $("#image-preview").show();
            } else {
              $("#image-preview").hide();
            }

            // Update UI for edit mode
            $("#form-title").text(self.config.i18n.editProduct);
            $("#submit-product .btn-text").text(self.config.i18n.updateProduct);
            $("#cancel-edit").show();
            $("#product-name").focus();
          }
        },
      });
    },

    /**
     * Handle delete product button click
     */
    handleDeleteProduct: function (button) {
      var self = this;
      var $button = $(button);
      var originalText = $button.text();
      var productId = $button.data("id");

      // Show confirmation modal
      showLimeConfirm(
        self.config.i18n.confirmDelete || "Are you sure you want to delete this product?",
        function onYes() {
          // User clicked Yes → disable button and show deleting text
          $button.prop("disabled", true).text(self.config.i18n.deleting);

          $.post(
            self.config.ajaxUrl,
            {
              action: "orpl_delete_product",
              id: productId,
              nonce: self.config.deleteNonce,
            },
            function (response) {
              // Show success/error modal
              showLimeModal(response.data, response.success ? "Success" : "Error");

              // After modal closes, reload products if successful
              if (response.success) {
                const modal = $("#lime-alert-modal");
                modal
                  .find("#lime-alert-close")
                  .off("click")
                  .on("click", function () {
                    self.loadProducts(self.config.currentPage);
                    modal.addClass("d-none");
                  });
              }
            }
          )
            .fail(function () {
              showLimeModal(self.config.i18n.deleteFailed || "Delete request failed. Please try again.", "Error");
            })
            .always(function () {
              // Re-enable button and restore original text
              $button.prop("disabled", false).text(originalText);
            });
        },
        "Confirm Delete"
      );
    },

    /**
     * Handle search input with debounce
     */
    handleSearchInput: function (input) {
      var self = this;
      clearTimeout(self.config.searchTimeout);
      self.config.searchTerm = $(input).val().trim();

      self.config.searchTimeout = setTimeout(function () {
        self.loadProducts(1); // Reset to first page when searching
      }, 500);
    },

    /**
     * Clear search term
     */
    clearSearch: function () {
      var self = this;
      $("#product-search").val("");
      self.config.searchTerm = "";
      self.loadProducts(1);
    },

    /**
     * Handle per page change
     */
    handlePerPageChange: function (select) {
      var self = this;
      self.config.perPage = parseInt($(select).val());
      self.loadProducts(1);
    },

    /**
     * Handle image preview
     */
    handleImagePreview: function (e) {
      var file = e.target.files[0];
      if (file) {
        var reader = new FileReader();
        reader.onload = function (e) {
          $("#preview-img").attr("src", e.target.result);
          $("#image-preview").show();
        };
        reader.readAsDataURL(file);
      }
    },

    /**
     * Set loading state for submit button
     */
    setButtonLoading: function (loading) {
      var button = $("#submit-product");
      var spinner = button.find(".spinner");
      var btnText = button.find(".btn-text");

      if (loading) {
        button.prop("disabled", true).addClass("button-loading");
        spinner.show();
        btnText.text(button.hasClass("button-loading") ? this.config.i18n.saving : this.config.i18n.updating);
      } else {
        button.prop("disabled", false).removeClass("button-loading");
        spinner.hide();
        btnText.text(btnText.text().includes("Update") ? this.config.i18n.updateProduct : this.config.i18n.saveProduct);
      }
    },

    /**
     * Reset form to initial state
     */
    resetForm: function () {
      $("#product-id").val("");
      $("#product-name").val("");
      $("#product-category").val("");
      $("#product-status").val("active");
      $("#product-image").val("");
      $("#image-preview").hide();

      // Update UI
      $("#form-title").text(this.config.i18n.addNewProduct);
      $("#submit-product .btn-text").text(this.config.i18n.saveProduct);
      $("#cancel-edit").hide();
      $("#product-name").focus();

      // Ensure button is enabled
      this.setButtonLoading(false);
    },
  };

  /**
   * Initialize when document is ready
   * Only initialize if we're on the products page
   */
  $(document).ready(function () {
    if ($("#add-product-form").length) {
      ORPLProducts.init();
    }
  });
})(jQuery);