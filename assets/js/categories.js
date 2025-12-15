/**
 * ORPL Restaurant POS Lite - Categories Management
 *
 * This file handles all JavaScript functionality for the Product Categories page.
 * It manages loading, adding, editing, and deleting categories via AJAX.
 *
 * @package     Obydullah_Restaurant_POS_Lite
 * @subpackage  Admin
 * @since       1.0.0
 *
 * Dependencies: jQuery, obydullah-restaurant-pos-lite-admin
 */

(function ($) {
  "use strict";
  /**
   * ORPL Categories Manager
   * Main controller for categories management functionality
   */
  let ORPLCategories = {
    // Configuration (will be populated from wp_localize_script)
    config: {
      isSubmitting: false,
      addNonce: "",
      editNonce: "",
      deleteNonce: "",
      getNonce: "",
      ajaxUrl: "",
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
          $("#category-list").html('<tr><td colspan="3" style="color:red;text-align:center;">' + "Failed to load categories." + "</td></tr>");
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
          tbody.append('<tr><td colspan="3" style="text-align:center;">' + "No categories found." + "</td></tr>");
          return;
        }

        $.each(response.data, function (_, cat) {
          var row = $("<tr>").attr("data-category-id", cat.id);

          // Name column
          row.append($("<td>").text(cat.name));

          // Status column with badge
          var statusClass = cat.status === "active" ? "status-active" : "status-inactive";
          var statusText = cat.status.charAt(0).toUpperCase() + cat.status.slice(1);
          row.append($("<td>").append($("<span>").addClass(statusClass).text(statusText)));

          // Actions column
          row.append($("<td>").append('<button class="button button-small edit-category">Edit</button>' + '<button class="button button-small button-link-delete delete-category">Delete</button>'));

          tbody.append(row);
        });
      } else {
        tbody.append('<tr><td colspan="3" style="color:red;text-align:center;">' + response.data + "</td></tr>");
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
        alert("Please enter a category name");
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
          } else {
            alert("Error: " + response.data);
          }
        }
      )
        .fail(function () {
          alert("Request failed. Please try again.");
        })
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
      var status = row.find("td").eq(1).find("span").hasClass("status-active") ? "active" : "inactive";

      // Populate form
      $("#category-id").val(categoryId);
      $("#category-name").val(name);
      $("#category-status").val(status);

      // Update UI for edit mode
      $("#form-title").text("Edit Category");
      $("#submit-category .btn-text").text("Update Category");
      $("#cancel-edit").show();
      $("#category-name").focus();
    },

    /**
     * Handle delete category button click
     */
    handleDeleteCategory: function (button) {
      var self = this;

      if (!confirm("Are you sure you want to delete this category?")) {
        return;
      }

      var $button = $(button);
      var originalText = $button.text();
      var categoryId = $button.closest("tr").data("category-id");

      // Disable button and show loading
      $button.prop("disabled", true).text("Deleting...");

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
          } else {
            alert(response.data);
          }
        }
      )
        .fail(function () {
          alert("Delete request failed. Please try again.");
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
      var button = $("#submit-category");
      var spinner = button.find(".spinner");
      var btnText = button.find(".btn-text");

      if (loading) {
        button.prop("disabled", true).addClass("button-loading");
        spinner.show();
        btnText.text(button.hasClass("button-loading") ? "Saving..." : "Updating...");
      } else {
        button.prop("disabled", false).removeClass("button-loading");
        spinner.hide();
        btnText.text(btnText.text().includes("Update") ? "Update Category" : "Save Category");
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
      $("#form-title").text("Add New Category");
      $("#submit-category .btn-text").text("Save Category");
      $("#cancel-edit").hide();
      $("#category-name").focus();

      // Ensure button is enabled
      this.setButtonLoading(false);
    },
  };

  /**
   * Initialize when document is ready
   * Only initialize if we're on the categories page
   */
  $(document).ready(function () {
    if ($("#add-category-form").length) {
      ORPLCategories.init();
    }
  });
})(jQuery);