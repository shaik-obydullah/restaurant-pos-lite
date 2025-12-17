/**
 * ORPL Product Categories Manager
 */
(function ($) {
  "use strict";
  let ORPLCategories = {
    // Configuration (will be populated from wp_localize_script)
    config: {
      isSubmitting: false,
      addNonce: "",
      editNonce: "",
      deleteNonce: "",
      getNonce: "",
      ajaxUrl: "",
      strings: {},
    },

    /**
     * Initialize categories module
     * Called from document ready
     */
    init: function () {
      // Load configuration from localized script
      if (typeof orplCategories !== "undefined") {
        this.config.addNonce = orplCategories.addNonce || "";
        this.config.editNonce = orplCategories.editNonce || "";
        this.config.deleteNonce = orplCategories.deleteNonce || "";
        this.config.getNonce = orplCategories.getNonce || "";
        this.config.ajaxUrl = orplCategories.ajaxUrl || "";
        this.config.strings = orplCategories.strings || {};
      }

      // Bind events
      this.bindEvents();

      // Load initial categories
      this.loadCategories();
    },

    /**
     * Bind all event handlers
     */
    bindEvents: function () {
      var self = this;

      // Form submission
      $("#add-category-form").on("submit", function (e) {
        e.preventDefault();
        self.handleCategorySubmit();
      });

      // Cancel edit
      $("#cancel-edit").on("click", function () {
        self.resetForm();
      });

      // Edit category button (delegated)
      $(document).on("click", ".edit-category", function () {
        self.handleEditCategory(this);
      });

      // Delete category button (delegated)
      $(document).on("click", ".delete-category", function () {
        self.handleDeleteCategory(this);
      });
    },

    /**
     * Load categories via AJAX
     */
    loadCategories: function () {
      var self = this;

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "orpl_get_product_categories",
          nonce: self.config.getNonce,
        },
        success: function (response) {
          self.renderCategories(response);
        },
        error: function () {
          $("#category-list").html('<tr><td colspan="3" class="text-center text-danger">' + self.config.strings.loadError || "Failed to load categories." + "</td></tr>");
        },
      });
    },

    /**
     * Render categories in the table
     */
    renderCategories: function (response) {
      var tbody = $("#category-list").empty();

      if (response.success) {
        if (!response.data.length) {
          tbody.append('<tr><td colspan="3" class="text-center">' + this.config.strings.noCategories || "No categories found." + "</td></tr>");
          return;
        }

        $.each(
          response.data,
          function (_, cat) {
            var row = $("<tr>").attr("data-category-id", cat.id);

            // Name column
            row.append($("<td>").text(cat.name));

            // Status column with proper styling
            var statusClass = cat.status === "active" ? "badge bg-success" : "badge bg-secondary";
            var statusText = cat.status.charAt(0).toUpperCase() + cat.status.slice(1);
            row.append(
              $("<td>").append(
                $("<span>")
                  .addClass(statusClass)
                  .css({
                    display: "inline-block",
                    padding: "0.25em 0.4em",
                    "font-size": "75%",
                    "font-weight": "700",
                    "line-height": "1",
                    "text-align": "center",
                    "white-space": "nowrap",
                    "vertical-align": "baseline",
                    "border-radius": "0.25rem",
                  })
                  .text(statusText)
              )
            );

            // Actions column
            var actions = $("<td>").addClass("text-right");
            actions.append(
              $("<button>")
                .addClass("btn btn-sm btn-secondary mr-2 edit-category")
                .text(this.config.strings.edit || "Edit")
            );
            actions.append(
              $("<button>")
                .addClass("btn btn-sm btn-danger delete-category")
                .text(this.config.strings.delete || "Delete")
            );

            row.append(actions);
            tbody.append(row);
          }.bind(this)
        );
      } else {
        tbody.append('<tr><td colspan="3" class="text-center text-danger">' + response.data + "</td></tr>");
      }
    },

    /**
     * Handle category form submission
     */
    handleCategorySubmit: function () {
      var self = this;

      // Prevent double submission
      if (self.config.isSubmitting) {
        return false;
      }

      var id = $("#category-id").val();
      var action = id ? "orpl_edit_product_category" : "orpl_add_product_category";
      var name = $("#category-name").val().trim();
      var status = $("#category-status").val();
      var nonce = id ? self.config.editNonce : self.config.addNonce;

      // Validation
      if (!name) {
        alert(this.config.strings.nameRequired || "Please enter a category name");
        return false;
      }

      // Set submitting state
      self.config.isSubmitting = true;
      self.setButtonLoading(true);

      // AJAX request
      $.post(
        self.config.ajaxUrl,
        {
          action: action,
          id: id,
          name: name,
          status: status,
          nonce: nonce,
        },
        function (response) {
          if (response.success) {
            self.resetForm();
            self.loadCategories();
            // Show success message
            alert(response.data);
          } else {
            alert(this.config.strings.error + ": " + response.data);
          }
        }.bind(this)
      )
        .fail(
          function () {
            alert(this.config.strings.requestFailed || "Request failed. Please try again.");
          }.bind(this)
        )
        .always(function () {
          // Reset submitting state
          self.config.isSubmitting = false;
          self.setButtonLoading(false);
        });
    },

    /**
     * Handle edit category button click
     */
    handleEditCategory: function (button) {
      var row = $(button).closest("tr");
      var categoryId = row.data("category-id");
      var name = row.find("td").eq(0).text();

      // Get status from badge
      var statusBadge = row.find("td").eq(1).find("span");
      var statusText = statusBadge.text().toLowerCase();
      var isActive = statusText === "active";

      // Populate form
      $("#category-id").val(categoryId);
      $("#category-name").val(name);
      $("#category-status").val(isActive ? "active" : "inactive");

      // Update UI for edit mode
      $("#form-title").text(this.config.strings.editCategory || "Edit Category");
      $("#cancel-edit").show();
      $("#submit-category .btn-text").text(this.config.strings.updateCategory || "Update Category");
      $("#category-name").focus();
    },

    /**
     * Handle delete category button click
     */
    handleDeleteCategory: function (button) {
      var self = this;

      if (!confirm(this.config.strings.confirmDelete)) {
        return;
      }

      var $button = $(button);
      var originalText = $button.text();
      var categoryId = $button.closest("tr").data("category-id");

      // Disable button and show loading
      $button.prop("disabled", true).text(this.config.strings.deleting);

      $.post(
        this.config.ajaxUrl,
        {
          action: "orpl_delete_product_category",
          id: categoryId,
          nonce: this.config.deleteNonce,
        },
        function (response) {
          if (response.success) {
            self.loadCategories();
            alert(response.data);
          } else {
            alert(response.data);
          }
        }
      )
        .fail(
          function () {
            alert(this.config.strings.deleteFailed || "Delete request failed. Please try again.");
          }.bind(this)
        )
        .always(function () {
          // Re-enable button
          $button.prop("disabled", false).text(originalText);
        });
    },

    /**
     * Set loading state for submit button
     */
    setButtonLoading: function (loading) {
      var button = $("#submit-category");
      var spinner = button.find(".spinner");
      var btnText = button.find(".btn-text");

      if (loading) {
        button.prop("disabled", true);
        spinner.show();
        var isEditMode = $("#category-id").val() !== "";
        btnText.text(isEditMode ? this.config.strings.updating : this.config.strings.saving);
      } else {
        button.prop("disabled", false);
        spinner.hide();
        var isEditMode = $("#category-id").val() !== "";
        btnText.text(isEditMode ? this.config.strings.updateCategory || "Update Category" : this.config.strings.saveCategory || "Save Category");
      }
    },

    /**
     * Reset form to initial state
     */
    resetForm: function () {
      $("#category-id").val("");
      $("#category-name").val("");
      $("#category-status").val("active");

      // Update UI
      $("#form-title").text(this.config.strings.addNewCategory || "Add New Category");
      $("#cancel-edit").hide();
      $("#submit-category .btn-text").text(this.config.strings.saveCategory || "Save Category");
      $("#submit-category").prop("disabled", false);
      $("#submit-category .spinner").hide();
      $("#category-name").focus();
    },
  };

  /**
   * Initialize when document is ready
   */
  $(document).ready(function () {
    if ($("#add-category-form").length) {
      ORPLCategories.init();
    }
  });
})(jQuery);