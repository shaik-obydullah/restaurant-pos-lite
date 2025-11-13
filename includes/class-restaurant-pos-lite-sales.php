<?php
/**
 * Sales Management
 *
 * @package Restaurant_POS_Lite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sales class for Restaurant POS Lite
 */
class Restaurant_POS_Lite_Sales {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_get_sales', array( $this, 'ajax_get_sales' ) );
		add_action( 'wp_ajax_delete_sale', array( $this, 'ajax_delete_sale' ) );
		add_action( 'wp_ajax_print_sale', array( $this, 'ajax_print_sale' ) );
	}

	/**
	 * Format currency using helper class
	 *
	 * @param float $amount The amount to format.
	 * @return string
	 */
	private function format_currency( $amount ) {
		return Restaurant_POS_Lite_Helpers::format_currency( $amount );
	}

	/**
	 * Format date using helper class
	 *
	 * @param string $date_string The date string to format.
	 * @return string
	 */
	private function format_date( $date_string ) {
		return Restaurant_POS_Lite_Helpers::format_date( $date_string );
	}

	/**
	 * Get shop name using helper class
	 *
	 * @return string
	 */
	private function get_shop_name() {
		return Restaurant_POS_Lite_Helpers::get_shop_name();
	}

	/**
	 * Get shop info using helper class
	 *
	 * @return array
	 */
	private function get_shop_info() {
		return Restaurant_POS_Lite_Helpers::get_shop_info();
	}

	/**
	 * Get sales table name
	 *
	 * @return string
	 */
	private function get_sales_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'pos_sales';
	}

	/**
	 * Get customers table name
	 *
	 * @return string
	 */
	private function get_customers_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'pos_customers';
	}

	/**
	 * Get sale details table name
	 *
	 * @return string
	 */
	private function get_sale_details_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'pos_sale_details';
	}

	/**
	 * Get products table name
	 *
	 * @return string
	 */
	private function get_products_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'pos_products';
	}

	/**
	 * Render the sales page
	 */
	public function render_page() {
		$shop_name = $this->get_shop_name();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline" style="margin-bottom:20px;">
				<?php echo esc_html( $shop_name ); ?> - <?php esc_html_e( 'Sales', 'restaurant-pos-lite' ); ?>
			</h1>
			<hr class="wp-header-end">

			<!-- Search and Filter Section -->
			<div class="sales-header" style="margin-bottom:20px;">
				<div class="sales-filters" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
					<!-- Search by Invoice ID -->
					<div class="search-box">
						<input type="text" id="search-invoice" placeholder="<?php esc_attr_e( 'Search by Invoice ID...', 'restaurant-pos-lite' ); ?>"
							style="padding:6px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;min-width:250px;">
					</div>

					<!-- Date Range Filter -->
					<div class="date-filters" style="display:flex;gap:8px;align-items:center;">
						<input type="date" id="date-from"
							style="padding:6px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;">
						<span><?php esc_html_e( 'to', 'restaurant-pos-lite' ); ?></span>
						<input type="date" id="date-to"
							style="padding:6px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;">
					</div>

					<!-- Sale Type Filter -->
					<div class="type-filter">
						<select id="sale-type"
							style="padding:6px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;">
							<option value=""><?php esc_html_e( 'All Types', 'restaurant-pos-lite' ); ?></option>
							<option value="dineIn"><?php esc_html_e( 'Dine In', 'restaurant-pos-lite' ); ?></option>
							<option value="takeAway"><?php esc_html_e( 'Take Away', 'restaurant-pos-lite' ); ?></option>
							<option value="pickUp"><?php esc_html_e( 'Pick Up', 'restaurant-pos-lite' ); ?></option>
						</select>
					</div>

					<!-- Status Filter -->
					<div class="status-filter">
						<select id="sale-status"
							style="padding:6px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;">
							<option value=""><?php esc_html_e( 'All Status', 'restaurant-pos-lite' ); ?></option>
							<option value="completed"><?php esc_html_e( 'Completed', 'restaurant-pos-lite' ); ?></option>
							<option value="saveSale"><?php esc_html_e( 'Saved', 'restaurant-pos-lite' ); ?></option>
							<option value="canceled"><?php esc_html_e( 'Canceled', 'restaurant-pos-lite' ); ?></option>
						</select>
					</div>

					<!-- Action Buttons -->
					<button type="button" id="search-sales" class="button button-primary" style="padding:6px 12px;">
						<?php esc_html_e( 'Search', 'restaurant-pos-lite' ); ?>
					</button>
					<button type="button" id="reset-filters" class="button" style="padding:6px 12px;">
						<?php esc_html_e( 'Reset', 'restaurant-pos-lite' ); ?>
					</button>
				</div>
			</div>

			<!-- Sales Table -->
			<div class="sales-table-wrapper">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Invoice ID', 'restaurant-pos-lite' ); ?></th>
							<th><?php esc_html_e( 'Date', 'restaurant-pos-lite' ); ?></th>
							<th><?php esc_html_e( 'Customer', 'restaurant-pos-lite' ); ?></th>
							<th><?php esc_html_e( 'Type', 'restaurant-pos-lite' ); ?></th>
							<th><?php esc_html_e( 'Net Price', 'restaurant-pos-lite' ); ?></th>
							<th><?php esc_html_e( 'Tax', 'restaurant-pos-lite' ); ?></th>
							<th><?php esc_html_e( 'Vat', 'restaurant-pos-lite' ); ?></th>
							<th><?php esc_html_e( 'Discount', 'restaurant-pos-lite' ); ?></th>
							<th><?php esc_html_e( 'Grand Total', 'restaurant-pos-lite' ); ?></th>
							<th><?php esc_html_e( 'Paid Amount', 'restaurant-pos-lite' ); ?></th>
							<th><?php esc_html_e( 'Due', 'restaurant-pos-lite' ); ?></th>
							<th><?php esc_html_e( 'Status', 'restaurant-pos-lite' ); ?></th>
							<th style="text-align:center;"><?php esc_html_e( 'Actions', 'restaurant-pos-lite' ); ?></th>
						</tr>
					</thead>
					<tbody id="sales-list">
						<tr>
							<td colspan="12" class="loading-sales" style="text-align:center;">
								<span class="spinner is-active"></span>
								<?php esc_html_e( 'Loading sales...', 'restaurant-pos-lite' ); ?>
							</td>
						</tr>
					</tbody>
				</table>

				<!-- Pagination -->
				<div class="sales-pagination"
					style="margin-top:20px;display:flex;justify-content:space-between;align-items:center;">
					<div class="pagination-info" style="font-size:13px;color:#646970;">
						<?php
						printf(
							/* translators: 1: showing from, 2: showing to, 3: total sales */
							esc_html__( 'Showing %1$s - %2$s of %3$s sales', 'restaurant-pos-lite' ),
							'<span id="showing-from">0</span>',
							'<span id="showing-to">0</span>',
							'<span id="total-sales">0</span>'
						);
						?>
					</div>
					<div class="pagination-links" style="display:flex;gap:5px;">
						<button class="button" id="prev-page" disabled><?php esc_html_e( 'Previous', 'restaurant-pos-lite' ); ?></button>
						<span class="pagination-numbers" style="display:flex;gap:5px;align-items:center;"></span>
						<button class="button" id="next-page" disabled><?php esc_html_e( 'Next', 'restaurant-pos-lite' ); ?></button>
					</div>
				</div>
			</div>
			<script>
				jQuery(document).ready(function ($) {
					let currentPage = 1;
					let perPage = 10;
					let totalPages = 1;
					let currentSearch = '';
					let dateFrom = '';
					let dateTo = '';
					let saleType = '';
					let saleStatus = '';

					// Format currency using PHP helper output
					function formatCurrency(amount) {
						// Use the PHP formatted currency as template
						const template = '<?php echo esc_js( $this->format_currency( 0 ) ); ?>';
						if (amount === null || amount === undefined) return template;
						const amountFormatted = parseFloat(amount).toFixed(2);
						return template.replace('0.00', amountFormatted);
					}

					// Load initial sales
					loadSales();

					// Search functionality
					$('#search-sales').on('click', function () {
						currentSearch = $('#search-invoice').val().trim();
						dateFrom = $('#date-from').val();
						dateTo = $('#date-to').val();
						saleType = $('#sale-type').val();
						saleStatus = $('#sale-status').val();
						currentPage = 1;
						loadSales();
					});

					// Reset filters
					$('#reset-filters').on('click', function () {
						$('#search-invoice').val('');
						$('#date-from').val('');
						$('#date-to').val('');
						$('#sale-type').val('');
						$('#sale-status').val('');
						currentSearch = '';
						dateFrom = '';
						dateTo = '';
						saleType = '';
						saleStatus = '';
						currentPage = 1;
						loadSales();
					});

					// Enter key in search
					$('#search-invoice').on('keypress', function (e) {
						if (e.which === 13) {
							$('#search-sales').click();
						}
					});

					// Pagination
					$('#prev-page').on('click', function () {
						if (currentPage > 1) {
							currentPage--;
							loadSales();
						}
					});

					$('#next-page').on('click', function () {
						if (currentPage < totalPages) {
							currentPage++;
							loadSales();
						}
					});

					function loadSales() {
						$.ajax({
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
							type: 'GET',
							data: {
								action: 'get_sales',
								page: currentPage,
								per_page: perPage,
								search: currentSearch,
								date_from: dateFrom,
								date_to: dateTo,
								sale_type: saleType,
								status: saleStatus,
								nonce: '<?php echo esc_js( wp_create_nonce( 'get_sales' ) ); ?>'
							},
							success: function (response) {
								let tbody = $('#sales-list').empty();
								if (response.success) {
									updatePagination(response.data);

									if (!response.data.sales.length) {
										tbody.append('<tr><td colspan="12" style="text-align:center;padding:20px;"><?php echo esc_js( __( 'No sales found.', 'restaurant-pos-lite' ) ); ?></td></tr>');
										return;
									}

									$.each(response.data.sales, function (_, sale) {
										let row = $('<tr>').attr('data-sale-id', sale.id);

										// Invoice ID
										row.append($('<td>').append(
											$('<strong>').text(sale.invoice_id || '<?php echo esc_js( __( 'N/A', 'restaurant-pos-lite' ) ); ?>')
										));

										// Date
										let saleDate = new Date(sale.created_at);
										let formattedDate = saleDate.toLocaleDateString() + ' ' + saleDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
										row.append($('<td>').text(formattedDate));

										// Customer
										row.append($('<td>').text(sale.customer_name || '<?php echo esc_js( __( 'Walk-in Customer', 'restaurant-pos-lite' ) ); ?>'));

										// Sale Type
										let typeClass = 'sale-type-' + sale.sale_type;
										let typeText = sale.sale_type ?
											sale.sale_type.charAt(0).toUpperCase() + sale.sale_type.slice(1).replace(/([A-Z])/g, ' $1') :
											'<?php echo esc_js( __( 'N/A', 'restaurant-pos-lite' ) ); ?>';
										row.append($('<td>').append(
											$('<span>').addClass(typeClass).text(typeText)
										));

										// Net Price
										row.append($('<td>').text(formatCurrency(sale.net_price)));

										// Tax
										let totalTax =  parseFloat(sale.tax_amount || 0);
										row.append($('<td>').text(formatCurrency(totalTax)));
										
                                        // VAT
										let totalVat = parseFloat(sale.vat_amount || 0);
										row.append($('<td>').text(formatCurrency(totalVat)));

										// Discount
										row.append($('<td>').text(formatCurrency(sale.discount_amount)));

										// Grand Total
										row.append($('<td>').append(
											$('<strong>').text(formatCurrency(sale.grand_total))
										));

										// Paid Amount
										row.append($('<td>').text(formatCurrency(sale.paid_amount)));

										// Due Amount
										let dueAmount = parseFloat(sale.sale_due || 0);
										let dueClass = dueAmount > 0 ? 'amount-negative' : (dueAmount < 0 ? 'amount-positive' : 'amount-zero');
										row.append($('<td>').append(
											$('<span>').addClass(dueClass).text(formatCurrency(dueAmount))
										));

										// Status
										let statusClass = 'status-' + sale.status;
										let statusText = sale.status ?
											sale.status === 'saveSale' ? '<?php echo esc_js( __( 'Saved', 'restaurant-pos-lite' ) ); ?>' :
												sale.status.charAt(0).toUpperCase() + sale.status.slice(1) :
											'<?php echo esc_js( __( 'N/A', 'restaurant-pos-lite' ) ); ?>';
										row.append($('<td>').append(
											$('<span>').addClass(statusClass).text(statusText)
										));

										// Actions
										let actionsTd = $('<td style="text-align:center;">');
										let actionsDiv = $('<div>').addClass('sale-actions');

										// Print button
										actionsDiv.append(
											$('<button>').addClass('button button-small print-sale')
												.text('<?php echo esc_js( __( 'Print', 'restaurant-pos-lite' ) ); ?>')
										);

										// Delete button
										actionsDiv.append(
											$('<button>').addClass('button button-small button-link-delete delete-sale')
												.text('<?php echo esc_js( __( 'Delete', 'restaurant-pos-lite' ) ); ?>')
										);

										actionsTd.append(actionsDiv);
										row.append(actionsTd);

										tbody.append(row);
									});
								} else {
									tbody.append('<tr><td colspan="12" style="color:red;text-align:center;">' + response.data + '</td></tr>');
								}
							},
							error: function () {
								$('#sales-list').html('<tr><td colspan="12" style="color:red;text-align:center;"><?php echo esc_js( __( 'Failed to load sales.', 'restaurant-pos-lite' ) ); ?></td></tr>');
							}
						});
					}

					function updatePagination(data) {
						totalPages = Math.ceil(data.total / perPage);

						// Update showing info
						$('#showing-from').text(data.showing_from);
						$('#showing-to').text(data.showing_to);
						$('#total-sales').text(data.total);

						// Update pagination buttons
						$('#prev-page').prop('disabled', currentPage === 1);
						$('#next-page').prop('disabled', currentPage === totalPages || totalPages === 0);

						// Update page numbers
						let paginationNumbers = $('.pagination-numbers').empty();
						let startPage = Math.max(1, currentPage - 2);
						let endPage = Math.min(totalPages, startPage + 4);

						for (let i = startPage; i <= endPage; i++) {
							let pageBtn = $('<button>').addClass('button page-number')
								.text(i)
								.toggleClass('current', i === currentPage);

							if (i !== currentPage) {
								pageBtn.on('click', function () {
									currentPage = i;
									loadSales();
								});
							}

							paginationNumbers.append(pageBtn);
						}
					}

					// Print sale
					$(document).on('click', '.print-sale', function () {
						let saleId = $(this).closest('tr').data('sale-id');

						if (!saleId) {
							alert('<?php echo esc_js( __( 'Cannot print: No sale ID available', 'restaurant-pos-lite' ) ); ?>');
							return;
						}

						$.ajax({
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
							type: 'POST',
							data: {
								action: 'print_sale',
								sale_id: saleId,
								nonce: '<?php echo esc_js( wp_create_nonce( 'print_sale' ) ); ?>'
							},
							success: function (response) {
								if (response.success) {
									// Open print window with sale data
									let printWindow = window.open('', '_blank');
									let sale = response.data;
									let shopInfo = <?php echo wp_json_encode( $this->get_shop_info() ); ?>;

									let printContent = `
<!DOCTYPE html>
<html>
<head>
	<title><?php echo esc_js( __( 'Invoice', 'restaurant-pos-lite' ) ); ?> ${sale.invoice_id || '<?php echo esc_js( __( 'N/A', 'restaurant-pos-lite' ) ); ?>'}</title>
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
			${shopInfo.name ? `<h2>${shopInfo.name}</h2>` : ''}
			${shopInfo.address ? `<p>${shopInfo.address}</p>` : ''}
			${shopInfo.phone ? `<p><?php echo esc_js( __( 'Phone:', 'restaurant-pos-lite' ) ); ?> ${shopInfo.phone}</p>` : ''}
		</div>
		<h3><?php echo esc_js( __( 'INVOICE', 'restaurant-pos-lite' ) ); ?> #${sale.invoice_id || '<?php echo esc_js( __( 'N/A', 'restaurant-pos-lite' ) ); ?>'}</h3>
	</div>
	
	<div class="invoice-details">
		<div class="customer-info">
			<p><strong><?php echo esc_js( __( 'Customer:', 'restaurant-pos-lite' ) ); ?></strong> ${sale.customer_name || '<?php echo esc_js( __( 'Walk-in Customer', 'restaurant-pos-lite' ) ); ?>'}</p>
			${sale.customer_mobile ? `
			<p><strong><?php echo esc_js( __( 'Mobile:', 'restaurant-pos-lite' ) ); ?></strong> ${sale.customer_mobile}</p>
			` : ''}
			${sale.customer_email ? `
			<p><strong><?php echo esc_js( __( 'Email:', 'restaurant-pos-lite' ) ); ?></strong> ${sale.customer_email}</p>
			` : ''}
			${sale.customer_address ? `
			<p><strong><?php echo esc_js( __( 'Address:', 'restaurant-pos-lite' ) ); ?></strong> ${sale.customer_address}</p>
			` : ''}
			<p><strong><?php echo esc_js( __( 'Order Type:', 'restaurant-pos-lite' ) ); ?></strong> ${sale.sale_type ? sale.sale_type.charAt(0).toUpperCase() + sale.sale_type.slice(1).replace(/([A-Z])/g, ' $1') : '<?php echo esc_js( __( 'N/A', 'restaurant-pos-lite' ) ); ?>'}</p>
			${sale.cooking_instructions ? `<p><strong><?php echo esc_js( __( 'Cooking Instructions:', 'restaurant-pos-lite' ) ); ?></strong> ${sale.cooking_instructions}</p>` : ''}
		</div>
		<div class="invoice-info">
			<p><strong><?php echo esc_js( __( 'Date:', 'restaurant-pos-lite' ) ); ?></strong> ${new Date(sale.created_at).toLocaleString()}</p>
			<p><strong><?php echo esc_js( __( 'Status:', 'restaurant-pos-lite' ) ); ?></strong> ${sale.status ? sale.status.charAt(0).toUpperCase() + sale.status.slice(1) : '<?php echo esc_js( __( 'N/A', 'restaurant-pos-lite' ) ); ?>'}</p>
		</div>
	</div>
	
	<table class="items-table">
		<thead>
			<tr>
				<th><?php echo esc_js( __( 'Item', 'restaurant-pos-lite' ) ); ?></th>
				<th><?php echo esc_js( __( 'Quantity', 'restaurant-pos-lite' ) ); ?></th>
				<th><?php echo esc_js( __( 'Price', 'restaurant-pos-lite' ) ); ?></th>
				<th><?php echo esc_js( __( 'Total', 'restaurant-pos-lite' ) ); ?></th>
			</tr>
		</thead>
		<tbody>
			${sale.items && sale.items.length > 0 ? sale.items.map(item => `
				<tr>
					<td>${item.product_name}</td>
					<td>${item.quantity}</td>
					<td>${formatCurrency(item.unit_price)}</td>
					<td>${formatCurrency(item.total_price)}</td>
				</tr>
			`).join('') : '<tr><td colspan="4" style="text-align:center;"><?php echo esc_js( __( 'No items found', 'restaurant-pos-lite' ) ); ?></td></tr>'}
		</tbody>
	</table>
	
	<table class="summary-table">
		<tr>
			<td><?php echo esc_js( __( 'Net Price:', 'restaurant-pos-lite' ) ); ?></td>
			<td>${formatCurrency(sale.net_price || 0)}</td>
		</tr>
		<tr>
			<td><?php echo esc_js( __( 'Tax:', 'restaurant-pos-lite' ) ); ?></td>
			<td>${formatCurrency((parseFloat(sale.tax_amount || 0)))}</td>
		</tr>
        <tr>
			<td><?php echo esc_js( __( 'VAT:', 'restaurant-pos-lite' ) ); ?></td>
			<td>${formatCurrency((parseFloat(sale.vat_amount || 0)))}</td>
		</tr>
		<tr>
			<td><?php echo esc_js( __( 'Shipping:', 'restaurant-pos-lite' ) ); ?></td>
			<td>${formatCurrency(sale.shipping_cost || 0)}</td>
		</tr>
		<tr>
			<td><?php echo esc_js( __( 'Discount:', 'restaurant-pos-lite' ) ); ?></td>
			<td>${formatCurrency(sale.discount_amount || 0)}</td>
		</tr>
		<tr class="total">
			<td><?php echo esc_js( __( 'Grand Total:', 'restaurant-pos-lite' ) ); ?></td>
			<td>${formatCurrency(sale.grand_total || 0)}</td>
		</tr>
		<tr>
			<td><?php echo esc_js( __( 'Paid Amount:', 'restaurant-pos-lite' ) ); ?></td>
			<td>${formatCurrency(sale.paid_amount || 0)}</td>
		</tr>
		<tr class="total">
			<td><?php echo esc_js( __( 'Balance Due:', 'restaurant-pos-lite' ) ); ?></td>
			<td>${formatCurrency(sale.sale_due || 0)}</td>
		</tr>
	</table>
	
	${sale.note ? `<div style="margin-top: 20px;"><strong><?php echo esc_js( __( 'Note:', 'restaurant-pos-lite' ) ); ?></strong> ${sale.note}</div>` : ''}
	
	<div class="thank-you">
		<p><?php echo esc_js( __( 'Thank you for your business!', 'restaurant-pos-lite' ) ); ?></p>
	</div>
</body>
</html>
									`;

									printWindow.document.write(printContent);
									printWindow.document.close();
									printWindow.focus();
									printWindow.print();
								} else {
									alert('<?php echo esc_js( __( 'Error:', 'restaurant-pos-lite' ) ); ?> ' + response.data);
								}
							},
							error: function (xhr, status, error) {
								alert('<?php echo esc_js( __( 'Request failed:', 'restaurant-pos-lite' ) ); ?> ' + error);
							}
						});
					});

					// Delete sale
					$(document).on('click', '.delete-sale', function () {
						if (!confirm('<?php echo esc_js( __( 'Are you sure you want to delete this sale? This action cannot be undone.', 'restaurant-pos-lite' ) ); ?>')) return;

						let button = $(this);
						let originalText = button.text();
						let id = $(this).closest('tr').data('sale-id');

						// Disable button and show loading
						button.prop('disabled', true).text('<?php echo esc_js( __( 'Deleting...', 'restaurant-pos-lite' ) ); ?>');

						$.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
							action: 'delete_sale',
							id: id,
							nonce: '<?php echo esc_js( wp_create_nonce( 'delete_sale' ) ); ?>'
						}, function (res) {
							if (res.success) {
								loadSales();
							} else {
								alert(res.data);
							}
						}).fail(() => alert('<?php echo esc_js( __( 'Delete request failed. Please try again.', 'restaurant-pos-lite' ) ); ?>'))
							.always(function () {
								// Re-enable button
								button.prop('disabled', false).text(originalText);
							});
					});
				});
			</script>
		</div>
		<?php
	}

	/** Get sales with pagination and search */
	public function ajax_get_sales() {
		// Check nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ?? '' ) ), 'get_sales' ) ) {
			wp_send_json_error( __( 'Security verification failed', 'restaurant-pos-lite' ) );
		}

		global $wpdb;
		$sales_table = $this->get_sales_table_name();
		$customers_table = $this->get_customers_table_name();

		$page = isset( $_GET['page'] ) ? max( 1, intval( $_GET['page'] ) ) : 1;
		$per_page = isset( $_GET['per_page'] ) ? max( 1, intval( $_GET['per_page'] ) ) : 10;
		$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
		$date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
		$sale_type = isset( $_GET['sale_type'] ) ? sanitize_text_field( wp_unslash( $_GET['sale_type'] ) ) : '';
		$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';

		$offset = ( $page - 1 ) * $per_page;

		// Build WHERE clause
		$where_clause = '1=1';
		$prepare_args = array();

		if ( ! empty( $search ) ) {
			$where_clause .= ' AND s.invoice_id LIKE %s';
			$prepare_args[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		if ( ! empty( $date_from ) ) {
			$where_clause .= ' AND DATE(s.created_at) >= %s';
			$prepare_args[] = $date_from;
		}

		if ( ! empty( $date_to ) ) {
			$where_clause .= ' AND DATE(s.created_at) <= %s';
			$prepare_args[] = $date_to;
		}

		if ( ! empty( $sale_type ) ) {
			$where_clause .= ' AND s.sale_type = %s';
			$prepare_args[] = $sale_type;
		}

		if ( ! empty( $status ) ) {
			$where_clause .= ' AND s.status = %s';
			$prepare_args[] = $status;
		}

		// Get total count
		$count_query = "SELECT COUNT(*) FROM $sales_table s WHERE $where_clause";
		if ( ! empty( $prepare_args ) ) {
			$count_query = $wpdb->prepare( $count_query, $prepare_args );
		}
		$total = $wpdb->get_var( $count_query );

		// Get sales data with customer information
		$query = "
			SELECT s.*, c.name as customer_name 
			FROM $sales_table s 
			LEFT JOIN $customers_table c ON s.fk_customer_id = c.id 
			WHERE $where_clause 
			ORDER BY s.created_at DESC 
			LIMIT %d OFFSET %d
		";

		$prepare_args[] = $per_page;
		$prepare_args[] = $offset;

		$query = $wpdb->prepare( $query, $prepare_args );
		$sales = $wpdb->get_results( $query );

		// Calculate showing range
		$showing_from = $total > 0 ? $offset + 1 : 0;
		$showing_to   = min( $offset + $per_page, $total );

		wp_send_json_success(
			array(
				'sales'        => $sales,
				'total'        => $total,
				'showing_from' => $showing_from,
				'showing_to'   => $showing_to,
				'current_page' => $page,
				'per_page'     => $per_page,
			)
		);
	}

	/** Print sale (get sale details for printing) */
	public function ajax_print_sale() {
		// Check nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'print_sale' ) ) {
			wp_send_json_error( __( 'Security verification failed', 'restaurant-pos-lite' ) );
		}

		global $wpdb;
		$sales_table = $this->get_sales_table_name();
		$sale_details_table = $this->get_sale_details_table_name();
		$products_table = $this->get_products_table_name();
		$customers_table = $this->get_customers_table_name();

		$sale_id = isset( $_POST['sale_id'] ) ? intval( $_POST['sale_id'] ) : 0;

		if ( empty( $sale_id ) ) {
			wp_send_json_error( __( 'Invalid sale ID', 'restaurant-pos-lite' ) );
		}

		// Get sale details with customer info using sale ID
		$sale = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT s.*, c.name as customer_name, c.mobile as customer_mobile, c.email as customer_email, c.address as customer_address
				FROM $sales_table s 
				LEFT JOIN $customers_table c ON s.fk_customer_id = c.id 
				WHERE s.id = %d
			",
				$sale_id
			)
		);

		if ( ! $sale ) {
			wp_send_json_error( __( 'Sale not found', 'restaurant-pos-lite' ) );
		}

		// Get sale details with product names
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT sd.*, p.name as product_name 
				FROM $sale_details_table sd 
				LEFT JOIN $products_table p ON sd.fk_product_id = p.id 
				WHERE sd.fk_sale_id = %d
				ORDER BY sd.id ASC
			",
				$sale->id
			)
		);

		$sale->items = $items;

		wp_send_json_success( $sale );
	}

	/** Delete sale */
	public function ajax_delete_sale() {
		// Check nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'delete_sale' ) ) {
			wp_send_json_error( __( 'Security verification failed', 'restaurant-pos-lite' ) );
		}

		global $wpdb;
		$sales_table = $this->get_sales_table_name();
		$sale_details_table = $this->get_sale_details_table_name();

		$id = intval( $_POST['id'] ?? 0 );

		if ( ! $id ) {
			wp_send_json_error( __( 'Invalid sale ID', 'restaurant-pos-lite' ) );
		}

		// Start transaction
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Delete sale details first
			$items_deleted = $wpdb->delete( $sale_details_table, array( 'fk_sale_id' => $id ), array( '%d' ) );

			if ( false === $items_deleted ) {
				throw new Exception( __( 'Failed to delete sale details', 'restaurant-pos-lite' ) );
			}

			// Delete sale
			$sale_deleted = $wpdb->delete( $sales_table, array( 'id' => $id ), array( '%d' ) );

			if ( false === $sale_deleted ) {
				throw new Exception( __( 'Failed to delete sale', 'restaurant-pos-lite' ) );
			}

			$wpdb->query( 'COMMIT' );
			wp_send_json_success( __( 'Sale deleted successfully', 'restaurant-pos-lite' ) );

		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( $e->getMessage() );
		}
	}
}